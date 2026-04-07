<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_ekayit_index_sayfasi_basarili_doner(): void
    {
        $response = $this->get(route('ekayit.index'));

        $response->assertOk();
    }
}
