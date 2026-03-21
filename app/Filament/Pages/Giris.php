<?php

namespace App\Filament\Pages;

use Filament\Pages\Auth\Login;

class Giris extends Login
{
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'eposta' => $data['email'],
            'password' => $data['password'],
        ];
    }
}
