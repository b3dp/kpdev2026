<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EkayitExport
{
    /** Tailwind renk adı → pastel hex (# yok) */
    private const RENK_MAP = [
        'blue'   => 'DBEAFE',
        'green'  => 'DCFCE7',
        'orange' => 'FFEDD5',
        'purple' => 'F3E8FF',
        'red'    => 'FEE2E2',
        'amber'  => 'FEF3C7',
        'teal'   => 'CCFBF1',
        'lime'   => 'ECFCCB',
        'pink'   => 'FCE7F3',
        'yellow' => 'FEF9C3',
    ];

    public function __construct(private readonly Collection $kayitlar)
    {
        $this->kayitlar->loadMissing([
            'sinif',
            'ogrenciBilgisi',
            'kimlikBilgisi',
            'veliBilgisi',
        ]);
    }

    public function download(string $dosyaAdi): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $writer = new Writer();
            $writer->openToFile('php://output');
            foreach ($this->satirlariUret() as [$deger, $renk]) {
                $satir = ($renk !== null)
                    ? Row::fromValues($deger, (new Style())->setBackgroundColor($renk))
                    : Row::fromValues($deger);
                $writer->addRow($satir);
            }
            $writer->close();
        }, $dosyaAdi, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** @return array{0: array, 1: string|null}[] */
    private function satirlariUret(): array
    {
        $satirlar = [[[
            'SN',
            'TC KİMLİK',
            'ADI SOYADI',
            'BABA ADI',
            'ANA ADI',
            'DOĞUM YERİ',
            'DOĞUM TARİHİ',
            'NUFUSA KAYITLI İL',
            'NUFUSA KAYITLI İLÇE',
            'NUFUSA KAYITLI MAHALLE KÖY',
            'NUFUSA KAYITLI CİLT NO',
            'NUFUSA KAYITLI AİLE SIRA NO',
            'ADRES',
            'TELEFON 1',
            'TELEFON 2',
            'SINIF',
        ], null]];

        foreach ($this->kayitlar->values() as $index => $kayit) {
            $twRenk = $kayit->sinif?->renk ?? 'blue';
            $hexRenk = self::RENK_MAP[$twRenk] ?? null;
            $ogrenci = $kayit->ogrenciBilgisi;
            $kimlik = $kayit->kimlikBilgisi;
            $veli = $kayit->veliBilgisi;

            $satirlar[] = [[
                $index + 1,
                $ogrenci?->tc_kimlik,
                $ogrenci?->ad_soyad,
                $ogrenci?->baba_adi,
                $ogrenci?->anne_adi,
                $ogrenci?->dogum_yeri,
                $ogrenci?->dogum_tarihi?->format('d.m.Y'),
                $kimlik?->kayitli_il,
                $kimlik?->kayitli_ilce,
                $kimlik?->kayitli_mahalle_koy,
                $kimlik?->cilt_no,
                $kimlik?->aile_sira_no,
                $this->adresiBirlestir($ogrenci?->adres, $ogrenci?->ikamet_il, $ogrenci?->ikamet_ilce),
                $this->telefonuEtiketle($veli?->telefon_1_sahibi, $veli?->telefon_1),
                $this->telefonuEtiketle($veli?->telefon_2_sahibi, $veli?->telefon_2),
                $kayit->sinif?->ad,
            ], $hexRenk];
        }

        return $satirlar;
    }

    private function adresiBirlestir(?string $adres, ?string $il, ?string $ilce): string
    {
        return collect([$adres, $ilce, $il])
            ->filter(fn (?string $deger) => filled($deger))
            ->implode(' / ');
    }

    private function telefonuEtiketle(?string $sahip, ?string $telefon): string
    {
        if (! filled($telefon)) {
            return '—';
        }

        $etiket = $this->telefonSahibiEtiketi($sahip);

        return $etiket === '—'
            ? (string) $telefon
            : $etiket.' - '.$telefon;
    }

    private function telefonSahibiEtiketi(?string $sahip): string
    {
        return match ($sahip) {
            'anne' => 'Anne',
            'baba' => 'Baba',
            'yakini' => 'Yakını',
            default => '—',
        };
    }
}
