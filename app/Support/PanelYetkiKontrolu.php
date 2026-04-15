<?php

namespace App\Support;

trait PanelYetkiKontrolu
{
    protected static function izinVarMi(string $izin): bool
    {
        return auth()->check() && auth()->user()->can($izin);
    }

    protected static function izinlerdenBiriVarMi(array $izinler): bool
    {
        return auth()->check() && auth()->user()->hasAnyPermission($izinler);
    }
}