<?php

namespace App\Filament\Resources;

use Z3d0X\FilamentLogger\Resources\ActivityResource;

class LogResource extends ActivityResource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getNavigationGroup(): ?string
    {
        return 'Sistem';
    }

    public static function getNavigationLabel(): string
    {
        return 'Loglar';
    }

    public static function getLabel(): string
    {
        return 'Log';
    }

    public static function getPluralLabel(): string
    {
        return 'Loglar';
    }

    public static function canViewAny(): bool
    {
        return static::izinVarMi('loglar.listele');
    }
}
