<?php

namespace App\Filament\Pages;

use App\Support\KurumsalStatikSayfalar;
use Filament\Pages\Page;

class StatikSayfalarSayfasi extends Page
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Statik Sayfalar';

    protected static ?string $title = 'Statik Sayfalar';

    protected static ?string $slug = 'statik-sayfalar';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 24;

    protected static string $view = 'filament.pages.statik-sayfalar-sayfasi';

    public static function canAccess(): bool
    {
        return static::izinVarMi('kurumsal_sayfalar.listele');
    }

    public function getSayfalarProperty(): array
    {
        return collect(KurumsalStatikSayfalar::tumu())
            ->map(function (array $sayfa): array {
                return [
                    'ad' => (string) ($sayfa['ad'] ?? ''),
                    'slug' => (string) ($sayfa['slug'] ?? ''),
                    'aktif' => (bool) ($sayfa['aktif'] ?? false),
                ];
            })
            ->all();
    }
}
