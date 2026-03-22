<?php

namespace Tests\Unit;

use App\Data\TurkiyeIlceler;
use App\Data\TurkiyeIller;
use Tests\TestCase;

class TurkiyeVeriTest extends TestCase
{
    public function test_il_listesi_81_kayit_dondurur(): void
    {
        $this->assertCount(81, TurkiyeIller::tumu());
    }

    public function test_ile_gore_ilce_listesi_dondurur(): void
    {
        $ilceler = TurkiyeIlceler::ilceSecenekleri('İzmir');

        $this->assertArrayHasKey('Konak', $ilceler);
        $this->assertArrayHasKey('Bornova', $ilceler);
    }
}