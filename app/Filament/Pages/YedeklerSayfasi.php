<?php

namespace App\Filament\Pages;

use Illuminate\Support\Facades\Storage;
use Filament\Pages\Page;
use Throwable;

class YedeklerSayfasi extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Yedekler';

    protected static ?string $title = 'Yedekler';

    protected static ?string $slug = 'yedekler';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 15;

    protected static string $view = 'filament.pages.yedekler-sayfasi';

    public array $log_yedekleri = [];

    public array $gunluk_db_yedekleri = [];

    public array $aylik_db_yedekleri = [];

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Admin');
    }

    public function mount(): void
    {
        $this->log_yedekleri = $this->yedekleriGetir('backups/logs');
        $this->gunluk_db_yedekleri = $this->yedekleriGetir('backups/db/daily');
        $this->aylik_db_yedekleri = $this->yedekleriGetir('backups/db/monthly');
    }

    private function yedekleriGetir(string $klasor): array
    {
        try {
            return collect(Storage::disk('spaces')->allFiles($klasor))
                ->map(function (string $yol): array {
                    try {
                        $boyut = (int) Storage::disk('spaces')->size($yol);
                        $degisim = (int) Storage::disk('spaces')->lastModified($yol);
                    } catch (Throwable) {
                        $boyut = 0;
                        $degisim = 0;
                    }

                    return [
                        'yol' => $yol,
                        'dosya_adi' => basename($yol),
                        'boyut' => $boyut,
                        'boyut_formatli' => $this->boyutFormatla($boyut),
                        'degisim' => $degisim,
                        'degisim_formatli' => $degisim > 0 ? date('d.m.Y H:i', $degisim) : '—',
                    ];
                })
                ->sortByDesc('degisim')
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function boyutFormatla(int $bayt): string
    {
        if ($bayt <= 0) {
            return '0 B';
        }

        $birimler = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = (int) floor(log($bayt, 1024));
        $index = min($index, count($birimler) - 1);
        $deger = $bayt / (1024 ** $index);

        return number_format($deger, $index === 0 ? 0 : 2, ',', '.').' '.$birimler[$index];
    }
}