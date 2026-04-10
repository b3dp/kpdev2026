<?php

namespace App\Services;

use DateTime;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GoogleDriveService
{
    private Drive $drive;

    public function __construct()
    {
        $client = $this->googleClientOlustur();

        $this->drive = new Drive($client);
    }

    public function klasorBulVeyaOlustur(string $ad, string $ustKlasorId): string
    {
        $sorgu = sprintf(
            "mimeType = 'application/vnd.google-apps.folder' and trashed = false and name = '%s' and '%s' in parents",
            $this->tekTirnakKacis($ad),
            $this->tekTirnakKacis($ustKlasorId),
        );

        $sonuc = $this->drive->files->listFiles([
            'q' => $sorgu,
            'pageSize' => 1,
            'fields' => 'files(id, name)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);

        $mevcutKlasor = $sonuc->getFiles()[0] ?? null;

        if ($mevcutKlasor !== null) {
            return $mevcutKlasor->getId();
        }

        $olusturulan = $this->drive->files->create(new DriveFile([
            'name' => $ad,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$ustKlasorId],
        ]), [
            'fields' => 'id',
            'supportsAllDrives' => true,
        ]);

        return (string) $olusturulan->getId();
    }

    public function excelYukle(string $anaKlasorId, string $localDosyaYolu, string $dosyaAdi, ?DateTime $tarih = null): string
    {
        if (! is_file($localDosyaYolu)) {
            throw new RuntimeException("Yuklenecek Excel dosyasi bulunamadi: {$localDosyaYolu}");
        }

        $tarih ??= new DateTime();

        try {
            $tarihKlasoruId = $this->klasorBulVeyaOlustur($tarih->format('dmY'), $anaKlasorId);

            $yuklenenDosya = $this->drive->files->create(
                new DriveFile([
                    'name' => $dosyaAdi,
                    'parents' => [$tarihKlasoruId],
                ]),
                [
                    'data' => file_get_contents($localDosyaYolu),
                    'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'uploadType' => 'multipart',
                    'fields' => 'id, webViewLink',
                    'supportsAllDrives' => true,
                ]
            );

            return (string) $yuklenenDosya->getWebViewLink();
        } catch (Throwable $exception) {
            Log::error('Google Drive Excel yukleme hatasi', [
                'ana_klasor_id' => $anaKlasorId,
                'local_dosya_yolu' => $localDosyaYolu,
                'dosya_adi' => $dosyaAdi,
                'hata' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function docxDosyasiniPdfyeCevir(string $localDosyaYolu, string $hedefPdfYolu, ?string $dosyaAdi = null): void
    {
        if (! is_file($localDosyaYolu)) {
            throw new RuntimeException("Yuklenecek DOCX dosyasi bulunamadi: {$localDosyaYolu}");
        }

        $dosyaAdi = $dosyaAdi ?: pathinfo($localDosyaYolu, PATHINFO_FILENAME);
        $geciciDosyaId = null;

        try {
            $olusturulanDosya = $this->drive->files->create(
                new DriveFile([
                    'name' => $dosyaAdi,
                    'mimeType' => 'application/vnd.google-apps.document',
                ]),
                [
                    'data' => file_get_contents($localDosyaYolu),
                    'mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'uploadType' => 'multipart',
                    'fields' => 'id, name',
                    'supportsAllDrives' => true,
                ]
            );

            $geciciDosyaId = (string) $olusturulanDosya->getId();
            $yanit = $this->drive->files->export($geciciDosyaId, 'application/pdf', ['alt' => 'media']);
            $pdfIcerik = $yanit->getBody()->getContents();

            if ($pdfIcerik === '') {
                throw new RuntimeException('Google Drive PDF export bos dondu.');
            }

            file_put_contents($hedefPdfYolu, $pdfIcerik);
        } catch (Throwable $exception) {
            Log::error('Google Drive DOCX PDF cevirme hatasi', [
                'local_dosya_yolu' => $localDosyaYolu,
                'hedef_pdf_yolu' => $hedefPdfYolu,
                'dosya_adi' => $dosyaAdi,
                'hata' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            if (filled($geciciDosyaId)) {
                try {
                    $this->drive->files->delete($geciciDosyaId, ['supportsAllDrives' => true]);
                } catch (Throwable $exception) {
                    Log::warning('Google Drive gecici dokuman silinemedi', [
                        'dosya_id' => $geciciDosyaId,
                        'hata' => $exception->getMessage(),
                    ]);
                }
            }
        }
    }

    public static function dosyaAdiUret(string $prefix, string $baslangic, ?string $bitis = null): string
    {
        return collect([$prefix, $baslangic, $bitis])
            ->filter(fn (?string $parca) => filled($parca))
            ->implode('-').'.xlsx';
    }

    private function serviceAccountJsonYolu(): string
    {
        $jsonYolu = (string) config('services.google_drive.service_account_json_path');

        if ($jsonYolu === '') {
            throw new RuntimeException('Google Drive service account JSON yolu tanimli degil.');
        }

        if (str_starts_with($jsonYolu, DIRECTORY_SEPARATOR)) {
            return $jsonYolu;
        }

        return base_path($jsonYolu);
    }

    private function googleClientOlustur(): Client
    {
        $authMode = (string) config('services.google_drive.auth_mode', 'service_account');

        $client = new Client();
        $client->setApplicationName(config('app.name').' Google Drive');
        $client->setScopes([Drive::DRIVE]);

        if ($authMode === 'oauth_user') {
            $clientId = (string) config('services.google_drive.oauth_client_id');
            $clientSecret = (string) config('services.google_drive.oauth_client_secret');
            $refreshToken = (string) config('services.google_drive.oauth_refresh_token');

            if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
                throw new RuntimeException('Google Drive OAuth bilgileri eksik.');
            }

            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setAccessType('offline');
            $client->setPrompt('consent');

            $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (is_array($token) && isset($token['error'])) {
                throw new RuntimeException('Google OAuth token yenileme hatasi: '.json_encode($token));
            }

            return $client;
        }

        $jsonYolu = $this->serviceAccountJsonYolu();

        if (! is_file($jsonYolu)) {
            throw new RuntimeException("Google service account JSON dosyasi bulunamadi: {$jsonYolu}");
        }

        $client->setAuthConfig($jsonYolu);

        return $client;
    }

    private function tekTirnakKacis(string $deger): string
    {
        return str_replace("'", "\\'", $deger);
    }
}