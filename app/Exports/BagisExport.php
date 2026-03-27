<?php

namespace App\Exports;

use App\Models\Bagis;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BagisExport
{
    public function __construct(private readonly Collection $bagislar)
    {
        $this->bagislar->loadMissing(['kalemler.bagisTuru', 'kisiler']);
    }

    public function download(string $dosyaAdi): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $writer = new Writer();
            $writer->openToFile('php://output');

            foreach ($this->satirlariUret() as $satir) {
                $writer->addRow(Row::fromValues($satir));
            }

            $writer->close();
        }, $dosyaAdi, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function saveToFile(string $yol): void
    {
        $dizin = dirname($yol);

        if (! is_dir($dizin)) {
            mkdir($dizin, 0755, true);
        }

        $writer = new Writer();
        $writer->openToFile($yol);

        foreach ($this->satirlariUret() as $satir) {
            $writer->addRow(Row::fromValues($satir));
        }

        $writer->close();
    }

    private function satirlariUret(): array
    {
        $satirlar = [[
            'Bağış No',
            'Bağış Türleri',
            'Durum',
            'Adet/Hisse',
            'Birim Fiyat',
            'Toplam Tutar',
            'Bağışçı Adı',
            'TC Kimlik',
            'Telefon',
            'E-posta',
            'Sahip Tipi',
            'Sahip Adı',
            'Sahip TC',
            'Ödeme Sağlayıcısı',
            'Ödeme Referans No',
            'Ödeme Tarihi',
            'Makbuz Linki',
        ]];

        foreach ($this->bagislar as $bagis) {
            /** @var Bagis $bagis */
            $bagisci = $bagis->odeyenKisi();
            $sahip = $bagis->sahipKisi() ?? $bagisci;

            $satirlar[] = [
                $bagis->bagis_no,
                $bagis->kalemler->pluck('bagisTuru.ad')->filter()->implode(', '),
                $bagis->durum?->label() ?? (string) $bagis->durum,
                (string) $bagis->kalemler->sum('adet'),
                $this->trOndalik($bagis->kalemler->avg(fn ($kalem) => (float) $kalem->birim_fiyat)),
                $this->trOndalik((float) $bagis->toplam_tutar),
                $bagisci?->ad_soyad,
                $bagisci?->tc_kimlik,
                $bagisci?->telefon,
                $bagisci?->eposta,
                $this->sahipTipi($bagis),
                $sahip?->ad_soyad,
                $sahip?->tc_kimlik,
                $bagis->odeme_saglayici?->label() ?? (string) $bagis->odeme_saglayici,
                $bagis->odeme_referans,
                $bagis->odeme_tarihi?->format('d.m.Y H:i'),
                $bagis->makbuzUrl(),
            ];
        }

        return $satirlar;
    }

    private function sahipTipi(Bagis $bagis): string
    {
        return $bagis->kalemler->contains(fn ($kalem) => $kalem->sahip_tipi === 'baskasi')
            ? 'Başkası Adına'
            : 'Kendi Adına';
    }

    private function trOndalik(?float $deger): string
    {
        return number_format((float) ($deger ?? 0), 2, ',', '.');
    }
}