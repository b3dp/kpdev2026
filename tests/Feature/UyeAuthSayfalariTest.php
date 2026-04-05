<?php

namespace Tests\Feature;

use Tests\TestCase;

class UyeAuthSayfalariTest extends TestCase
{
    public function test_uye_kayit_form_route_tanimlidir(): void
    {
        $this->assertSame('/uye-kayit', route('uye.kayit.form', absolute: false));
    }

    public function test_giris_sayfasi_hataya_dusmeden_acilir(): void
    {
        $response = $this->get(route('uye.giris.form'));

        $response->assertOk();
        $response->assertSee('Giriş Yap');
        $response->assertSee('Kayıt Ol');
    }
}
