<?php

namespace App\Http\Controllers;

use App\Data\TurkiyeIlceler;
use App\Data\TurkiyeIller;
use App\Enums\EkayitDurumu;
use App\Jobs\EkayitSmsJob;
use App\Models\EkayitDonem;
use App\Models\EkayitKayit;
use App\Models\EkayitKimlikBilgisi;
use App\Models\EkayitOgrenciBilgisi;
use App\Models\EkayitOkulBilgisi;
use App\Models\EkayitSinif;
use App\Models\EkayitVeliBilgisi;
use App\Models\Uye;
use App\Services\EkayitPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class EkayitController extends Controller
{
    public function index()
    {
        $aktifDonem = EkayitDonem::where('aktif', 1)->first();

        $siniflar = $aktifDonem
            ? $this->getirDonemSiniflari((int) $aktifDonem->id)
            : collect();

        $gruplar = $this->hazirlaSinifGruplari($siniflar);

        return view('pages.ekayit.index', compact('aktifDonem', 'siniflar', 'gruplar'));
    }

    protected function getirDonemSiniflari(int $donemId): Collection
    {
        $sorgu = EkayitSinif::query()
            ->where('donem_id', $donemId)
            ->where('aktif', true);

        if (Schema::hasColumn('ekayit_siniflar', 'sira')) {
            $sorgu->orderBy('sira');
        } elseif (Schema::hasColumn('ekayit_siniflar', 'sinif_no')) {
            $sorgu->orderBy('sinif_no');
        } else {
            $sorgu->orderBy('ad');
        }

        return $sorgu->get();
    }

    protected function hazirlaSinifGruplari(Collection $siniflar): Collection
    {
        $turSirasi = [
            'ilkokul' => 'İlkokul',
            'ortaokul' => 'Ortaokul',
            'lise' => 'Lise',
            'universite' => 'Üniversite',
            'diger' => 'Sınıf Seçenekleri',
        ];

        $hazirSiniflar = $siniflar
            ->map(function (EkayitSinif $sinif) use ($turSirasi) {
                $grupKey = $this->sinifGrupAnahtari($sinif);
                preg_match('/\d+/', (string) ($sinif->ad ?? ''), $eslesme);

                $sinifNo = $sinif->sinif_no ?? ($eslesme[0] ?? null);

                return [
                    'id' => $sinif->id,
                    'kart_baslik' => $sinifNo ?: Str::upper(Str::substr((string) $sinif->ad, 0, 10)),
                    'kart_alt_baslik' => $sinifNo ? 'Sınıf' : 'Başvuru',
                    'kart_rozet' => $sinif->tur_etiket ?? ($turSirasi[$grupKey] ?? 'Sınıf'),
                    'kart_ad' => $sinif->ad,
                    'grup_key' => $grupKey,
                    'siralama' => $sinifNo ? (int) $sinifNo : PHP_INT_MAX,
                ];
            })
            ->groupBy('grup_key');

        return collect($turSirasi)
            ->filter(fn (string $ad, string $key) => $hazirSiniflar->has($key))
            ->map(fn (string $ad, string $key) => [
                'anahtar' => $key,
                'ad' => $ad,
                'siniflar' => collect($hazirSiniflar->get($key, []))->sortBy('siralama')->values(),
            ])
            ->values();
    }

    protected function sinifGrupAnahtari(EkayitSinif $sinif): string
    {
        if (filled($sinif->tur ?? null)) {
            return (string) $sinif->tur;
        }

        $ad = mb_strtolower((string) ($sinif->ad ?? ''), 'UTF-8');

        return match (true) {
            str_contains($ad, 'ilkokul') => 'ilkokul',
            str_contains($ad, 'ortaokul') => 'ortaokul',
            str_contains($ad, 'lise') => 'lise',
            str_contains($ad, 'üniversite'), str_contains($ad, 'universite') => 'universite',
            default => 'diger',
        };
    }

    public function form(Request $request)
    {
        $sinifId = $request->query('sinif_id');
        $sinif = null;

        if ($sinifId) {
            $sinif = EkayitSinif::find($sinifId);
        }

        $aktifDonem = EkayitDonem::where('aktif', 1)->first();

        if (! $aktifDonem) {
            return redirect()->route('ekayit.index')->with('error', 'Aktif kayıt dönemi bulunamadı.');
        }

        if ($sinif && (int) $sinif->donem_id !== (int) $aktifDonem->id) {
            return redirect()->route('ekayit.index')->with('error', 'Seçilen sınıf aktif kayıt dönemine ait değil.');
        }

        $sinifSecenekleri = $this->getirDonemSiniflari((int) $aktifDonem->id);
        $iller = TurkiyeIller::secenekler();
        $ogrenciIlceleri = TurkiyeIlceler::ilceSecenekleri((string) old('ogrenci_ikamet_il'));
        $kimlikIlceleri = TurkiyeIlceler::ilceSecenekleri((string) old('kimlik_kayitli_il'));
        $veliIlceleri = TurkiyeIlceler::ilceSecenekleri((string) old('veli_il'));
        $okulIlceleri = TurkiyeIlceler::ilceSecenekleri((string) old('okul_il'));
        $ilceler_haritasi = TurkiyeIlceler::tumu();

        return view('pages.ekayit.form', compact(
            'sinif',
            'aktifDonem',
            'sinifSecenekleri',
            'iller',
            'ogrenciIlceleri',
            'kimlikIlceleri',
            'veliIlceleri',
            'okulIlceleri',
            'ilceler_haritasi'
        ));
    }

    public function store(Request $request)
    {
        try {
            $aktifDonem = EkayitDonem::where('aktif', 1)->first();

            if (! $aktifDonem) {
                return redirect()->route('ekayit.index')->with('error', 'Aktif kayıt dönemi bulunamadı.');
            }

            $metinAlanlar = [
                'ogrenci_ad', 'ogrenci_soyad', 'ogrenci_dogum_yeri', 'ogrenci_baba_adi', 'ogrenci_anne_adi',
                'ogrenci_adres', 'kimlik_kayitli_mahalle_koy', 'veli_ad_soyad', 'veli_adres', 'okul_adi', 'otp_kodu',
            ];

            foreach ($metinAlanlar as $alan) {
                if ($request->has($alan) && filled($request->input($alan))) {
                    $request->merge([$alan => $this->metniBuyukHarfYap($request->input($alan))]);
                }
            }

            $request->merge([
                'ogrenci_tc' => preg_replace('/\D+/', '', (string) $request->input('ogrenci_tc')),
                'ogrenci_telefon' => $this->telefonuTemizle($request->input('ogrenci_telefon')),
                'ogrenci_eposta' => filled($request->input('ogrenci_eposta'))
                    ? mb_strtolower(trim((string) $request->input('ogrenci_eposta')), 'UTF-8')
                    : null,
                'kimlik_cilt_no' => preg_replace('/\D+/', '', (string) $request->input('kimlik_cilt_no')),
                'kimlik_aile_sira_no' => preg_replace('/\D+/', '', (string) $request->input('kimlik_aile_sira_no')),
                'kimlik_sira_no' => preg_replace('/\D+/', '', (string) $request->input('kimlik_sira_no')),
                'kimlik_kan_grubu' => filled($request->input('kimlik_kan_grubu'))
                    ? $this->metniBuyukHarfYap($request->input('kimlik_kan_grubu'))
                    : null,
                'veli_telefon' => $this->telefonuTemizle($request->input('veli_telefon')),
                'veli_telefon_2' => $this->telefonuTemizle($request->input('veli_telefon_2')),
                'veli_eposta' => filled($request->input('veli_eposta'))
                    ? mb_strtolower(trim((string) $request->input('veli_eposta')), 'UTF-8')
                    : null,
                'okul_numarasi' => filled($request->input('okul_numarasi'))
                    ? trim((string) $request->input('okul_numarasi'))
                    : null,
            ]);

            $veri = $request->validate([
                'sinif_id' => ['required', 'integer', 'exists:ekayit_siniflar,id'],
                'donem_id' => ['nullable', 'integer'],
                'ogrenci_ad' => ['required', 'string', 'max:100', 'regex:/^[A-ZÇĞİÖŞÜa-zçğıöşü\s]+$/u'],
                'ogrenci_soyad' => ['required', 'string', 'max:100', 'regex:/^[A-ZÇĞİÖŞÜa-zçğıöşü\s]+$/u'],
                'ogrenci_tc' => ['required', 'digits:11'],
                'ogrenci_telefon' => ['required', 'string', 'min:10', 'max:20'],
                'ogrenci_eposta' => ['required', 'email', 'max:255'],
                'ogrenci_dogum_tarihi' => ['required', 'date'],
                'ogrenci_dogum_yeri' => ['nullable', 'string', 'max:255'],
                'ogrenci_baba_adi' => ['nullable', 'string', 'max:255'],
                'ogrenci_anne_adi' => ['nullable', 'string', 'max:255'],
                'ogrenci_adres' => ['nullable', 'string', 'max:1000'],
                'ogrenci_ikamet_il' => ['nullable', 'string', 'max:100', 'required_with:ogrenci_ikamet_ilce'],
                'ogrenci_ikamet_ilce' => ['nullable', 'string', 'max:100', 'required_with:ogrenci_ikamet_il'],
                'eski_tip_kimlik_var' => ['nullable', 'boolean'],
                'kimlik_kayitli_il' => ['nullable', 'string', 'max:100', 'required_with:eski_tip_kimlik_var'],
                'kimlik_kayitli_ilce' => ['nullable', 'string', 'max:100', 'required_with:eski_tip_kimlik_var'],
                'kimlik_kayitli_mahalle_koy' => ['nullable', 'string', 'max:255', 'required_with:eski_tip_kimlik_var'],
                'kimlik_cilt_no' => ['nullable', 'string', 'max:50', 'required_with:eski_tip_kimlik_var'],
                'kimlik_aile_sira_no' => ['nullable', 'string', 'max:50', 'required_with:eski_tip_kimlik_var'],
                'kimlik_sira_no' => ['nullable', 'string', 'max:50', 'required_with:eski_tip_kimlik_var'],
                'kimlik_cuzdanin_verildigi_yer' => ['nullable', 'string', 'max:255'],
                'kimlik_seri_no' => ['nullable', 'string', 'max:50'],
                'kimlik_kan_grubu' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,0+,0-'],
                'ogrenci_cinsiyet' => ['required', 'in:E,K'],
                'veli_ad_soyad' => ['required', 'string', 'max:255', 'regex:/^[A-ZÇĞİÖŞÜa-zçğıöşü\s]+$/u'],
                'veli_telefon' => ['required', 'string', 'min:10', 'max:20'],
                'veli_telefon_2' => ['nullable', 'string', 'min:10', 'max:20'],
                'veli_eposta' => ['required', 'email', 'max:255'],
                'veli_il' => ['nullable', 'string', 'max:100'],
                'veli_ilce' => ['nullable', 'string', 'max:100', 'required_with:veli_il'],
                'veli_adres' => ['nullable', 'string', 'max:1000'],
                'okul_adi' => ['required', 'string', 'max:255'],
                'okul_numarasi' => ['nullable', 'string', 'max:50'],
                'okul_il' => ['required', 'string', 'max:100'],
                'okul_ilce' => ['required', 'string', 'max:100'],
                'okul_turu' => ['nullable', 'in:devlet,ozel,imam-hatip'],
                'not_ortalamasi' => ['nullable', 'numeric', 'between:0,100'],
                'otp_kodu' => ['nullable', 'digits:6'],
                'onay_bilgi' => ['accepted'],
                'onay_kvkk' => ['accepted'],
                'onay_iletisim' => ['accepted'],
                'onay_tuzuk' => ['accepted'],
            ], [
                'onay_bilgi.accepted' => 'Bilgi doğruluğu onayı gereklidir.',
                'onay_kvkk.accepted' => 'KVKK onayı gereklidir.',
                'onay_iletisim.accepted' => 'İletişim izni onayı gereklidir.',
                'onay_tuzuk.accepted' => 'Dernek tüzüğü onayı gereklidir.',
            ]);

            $sinif = EkayitSinif::query()
                ->whereKey($veri['sinif_id'])
                ->where('donem_id', $aktifDonem->id)
                ->where('aktif', true)
                ->first();

            if (! $sinif) {
                throw ValidationException::withMessages([
                    'sinif_id' => 'Geçerli ve aktif bir sınıf seçiniz.',
                ]);
            }

            $mevcutKayit = EkayitKayit::withTrashed()
                ->whereHas('ogrenciBilgisi', fn ($query) => $query->where('tc_kimlik', $veri['ogrenci_tc']))
                ->whereHas('sinif', fn ($query) => $query->where('donem_id', $aktifDonem->id))
                ->first();

            if ($mevcutKayit) {
                throw ValidationException::withMessages([
                    'ogrenci_tc' => 'Bu TC kimlik numarasıyla bu dönem için zaten başvuru bulunmaktadır.',
                ]);
            }

            $uye = Auth::guard('uye')->user();

            if (! $uye) {
                $uye = Uye::query()
                    ->where('telefon', $veri['veli_telefon'])
                    ->orWhere('eposta', $veri['veli_eposta'])
                    ->first();
            }

            $ekNotlar = collect([
                'Cinsiyet: '.($veri['ogrenci_cinsiyet'] === 'E' ? 'ERKEK' : 'KIZ'),
                filled($veri['veli_il'] ?? null) || filled($veri['veli_ilce'] ?? null)
                    ? 'Veli Konumu: '.collect([$veri['veli_il'] ?? null, $veri['veli_ilce'] ?? null])->filter()->implode(' / ')
                    : null,
                filled($veri['veli_adres'] ?? null) ? 'Veli Adresi: '.$veri['veli_adres'] : null,
                filled($veri['okul_turu'] ?? null) ? 'Okul Türü: '.mb_strtoupper((string) $veri['okul_turu'], 'UTF-8') : null,
                filled($veri['not_ortalamasi'] ?? null) ? 'Not Ortalaması: '.$veri['not_ortalamasi'] : null,
                filled($veri['otp_kodu'] ?? null) ? 'Ön Doğrulama Kodu: '.$veri['otp_kodu'] : null,
            ])->filter()->implode(PHP_EOL);

            $kayit = EkayitKayit::create([
                'sinif_id' => $sinif->id,
                'uye_id' => $uye?->id,
                'durum' => EkayitDurumu::Beklemede->value,
                'genel_not' => $ekNotlar !== '' ? $ekNotlar : null,
            ]);

            EkayitOgrenciBilgisi::create([
                'kayit_id' => $kayit->id,
                'ad_soyad' => trim($veri['ogrenci_ad'].' '.$veri['ogrenci_soyad']),
                'tc_kimlik' => $veri['ogrenci_tc'],
                'telefon' => $veri['ogrenci_telefon'],
                'eposta' => $veri['ogrenci_eposta'],
                'dogum_yeri' => $veri['ogrenci_dogum_yeri'] ?? null,
                'dogum_tarihi' => $veri['ogrenci_dogum_tarihi'],
                'baba_adi' => $veri['ogrenci_baba_adi'] ?? null,
                'anne_adi' => $veri['ogrenci_anne_adi'] ?? null,
                'adres' => $veri['ogrenci_adres'] ?? null,
                'ikamet_il' => $veri['ogrenci_ikamet_il'] ?? null,
                'ikamet_ilce' => $veri['ogrenci_ikamet_ilce'] ?? null,
            ]);

            $eskiTipKimlikVar = (bool) ($veri['eski_tip_kimlik_var'] ?? false);

            EkayitKimlikBilgisi::create([
                'kayit_id' => $kayit->id,
                'kayitli_il' => $eskiTipKimlikVar ? ($veri['kimlik_kayitli_il'] ?? null) : null,
                'kayitli_ilce' => $eskiTipKimlikVar ? ($veri['kimlik_kayitli_ilce'] ?? null) : null,
                'kayitli_mahalle_koy' => $eskiTipKimlikVar ? ($veri['kimlik_kayitli_mahalle_koy'] ?? null) : null,
                'cilt_no' => $eskiTipKimlikVar ? ($veri['kimlik_cilt_no'] ?? null) : null,
                'aile_sira_no' => $eskiTipKimlikVar ? ($veri['kimlik_aile_sira_no'] ?? null) : null,
                'sira_no' => $eskiTipKimlikVar ? ($veri['kimlik_sira_no'] ?? null) : null,
                'cuzdanin_verildigi_yer' => $veri['kimlik_cuzdanin_verildigi_yer'] ?? null,
                'kimlik_seri_no' => $veri['kimlik_seri_no'] ?? null,
                'kan_grubu' => $veri['kimlik_kan_grubu'] ?? null,
            ]);

            $okulNotu = collect([
                filled($veri['okul_turu'] ?? null) ? 'Okul Türü: '.mb_strtoupper((string) $veri['okul_turu'], 'UTF-8') : null,
                filled($veri['okul_il'] ?? null) || filled($veri['okul_ilce'] ?? null)
                    ? 'Okul Konumu: '.collect([$veri['okul_il'] ?? null, $veri['okul_ilce'] ?? null])->filter()->implode(' / ')
                    : null,
                filled($veri['not_ortalamasi'] ?? null) ? 'Not Ortalaması: '.$veri['not_ortalamasi'] : null,
            ])->filter()->implode(' | ');

            EkayitOkulBilgisi::create([
                'kayit_id' => $kayit->id,
                'okul_adi' => $veri['okul_adi'],
                'okul_numarasi' => $veri['okul_numarasi'] ?? null,
                'not' => $okulNotu !== '' ? $okulNotu : null,
            ]);

            EkayitVeliBilgisi::create([
                'kayit_id' => $kayit->id,
                'ad_soyad' => $veri['veli_ad_soyad'],
                'eposta' => $veri['veli_eposta'],
                'telefon_1' => $veri['veli_telefon'],
                'telefon_2' => $veri['veli_telefon_2'] ?? null,
                'adres' => $veri['veli_adres'] ?? null,
                'ikamet_il' => $veri['veli_il'] ?? null,
                'ikamet_ilce' => $veri['veli_ilce'] ?? null,
            ]);

            if (filled($veri['veli_telefon'])) {
                try {
                    dispatch(new EkayitSmsJob(
                        $kayit->id,
                        'basvuru_alindi',
                        $veri['veli_telefon'],
                        false,
                        1
                    ));
                } catch (Throwable $e) {
                    Log::warning('E-Kayıt başvuru SMS kuyruğa alınamadı', [
                        'kayit_id' => $kayit->id,
                        'mesaj' => $e->getMessage(),
                    ]);
                }
            }

            return redirect()->route('ekayit.tesekkur')
                ->with('son_ekayit_id', $kayit->id);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('E-Kayıt başvurusu kaydedilemedi', [
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
                'ip' => $request->ip(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Başvurunuz kaydedilirken beklenmeyen bir sorun oluştu. Lütfen tekrar deneyin.');
        }
    }

    public function evrakIndir(Request $request, EkayitKayit $kayit)
    {
        $uyeId = Auth::guard('uye')->id();
        $erisimeIzinVar = $request->hasValidSignature()
            || Auth::guard('admin')->check()
            || ($uyeId && (int) $kayit->uye_id === (int) $uyeId);

        abort_unless($erisimeIzinVar, 403);

        $sonuc = app(EkayitPdfService::class)->olustur($kayit);

        if (! $sonuc) {
            return redirect()->route('ekayit.index')
                ->with('error', 'PDF hazırlanırken bir sorun oluştu. Lütfen tekrar deneyin.');
        }

        return response()->streamDownload(function () use ($sonuc): void {
            echo $sonuc['icerik'];
        }, $sonuc['indirme_dosya_adi'], [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function tesekkur()
    {
        $kayitId = session('son_ekayit_id');

        if (! $kayitId) {
            return redirect()->route('ekayit.index')
                ->with('info', 'Başvuru bilgisi bulunamadı.');
        }

        $kayit = EkayitKayit::with(['sinif', 'ogrenciBilgisi', 'veliBilgisi'])->find($kayitId);

        if (! $kayit) {
            return redirect()->route('ekayit.index')
                ->with('info', 'Başvuru bilgisi bulunamadı.');
        }

        return view('pages.ekayit.tesekkur', compact('kayit'));
    }

    protected function metniBuyukHarfYap(?string $metin): ?string
    {
        $temizMetin = trim((string) $metin);

        if ($temizMetin === '') {
            return null;
        }

        return mb_strtoupper(strtr($temizMetin, ['i' => 'İ', 'ı' => 'I']), 'UTF-8');
    }

    protected function telefonuTemizle(?string $telefon): ?string
    {
        $temizTelefon = preg_replace('/\D+/', '', (string) $telefon) ?: '';

        if ($temizTelefon === '') {
            return null;
        }

        if (str_starts_with($temizTelefon, '0090')) {
            $temizTelefon = substr($temizTelefon, 4);
        } elseif (str_starts_with($temizTelefon, '90')) {
            $temizTelefon = substr($temizTelefon, 2);
        }

        if ($temizTelefon === '') {
            return null;
        }

        return str_starts_with($temizTelefon, '0') ? $temizTelefon : ('0'.$temizTelefon);
    }
}
