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
            'sinif.donem',
            'ogrenciBilgisi',
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
        $satirlar = [[['Kayıt No', 'Öğrenci Adı', 'TC Kimlik', 'Sınıf', 'Dönem',
                        'Veli Adı', 'Tel 1', 'Tel 2', 'Durum',
                        'Kayıt Tarihi', 'Durum Tarihi'], null]];

        foreach ($this->kayitlar as $kayit) {
            $twRenk = $kayit->sinif?->renk ?? 'blue';
            $hexRenk = self::RENK_MAP[$twRenk] ?? null;

            $durum = $kayit->durum;
            $satirlar[] = [[
                $kayit->id,
                $kayit->ogrenciBilgisi?->ad_soyad,
                $kayit->ogrenciBilgisi?->tc_kimlik,
                $kayit->sinif?->ad,
                $kayit->sinif?->donem?->ad,
                $kayit->veliBilgisi?->ad_soyad,
                $kayit->veliBilgisi?->telefon_1,
                $kayit->veliBilgisi?->telefon_2,
                $durum instanceof \App\Enums\EkayitDurumu ? $durum->label() : (string) $durum,
                $kayit->created_at?->format('d.m.Y H:i'),
                $kayit->durum_tarihi?->format('d.m.Y H:i'),
            ], $hexRenk];
        }

        return $satirlar;
    }
}
