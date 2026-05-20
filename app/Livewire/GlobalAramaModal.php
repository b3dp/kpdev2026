<?php

namespace App\Livewire;

use App\Filament\Resources\BagisResource;
use App\Filament\Resources\EkayitKayitResource;
use App\Filament\Resources\EtkinlikResource;
use App\Filament\Resources\HaberResource;
use App\Filament\Resources\KisiResource;
use App\Filament\Resources\KurumResource;
use App\Filament\Resources\KurumsalSayfaResource;
use App\Filament\Resources\MezunProfilResource;
use App\Filament\Resources\UyeResource;
use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\Kisi;
use App\Models\Kurum;
use App\Models\KurumsalSayfa;
use App\Models\Uye;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class GlobalAramaModal extends Component
{
    public bool $acik = false;

    public string $arama = '';

    public int $toplamSonuc = 0;

    public array $sonuclar = [
        'kisiler'           => [],
        'uyeler'            => [],
        'haberler'          => [],
        'ekayit_kayitlar'   => [],
        'bagislar'          => [],
        'mezunlar'          => [],
        'etkinlikler'       => [],
        'kurumlar'          => [],
        'kurumsal_sayfalar' => [],
    ];

    #[On('aramaModalAc')]
    public function ac(): void
    {
        $this->acik = true;
        $this->arama = '';
        $this->toplamSonuc = 0;
        $this->sonuclar = [
            'kisiler'           => [],
            'uyeler'            => [],
            'haberler'          => [],
            'ekayit_kayitlar'   => [],
            'bagislar'          => [],
            'mezunlar'          => [],
            'etkinlikler'       => [],
            'kurumlar'          => [],
            'kurumsal_sayfalar' => [],
        ];
    }

    public function kapat(): void
    {
        $this->acik = false;
    }

    public function updatedArama(): void
    {
        $this->ara();
    }

    public function ara(): void
    {
        $kelime = trim($this->arama);

        $bos = [
            'kisiler'           => [],
            'uyeler'            => [],
            'haberler'          => [],
            'ekayit_kayitlar'   => [],
            'bagislar'          => [],
            'mezunlar'          => [],
            'etkinlikler'       => [],
            'kurumlar'          => [],
            'kurumsal_sayfalar' => [],
        ];

        if (mb_strlen($kelime, 'UTF-8') < 2) {
            $this->sonuclar = $bos;
            $this->toplamSonuc = 0;
            return;
        }

        $like = '%'.$kelime.'%';

        // Kişiler — Scout
        try {
            $kisiler = Kisi::search($kelime)->take(5)->get();
            $this->sonuclar['kisiler'] = $kisiler->map(fn (Kisi $k) => [
                'id'     => $k->id,
                'baslik' => $k->full_ad,
                'ozet'   => collect([$k->telefon, $k->eposta, $k->meslek])->filter()->implode(' · '),
                'link'   => KisiResource::getUrl('edit', ['record' => $k]),
            ])->all();
        } catch (\Throwable $e) {
            Log::error('AramaModal kisi: '.$e->getMessage());
            $this->sonuclar['kisiler'] = [];
        }

        // Üyeler — DB LIKE
        try {
            $uyeler = Uye::where(fn ($q) => $q
                ->where('ad_soyad', 'LIKE', $like)
                ->orWhere('telefon', 'LIKE', $like)
                ->orWhere('eposta', 'LIKE', $like)
            )->limit(5)->get();

            $this->sonuclar['uyeler'] = $uyeler->map(fn (Uye $u) => [
                'id'     => $u->id,
                'baslik' => $u->ad_soyad ?? $u->telefon,
                'ozet'   => collect([$u->telefon, $u->eposta])->filter()->implode(' · '),
                'link'   => UyeResource::getUrl('edit', ['record' => $u]),
            ])->all();
        } catch (\Throwable $e) {
            Log::error('AramaModal uye: '.$e->getMessage());
            $this->sonuclar['uyeler'] = [];
        }

        // Haberler — Scout
        try {
            $haberler = Haber::search($kelime)->take(5)->get();
            $this->sonuclar['haberler'] = $haberler->map(fn (Haber $h) => [
                'id'     => $h->id,
                'baslik' => $h->baslik,
                'ozet'   => $h->ozet,
                'link'   => HaberResource::getUrl('edit', ['record' => $h]),
            ])->all();
        } catch (\Throwable $e) {
            Log::error('AramaModal haber: '.$e->getMessage());
            $this->sonuclar['haberler'] = [];
        }

        // E-Kayıt — DB join (öğrenci adı, veli adı/telefon)
        try {
            $ekayitler = DB::table('ekayit_kayitlar as ek')
                ->join('ekayit_ogrenci_bilgileri as ob', 'ek.id', '=', 'ob.kayit_id')
                ->join('ekayit_veli_bilgileri as vb', 'ek.id', '=', 'vb.kayit_id')
                ->whereNull('ek.deleted_at')
                ->where(fn ($q) => $q
                    ->where('ob.ad_soyad', 'LIKE', $like)
                    ->orWhere('vb.ad_soyad', 'LIKE', $like)
                    ->orWhere('vb.telefon_1', 'LIKE', $like)
                    ->orWhere('vb.telefon_2', 'LIKE', $like)
                    ->orWhere('ob.telefon', 'LIKE', $like)
                )
                ->select('ek.id', 'ob.ad_soyad as ogrenci_ad', 'vb.ad_soyad as veli_ad', 'vb.telefon_1 as veli_tel')
                ->limit(5)
                ->get();

            $this->sonuclar['ekayit_kayitlar'] = $ekayitler->map(fn ($r) => [
                'id'     => $r->id,
                'baslik' => $r->ogrenci_ad ?? '—',
                'ozet'   => 'Veli: '.($r->veli_ad ?? '—').' · '.($r->veli_tel ?? ''),
                'link'   => EkayitKayitResource::getUrl('edit', ['record' => $r->id]),
            ])->all();
        } catch (\Throwable $e) {
            Log::error('AramaModal ekayit: '.$e->getMessage());
            $this->sonuclar['ekayit_kayitlar'] = [];
        }

        // Bağışlar — DB join
        try {
            $bagislar = DB::table('bagislar as b')
                ->leftJoin('uyeler as u', 'b.uye_id', '=', 'u.id')
                ->leftJoin('kisiler as k', 'b.kisi_id', '=', 'k.id')
                ->where(fn ($q) => $q
                    ->where('b.bagis_no', 'LIKE', $like)
                    ->orWhere('u.ad_soyad', 'LIKE', $like)
                    ->orWhere('u.telefon', 'LIKE', $like)
                    ->orWhere('u.eposta', 'LIKE', $like)
                    ->orWhereRaw("CONCAT(COALESCE(k.ad,''), ' ', COALESCE(k.soyad,'')) LIKE ?", [$like])
                    ->orWhere('k.telefon', 'LIKE', $like)
                )
                ->select('b.id', 'b.bagis_no', 'b.toplam_tutar', 'u.ad_soyad as uye_ad', 'u.telefon as uye_tel')
                ->limit(5)
                ->get();

            $this->sonuclar['bagislar'] = $bagislar->map(fn ($r) => [
                'id'     => $r->id,
                'baslik' => ($r->uye_ad ?? '—').' — '.$r->bagis_no,
                'ozet'   => collect([
                    $r->uye_tel ? 'Tel: '.$r->uye_tel : null,
                    $r->toplam_tutar ? '₺'.number_format($r->toplam_tutar, 2, ',', '.') : null,
                ])->filter()->implode(' · '),
                'link'   => BagisResource::getUrl('edit', ['record' => $r->id]),
            ])->all();
        } catch (\Throwable $e) {
            Log::error('AramaModal bagis: '.$e->getMessage());
            $this->sonuclar['bagislar'] = [];
        }

        // Mezunlar — DB join
        try {
            $mezunlar = DB::table('mezun_profiller as mp')
                ->leftJoin('uyeler as u', 'mp.uye_id', '=', 'u.id')
                ->whereNull('mp.deleted_at')
                ->where(fn ($q) => $q
                    ->where('u.ad_soyad', 'LIKE', $like)
                    ->orWhere('u.telefon', 'LIKE', $like)
                    ->orWhere('u.eposta', 'LIKE', $like)
                    ->orWhere('mp.meslek', 'LIKE', $like)
                    ->orWhere('mp.gorev_il', 'LIKE', $like)
                )
                ->select('mp.id', 'u.ad_soyad as ad', 'u.telefon', 'mp.meslek', 'mp.mezuniyet_yili')
                ->limit(5)
                ->get();

            $this->sonuclar['mezunlar'] = $mezunlar->map(fn ($r) => [
                'id'     => $r->id,
                'baslik' => $r->ad ?? '—',
                'ozet'   => collect([
                    $r->meslek,
                    $r->mezuniyet_yili ? 'Mezuniyet: '.$r->mezuniyet_yili : null,
                ])->filter()->implode(' · '),
                'link'   => MezunProfilResource::getUrl('edit', ['record' => $r->id]),
            ])->all();
        } catch (\Throwable $e) {
            Log::error('AramaModal mezun: '.$e->getMessage());
            $this->sonuclar['mezunlar'] = [];
        }

        // Etkinlikler — Scout
        try {
            $etkinlikler = Etkinlik::search($kelime)->take(5)->get();
            $this->sonuclar['etkinlikler'] = $etkinlikler->map(fn (Etkinlik $e) => [
                'id'     => $e->id,
                'baslik' => $e->baslik,
                'ozet'   => $e->ozet,
                'link'   => EtkinlikResource::getUrl('edit', ['record' => $e]),
            ])->all();
        } catch (\Throwable $e) {
            Log::error('AramaModal etkinlik: '.$e->getMessage());
            $this->sonuclar['etkinlikler'] = [];
        }

        // Kurumlar — Scout
        try {
            $kurumlar = Kurum::search($kelime)->take(5)->get();
            $this->sonuclar['kurumlar'] = $kurumlar->map(fn (Kurum $k) => [
                'id'     => $k->id,
                'baslik' => $k->ad,
                'ozet'   => $k->il,
                'link'   => KurumResource::getUrl('edit', ['record' => $k]),
            ])->all();
        } catch (\Throwable $e) {
            Log::error('AramaModal kurum: '.$e->getMessage());
            $this->sonuclar['kurumlar'] = [];
        }

        // Kurumsal Sayfalar — Scout
        try {
            $kurumsalSayfalar = KurumsalSayfa::search($kelime)->take(5)->get();
            $this->sonuclar['kurumsal_sayfalar'] = $kurumsalSayfalar->map(fn (KurumsalSayfa $s) => [
                'id'     => $s->id,
                'baslik' => $s->ad,
                'ozet'   => $s->ozet,
                'link'   => KurumsalSayfaResource::getUrl('edit', ['record' => $s]),
            ])->all();
        } catch (\Throwable $e) {
            Log::error('AramaModal kurumsalsayfa: '.$e->getMessage());
            $this->sonuclar['kurumsal_sayfalar'] = [];
        }

        $this->toplamSonuc = array_sum(array_map('count', $this->sonuclar));
    }

    public function render()
    {
        return view('livewire.global-arama-modal');
    }
}
