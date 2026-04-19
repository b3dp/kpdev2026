<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class YedeklemeService
{
    public function aylikLogYedegiAl(): bool
    {
        $geciciDosya = null;

        try {
            $baslangic = now()->startOfMonth()->subMonth()->startOfMonth();
            $bitis = (clone $baslangic)->addMonth();
            $donem = $baslangic->format('Y-m');
            $kayitSayisi = 0;

            $geciciDosya = $this->geciciDosyaYolu('activity-log-'.$donem.'.jsonl.gz');

            $this->activityLogDosyasiOlustur($geciciDosya, $baslangic, $bitis, $kayitSayisi);

            if ($kayitSayisi === 0) {
                Log::info('Aylık log yedeği atlandı; arşivlenecek kayıt bulunamadı.', [
                    'donem' => $donem,
                ]);

                $this->uzakDosyalariTemizle(
                    klasor: 'backups/logs',
                    eslesmeDeseni: '/activity-log-(\d{4}-\d{2})\.jsonl\.gz$/',
                    tutulacakAdet: 3,
                );

                return true;
            }

            $uzakYol = sprintf('backups/logs/%s/%s/activity-log-%s.jsonl.gz', $baslangic->format('Y'), $baslangic->format('m'), $donem);

            $this->spacesaYukleVeDogrula($geciciDosya, $uzakYol);

            $silinenKayit = DB::table('activity_log')
                ->where('created_at', '>=', $baslangic)
                ->where('created_at', '<', $bitis)
                ->delete();

            $this->uzakDosyalariTemizle(
                klasor: 'backups/logs',
                eslesmeDeseni: '/activity-log-(\d{4}-\d{2})\.jsonl\.gz$/',
                tutulacakAdet: 3,
            );

            Log::info('Aylık log yedeği tamamlandı.', [
                'donem' => $donem,
                'uzak_yol' => $uzakYol,
                'kayit_sayisi' => $kayitSayisi,
                'silinen_kayit' => $silinenKayit,
            ]);

            return true;
        } catch (Throwable $exception) {
            Log::error('Aylık log yedeği başarısız.', [
                'hata' => $exception->getMessage(),
            ]);

            return false;
        } finally {
            $this->geciciDosyayiSil($geciciDosya);
        }
    }

    public function gunlukVeritabaniYedegiAl(): bool
    {
        return $this->veritabaniYedegiAl(
            tip: 'daily',
            dosyaAdi: 'db-daily-'.now()->format('Y-m-d').'.sql.gz',
            uzakYol: 'backups/db/daily/db-daily-'.now()->format('Y-m-d').'.sql.gz',
            eslesmeDeseni: '/db-daily-(\d{4}-\d{2}-\d{2})\.sql\.gz$/',
            tutulacakAdet: 15,
        );
    }

    public function aylikVeritabaniYedegiAl(): bool
    {
        return $this->veritabaniYedegiAl(
            tip: 'monthly',
            dosyaAdi: 'db-monthly-'.now()->format('Y-m').'.sql.gz',
            uzakYol: 'backups/db/monthly/db-monthly-'.now()->format('Y-m').'.sql.gz',
            eslesmeDeseni: '/db-monthly-(\d{4}-\d{2})\.sql\.gz$/',
            tutulacakAdet: 6,
        );
    }

    private function veritabaniYedegiAl(string $tip, string $dosyaAdi, string $uzakYol, string $eslesmeDeseni, int $tutulacakAdet): bool
    {
        $geciciDosya = null;

        try {
            $geciciDosya = $this->geciciDosyaYolu($dosyaAdi);
            $this->veritabaniDumpOlustur($geciciDosya);
            $this->spacesaYukleVeDogrula($geciciDosya, $uzakYol);

            $this->uzakDosyalariTemizle(
                klasor: 'backups/db/'.$tip,
                eslesmeDeseni: $eslesmeDeseni,
                tutulacakAdet: $tutulacakAdet,
            );

            Log::info('Veritabanı yedeği tamamlandı.', [
                'tip' => $tip,
                'uzak_yol' => $uzakYol,
                'boyut' => is_file($geciciDosya) ? filesize($geciciDosya) : null,
            ]);

            return true;
        } catch (Throwable $exception) {
            Log::error('Veritabanı yedeği başarısız.', [
                'tip' => $tip,
                'hata' => $exception->getMessage(),
            ]);

            return false;
        } finally {
            $this->geciciDosyayiSil($geciciDosya);
        }
    }

    private function activityLogDosyasiOlustur(string $dosyaYolu, Carbon $baslangic, Carbon $bitis, int &$kayitSayisi): void
    {
        $gzip = gzopen($dosyaYolu, 'wb9');

        if ($gzip === false) {
            throw new RuntimeException('Log yedeği için geçici gzip dosyası oluşturulamadı.');
        }

        try {
            DB::table('activity_log')
                ->where('created_at', '>=', $baslangic)
                ->where('created_at', '<', $bitis)
                ->orderBy('id')
                ->chunkById(500, function (Collection $satirlar) use ($gzip, &$kayitSayisi): void {
                    foreach ($satirlar as $satir) {
                        $json = json_encode((array) $satir, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        if ($json === false) {
                            throw new RuntimeException('Log satırı JSON formatına dönüştürülemedi.');
                        }

                        gzwrite($gzip, $json."\n");
                        $kayitSayisi++;
                    }
                }, 'id');
        } finally {
            gzclose($gzip);
        }
    }

    private function veritabaniDumpOlustur(string $dosyaYolu): void
    {
        $baglanti = $this->veritabaniBaglantisiniGetir();
        $komut = $this->veritabaniDumpKomutunuOlustur($baglanti);
        $gzip = gzopen($dosyaYolu, 'wb9');

        if ($gzip === false) {
            throw new RuntimeException('Veritabanı yedeği için geçici gzip dosyası oluşturulamadı.');
        }

        $descriptor = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = [
            'MYSQL_PWD' => (string) ($baglanti['password'] ?? ''),
        ];

        $process = proc_open($komut, $descriptor, $pipes, null, $env);

        if (! is_resource($process)) {
            gzclose($gzip);
            throw new RuntimeException('mysqldump işlemi başlatılamadı.');
        }

        try {
            while (! feof($pipes[1])) {
                $veri = fread($pipes[1], 1024 * 1024);

                if ($veri === false) {
                    throw new RuntimeException('mysqldump çıktısı okunamadı.');
                }

                if ($veri !== '') {
                    gzwrite($gzip, $veri);
                }
            }

            $hataCiktisi = stream_get_contents($pipes[2]) ?: '';
            fclose($pipes[1]);
            fclose($pipes[2]);

            $cikisKodu = proc_close($process);

            if ($cikisKodu !== 0) {
                throw new RuntimeException(trim($hataCiktisi) !== '' ? trim($hataCiktisi) : 'mysqldump başarısız oldu.');
            }
        } finally {
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }

            if (is_resource($process)) {
                proc_terminate($process);
            }

            gzclose($gzip);
        }

        if (! is_file($dosyaYolu) || filesize($dosyaYolu) === 0) {
            throw new RuntimeException('Veritabanı yedek dosyası boş oluşturuldu.');
        }
    }

    private function spacesaYukleVeDogrula(string $yerelDosya, string $uzakYol): void
    {
        if (! is_file($yerelDosya) || filesize($yerelDosya) === 0) {
            throw new RuntimeException('Yüklenecek dosya bulunamadı veya boş.');
        }

        $akim = fopen($yerelDosya, 'r');

        if ($akim === false) {
            throw new RuntimeException('Yerel yedek dosyası açılamadı.');
        }

        try {
            $yuklendi = Storage::disk('spaces')->put($uzakYol, $akim, [
                'visibility' => 'private',
            ]);
        } finally {
            fclose($akim);
        }

        if (! $yuklendi) {
            throw new RuntimeException('Yedek dosyası DigitalOcean Spaces alanına yüklenemedi.');
        }

        if (! Storage::disk('spaces')->exists($uzakYol)) {
            throw new RuntimeException('Yedek dosyası Spaces üzerinde doğrulanamadı.');
        }

        $yerelBoyut = filesize($yerelDosya);
        $uzakBoyut = Storage::disk('spaces')->size($uzakYol);

        if ($yerelBoyut !== $uzakBoyut) {
            throw new RuntimeException('Yedek doğrulama boyut kontrolü başarısız oldu.');
        }

        $yerelHash = hash_file('sha256', $yerelDosya);
        $uzakHash = $this->uzakDosyaHashiAl($uzakYol);

        if ($yerelHash === false || $uzakHash === '' || $yerelHash !== $uzakHash) {
            throw new RuntimeException('Yedek doğrulama hash kontrolü başarısız oldu.');
        }
    }

    private function uzakDosyalariTemizle(string $klasor, string $eslesmeDeseni, int $tutulacakAdet): void
    {
        $dosyalar = collect(Storage::disk('spaces')->allFiles($klasor))
            ->map(function (string $yol) use ($eslesmeDeseni): ?array {
                if (! preg_match($eslesmeDeseni, $yol, $eslesme)) {
                    return null;
                }

                return [
                    'yol' => $yol,
                    'sirala' => $eslesme[1],
                ];
            })
            ->filter()
            ->sortByDesc('sirala')
            ->values();

        $dosyalar
            ->slice($tutulacakAdet)
            ->each(function (array $dosya): void {
                Storage::disk('spaces')->delete($dosya['yol']);
            });
    }

    private function uzakDosyaHashiAl(string $uzakYol): string
    {
        $akim = Storage::disk('spaces')->readStream($uzakYol);

        if ($akim === false) {
            throw new RuntimeException('Spaces üzerindeki yedek dosyası okunamadı.');
        }

        $baglam = hash_init('sha256');

        try {
            hash_update_stream($baglam, $akim);
        } finally {
            if (is_resource($akim)) {
                fclose($akim);
            }
        }

        return hash_final($baglam);
    }

    private function veritabaniBaglantisiniGetir(): array
    {
        $varsayilan = Config::get('database.default');
        $baglanti = Config::get('database.connections.'.$varsayilan);

        if (! is_array($baglanti) || ! in_array($baglanti['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Veritabanı yedeği için sadece mysql/mariadb bağlantısı desteklenmektedir.');
        }

        return $baglanti;
    }

    private function veritabaniDumpKomutunuOlustur(array $baglanti): array
    {
        $komut = [
            '/usr/bin/mysqldump',
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--default-character-set='.(string) ($baglanti['charset'] ?? 'utf8mb4'),
            '--user='.(string) ($baglanti['username'] ?? ''),
        ];

        if (filled($baglanti['unix_socket'] ?? null)) {
            $komut[] = '--socket='.(string) $baglanti['unix_socket'];
        } else {
            $komut[] = '--host='.(string) ($baglanti['host'] ?? '127.0.0.1');
            $komut[] = '--port='.(string) ($baglanti['port'] ?? '3306');
        }

        $komut[] = (string) ($baglanti['database'] ?? '');

        return $komut;
    }

    private function geciciDosyaYolu(string $dosyaAdi): string
    {
        $dizin = storage_path('app/private/tmp/yedekler');

        if (! is_dir($dizin)) {
            mkdir($dizin, 0755, true);
        }

        return $dizin.'/'.$dosyaAdi;
    }

    private function geciciDosyayiSil(?string $dosyaYolu): void
    {
        if ($dosyaYolu && is_file($dosyaYolu)) {
            unlink($dosyaYolu);
        }
    }
}