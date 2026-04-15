<?php

namespace Tests\Feature;

use App\Exports\EkayitExport;
use App\Models\EkayitDonem;
use App\Models\EkayitKayit;
use App\Models\EkayitKimlikBilgisi;
use App\Models\EkayitOgrenciBilgisi;
use App\Models\EkayitOkulBilgisi;
use App\Models\EkayitSinif;
use App\Models\EkayitVeliBilgisi;
use App\Models\Kurum;
use App\Models\Uye;
use App\Services\EkayitPdfService;
use App\Services\ZeptomailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class EkayitPdfTest extends TestCase
{
    use RefreshDatabase;

    private ?string $spaces_test_disk_koku = null;

    protected function tearDown(): void
    {
        if (filled($this->spaces_test_disk_koku) && File::isDirectory($this->spaces_test_disk_koku)) {
            File::deleteDirectory($this->spaces_test_disk_koku);
        }

        $this->spaces_test_disk_koku = null;

        parent::tearDown();
    }

    public function test_public_ekayit_basvurusu_ayni_veli_numaralarina_izin_vermez(): void
    {
        $kurum = Kurum::query()->create([
            'ad' => 'Hatay Kursu',
            'slug' => 'hatay-kursu-ayni-numara',
            'tip' => 'kurs',
            'aktif' => true,
        ]);

        $donem = EkayitDonem::query()->create([
            'ad' => '2026-2027 Kayıt Dönemi',
            'ogretim_yili' => '2026-2027',
            'baslangic' => now()->subDay(),
            'bitis' => now()->addMonth(),
            'aktif' => true,
        ]);

        $sinif = EkayitSinif::query()->create([
            'ad' => '7. Sınıf Hafızlık',
            'ogretim_yili' => '2026-2027',
            'kurum_id' => $kurum->id,
            'donem_id' => $donem->id,
            'renk' => 'blue',
            'aktif' => true,
        ]);

        $response = $this->from(route('ekayit.form', ['sinif_id' => $sinif->id]))
            ->post(route('ekayit.store'), [
                'sinif_id' => $sinif->id,
                'donem_id' => $donem->id,
                'ogrenci_ad' => 'Ahmet',
                'ogrenci_soyad' => 'Yılmaz',
                'ogrenci_tc' => '12345678901',
                'ogrenci_telefon' => '0555 111 22 33',
                'ogrenci_eposta' => 'ogrenci@example.com',
                'ogrenci_dogum_tarihi' => '2012-01-15',
                'ogrenci_adres' => 'Öğrenci adresi',
                'ogrenci_ikamet_il' => 'İzmir',
                'ogrenci_ikamet_ilce' => 'Konak',
                'ogrenci_cinsiyet' => 'E',
                'veli_ad_soyad' => 'Fatma Yılmaz',
                'veli_telefon_sahibi_1' => 'anne',
                'veli_telefon' => '0555 444 55 66',
                'veli_telefon_sahibi_2' => 'baba',
                'veli_telefon_2' => '0555 444 55 66',
                'veli_eposta' => 'veli@example.com',
                'veli_il' => 'İzmir',
                'veli_ilce' => 'Konak',
                'veli_adres' => 'Veli adresi',
                'okul_adi' => 'Şehitler Ortaokulu',
                'okul_il' => 'İzmir',
                'okul_ilce' => 'Konak',
                'onay_bilgi' => '1',
                'onay_kvkk' => '1',
                'onay_iletisim' => '1',
                'onay_tuzuk' => '1',
            ]);

        $response->assertRedirect(route('ekayit.form', ['sinif_id' => $sinif->id]));
        $response->assertSessionHasErrors('veli_telefon_2');
    }

    public function test_public_ekayit_basvurusu_ogrenci_ve_veli_adres_bilgileri_zorunludur(): void
    {
        $kurum = Kurum::query()->create([
            'ad' => 'Hatay Kursu',
            'slug' => 'hatay-kursu-zorunlu-alanlar',
            'tip' => 'kurs',
            'aktif' => true,
        ]);

        $donem = EkayitDonem::query()->create([
            'ad' => '2026-2027 Kayıt Dönemi',
            'ogretim_yili' => '2026-2027',
            'baslangic' => now()->subDay(),
            'bitis' => now()->addMonth(),
            'aktif' => true,
        ]);

        $sinif = EkayitSinif::query()->create([
            'ad' => '6. Sınıf',
            'ogretim_yili' => '2026-2027',
            'kurum_id' => $kurum->id,
            'donem_id' => $donem->id,
            'renk' => 'blue',
            'aktif' => true,
        ]);

        $response = $this->from(route('ekayit.form', ['sinif_id' => $sinif->id]))
            ->post(route('ekayit.store'), [
                'sinif_id' => $sinif->id,
                'donem_id' => $donem->id,
                'ogrenci_ad' => 'Ahmet',
                'ogrenci_soyad' => 'Yılmaz',
                'ogrenci_tc' => '12345678901',
                'ogrenci_telefon' => '05551112233',
                'ogrenci_eposta' => 'ogrenci@example.com',
                'ogrenci_dogum_tarihi' => '2012-01-15',
                'ogrenci_cinsiyet' => 'E',
                'veli_ad_soyad' => 'Fatma Yılmaz',
                'veli_telefon_sahibi_1' => 'anne',
                'veli_telefon' => '05554445566',
                'veli_eposta' => 'veli@example.com',
                'okul_adi' => 'Şehitler Ortaokulu',
                'okul_il' => 'İzmir',
                'okul_ilce' => 'Konak',
                'onay_bilgi' => '1',
                'onay_kvkk' => '1',
                'onay_iletisim' => '1',
                'onay_tuzuk' => '1',
            ]);

        $response->assertRedirect(route('ekayit.form', ['sinif_id' => $sinif->id]));
        $response->assertSessionHasErrors([
            'ogrenci_adres',
            'ogrenci_ikamet_il',
            'ogrenci_ikamet_ilce',
            'veli_il',
            'veli_ilce',
            'veli_adres',
        ]);
    }

    public function test_public_ekayit_basvurusu_yeni_veli_ve_okul_alanlarini_kaydeder_ve_bildirim_epostasi_gonderir(): void
    {
        $kurum = Kurum::query()->create([
            'ad' => 'Hatay Kursu',
            'slug' => 'hatay-kursu',
            'tip' => 'kurs',
            'aktif' => true,
        ]);

        $donem = EkayitDonem::query()->create([
            'ad' => '2026-2027 Kayıt Dönemi',
            'ogretim_yili' => '2026-2027',
            'baslangic' => now()->subDay(),
            'bitis' => now()->addMonth(),
            'aktif' => true,
        ]);

        $sinif = EkayitSinif::query()->create([
            'ad' => '8. Sınıf Hafızlık',
            'ogretim_yili' => '2026-2027',
            'kurum_id' => $kurum->id,
            'donem_id' => $donem->id,
            'renk' => 'blue',
            'aktif' => true,
        ]);

        $zeptomailService = \Mockery::mock(ZeptomailService::class);
        $zeptomailService->shouldReceive('ekayitDurumGonder')->once()->andReturnTrue();
        $this->app->instance(ZeptomailService::class, $zeptomailService);

        $response = $this->post(route('ekayit.store'), [
            'sinif_id' => $sinif->id,
            'donem_id' => $donem->id,
            'ogrenci_ad' => 'Ahmet',
            'ogrenci_soyad' => 'Yılmaz',
            'ogrenci_tc' => '12345678901',
            'ogrenci_telefon' => '0555 111 22 33',
            'ogrenci_eposta' => 'ogrenci@example.com',
            'ogrenci_dogum_tarihi' => '2012-01-15',
            'ogrenci_dogum_yeri' => 'İzmir',
            'ogrenci_baba_adi' => 'Mehmet',
            'ogrenci_anne_adi' => 'Ayşe',
            'ogrenci_adres' => 'Öğrenci adresi',
            'ogrenci_ikamet_il' => 'İzmir',
            'ogrenci_ikamet_ilce' => 'Karabağlar',
            'ogrenci_cinsiyet' => 'E',
            'veli_ad_soyad' => 'Fatma Yılmaz',
            'veli_telefon_sahibi_1' => 'anne',
            'veli_telefon' => '0555 444 55 66',
            'veli_telefon_sahibi_2' => 'baba',
            'veli_telefon_2' => '0555 777 88 99',
            'veli_eposta' => 'veli@example.com',
            'veli_il' => 'İzmir',
            'veli_ilce' => 'Konak',
            'veli_adres' => 'Veli açık adresi',
            'okul_adi' => 'Şehitler Ortaokulu',
            'okul_numarasi' => '456',
            'okul_il' => 'İzmir',
            'okul_ilce' => 'Konak',
            'okul_turu' => 'devlet',
            'onay_bilgi' => '1',
            'onay_kvkk' => '1',
            'onay_iletisim' => '1',
            'onay_tuzuk' => '1',
        ]);

        $response->assertRedirect(route('ekayit.tesekkur'));

        $kayit = EkayitKayit::query()->with(['veliBilgisi', 'okulBilgisi'])->firstOrFail();

        $this->assertSame('05554445566', preg_replace('/\D+/', '', (string) $kayit->veliBilgisi?->telefon_1));
        $this->assertSame('05557778899', preg_replace('/\D+/', '', (string) $kayit->veliBilgisi?->telefon_2));
        $this->assertSame('anne', $kayit->veliBilgisi?->telefon_1_sahibi);
        $this->assertSame('baba', $kayit->veliBilgisi?->telefon_2_sahibi);
        $this->assertSame('VELİ AÇIK ADRESİ', $kayit->veliBilgisi?->adres);
        $this->assertSame('İzmir', $kayit->veliBilgisi?->ikamet_il);
        $this->assertSame('Konak', $kayit->veliBilgisi?->ikamet_ilce);
        $this->assertSame('456', $kayit->okulBilgisi?->okul_numarasi);

        $this->assertDatabaseHas('uyeler', [
            'telefon' => '05554445566',
            'ad_soyad' => 'FATMA YILMAZ',
        ]);

        $this->assertDatabaseHas('uyeler', [
            'telefon' => '05557778899',
            'ad_soyad' => 'FATMA YILMAZ',
        ]);

        $tesekkurResponse = $this->withSession(['son_ekayit_id' => $kayit->id])
            ->get(route('ekayit.tesekkur'));

        $tesekkurResponse->assertOk();
        $tesekkurResponse->assertSee('Hatay Kursu', false);
        $tesekkurResponse->assertSee('baris@b3dp.com', false);
    }

    public function test_ekayit_pdf_servisi_ogrenci_adi_ve_kayit_no_ile_pdf_olusturur(): void
    {
        $this->hazirlaSpacesTestDiski();

        $kurum = Kurum::query()->create([
            'ad' => 'Hatay Kursu',
            'slug' => 'hatay-kursu-2',
            'tip' => 'kurs',
            'aktif' => true,
        ]);

        $donem = EkayitDonem::query()->create([
            'ad' => '2026-2027 Kayıt Dönemi',
            'ogretim_yili' => '2026-2027',
            'baslangic' => now()->subDay(),
            'bitis' => now()->addMonth(),
            'aktif' => true,
        ]);

        $sinif = EkayitSinif::query()->create([
            'ad' => 'Hazırlık Sınıfı',
            'ogretim_yili' => '2026-2027',
            'kurum_id' => $kurum->id,
            'donem_id' => $donem->id,
            'renk' => 'green',
            'aktif' => true,
        ]);

        $kayit = EkayitKayit::query()->create([
            'sinif_id' => $sinif->id,
            'durum' => 'onaylandi',
        ]);

        EkayitOgrenciBilgisi::query()->create([
            'kayit_id' => $kayit->id,
            'ad_soyad' => 'Ahmet Emin Günden',
            'tc_kimlik' => '12345678901',
            'telefon' => '05551112233',
            'eposta' => 'ogrenci@example.com',
            'dogum_yeri' => 'İzmir',
            'dogum_tarihi' => '2012-01-15',
            'baba_adi' => 'Mehmet',
            'anne_adi' => 'Ayşe',
            'adres' => 'Öğrenci adresi',
            'ikamet_il' => 'İzmir',
            'ikamet_ilce' => 'Karabağlar',
        ]);

        EkayitKimlikBilgisi::query()->create([
            'kayit_id' => $kayit->id,
            'kayitli_il' => 'İzmir',
            'kayitli_ilce' => 'Konak',
            'kayitli_mahalle_koy' => 'Mithatpaşa',
            'cilt_no' => '12',
            'aile_sira_no' => '34',
            'sira_no' => '56',
        ]);

        EkayitOkulBilgisi::query()->create([
            'kayit_id' => $kayit->id,
            'okul_adi' => 'Şehitler Ortaokulu',
            'okul_numarasi' => '456',
        ]);

        EkayitVeliBilgisi::query()->create([
            'kayit_id' => $kayit->id,
            'ad_soyad' => 'Fatma Günden',
            'eposta' => 'veli@example.com',
            'telefon_1' => '05554445566',
            'telefon_2' => '05557778899',
            'adres' => 'Veli adresi',
            'ikamet_il' => 'İzmir',
            'ikamet_ilce' => 'Konak',
        ]);

        $sonuc = app(EkayitPdfService::class)->olustur($kayit->fresh());

        $this->assertNotNull($sonuc);
        $this->assertSame('docx', $sonuc['sablon_tipi']);
        $this->assertSame('Ahmet Emin Günden - 0000'.$kayit->id.'.pdf', $sonuc['indirme_dosya_adi']);
        $this->assertStringEndsWith('.pdf', $sonuc['dosya_yol']);
        $this->assertStringContainsString('ahmet-emin-gunden', $sonuc['dosya_yol']);
        $this->assertStringEndsWith('.docx', $sonuc['docx_dosya_yol']);

        $this->assertTrue(Storage::disk('spaces')->exists($sonuc['dosya_yol']));
        $this->assertTrue(Storage::disk('spaces')->exists($sonuc['docx_dosya_yol']));

        $geciciDocxYolu = storage_path('app/private/tmp/test-ekayit-template.docx');
        file_put_contents($geciciDocxYolu, Storage::disk('spaces')->get($sonuc['docx_dosya_yol']));

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($geciciDocxYolu) === true);

        $xmlIcerik = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($geciciDocxYolu);

        $this->assertStringContainsString('Ahmet Emin Günden', $xmlIcerik);
        $this->assertStringContainsString('Fatma Günden', $xmlIcerik);
        $this->assertStringContainsString('Şehitler Ortaokulu', $xmlIcerik);
        $this->assertStringContainsString('Konak', $xmlIcerik);
        $this->assertStringContainsString('Mithatpaşa', $xmlIcerik);
        $this->assertStringContainsString('12', $xmlIcerik);
        $this->assertStringContainsString('w:tblLayout w:type="fixed"', $xmlIcerik);
        $this->assertStringContainsString('w:jc w:val="center"', $xmlIcerik);
        $this->assertStringContainsString('w:pgMar w:top="850"', $xmlIcerik);
        $this->assertStringContainsString('w:right="850"', $xmlIcerik);
        $this->assertStringContainsString('w:bottom="850"', $xmlIcerik);
        $this->assertStringContainsString('w:left="850"', $xmlIcerik);
        $this->assertStringNotContainsString('w:tblInd w:w="-', $xmlIcerik);
        $this->assertStringNotContainsString('ogrenci_ad_soyad', $xmlIcerik);
        $this->assertStringNotContainsString('veli_ad_soyad', $xmlIcerik);
        $this->assertDoesNotMatchRegularExpression('/\[[^\]]+\]/u', $xmlIcerik);

        $this->assertDatabaseHas('ekayit_olusturulan_evraklar', [
            'kayit_id' => $kayit->id,
            'dosya_yol' => $sonuc['dosya_yol'],
        ]);
    }

    public function test_ekayit_export_istenen_rapor_sutunlarini_olusturur(): void
    {
        $kurum = Kurum::query()->create([
            'ad' => 'Hatay Kursu',
            'slug' => 'hatay-kursu-export',
            'tip' => 'kurs',
            'aktif' => true,
        ]);

        $donem = EkayitDonem::query()->create([
            'ad' => '2026-2027 Kayıt Dönemi',
            'ogretim_yili' => '2026-2027',
            'baslangic' => now()->subDay(),
            'bitis' => now()->addMonth(),
            'aktif' => true,
        ]);

        $sinif = EkayitSinif::query()->create([
            'ad' => 'Hazırlık Sınıfı',
            'ogretim_yili' => '2026-2027',
            'kurum_id' => $kurum->id,
            'donem_id' => $donem->id,
            'renk' => 'green',
            'aktif' => true,
        ]);

        $kayit = EkayitKayit::query()->create([
            'sinif_id' => $sinif->id,
            'durum' => 'beklemede',
        ]);

        EkayitOgrenciBilgisi::query()->create([
            'kayit_id' => $kayit->id,
            'ad_soyad' => 'Ahmet Emin Günden',
            'tc_kimlik' => '12345678901',
            'telefon' => '05551112233',
            'eposta' => 'ogrenci@example.com',
            'dogum_yeri' => 'İzmir',
            'dogum_tarihi' => '2012-01-15',
            'baba_adi' => 'Mehmet',
            'anne_adi' => 'Ayşe',
            'adres' => 'Öğrenci adresi',
            'ikamet_il' => 'İzmir',
            'ikamet_ilce' => 'Karabağlar',
        ]);

        EkayitKimlikBilgisi::query()->create([
            'kayit_id' => $kayit->id,
            'kayitli_il' => 'İzmir',
            'kayitli_ilce' => 'Konak',
            'kayitli_mahalle_koy' => 'Mithatpaşa',
            'cilt_no' => '12',
            'aile_sira_no' => '34',
            'sira_no' => '56',
        ]);

        EkayitVeliBilgisi::query()->create([
            'kayit_id' => $kayit->id,
            'ad_soyad' => 'Fatma Günden',
            'eposta' => 'veli@example.com',
            'telefon_1_sahibi' => 'anne',
            'telefon_1' => '05554445566',
            'telefon_2_sahibi' => 'baba',
            'telefon_2' => '05557778899',
            'adres' => 'Veli adresi',
            'ikamet_il' => 'İzmir',
            'ikamet_ilce' => 'Konak',
        ]);

        ob_start();
        (new EkayitExport(EkayitKayit::query()->with(['sinif', 'ogrenciBilgisi', 'kimlikBilgisi', 'veliBilgisi'])->get()))
            ->download('ekayit.xlsx')
            ->sendContent();
        $icerik = ob_get_clean();

        if ($icerik === false || $icerik === null) {
            $this->fail('Excel çıktısı üretilemedi.');
        }

        $geciciDosya = tempnam(sys_get_temp_dir(), 'ekayit-export-');
        file_put_contents($geciciDosya, $icerik);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($geciciDosya) === true);

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

        $zip->close();
        @unlink($geciciDosya);

        $this->assertNotFalse($sheetXml);
        $this->assertStringContainsString('TC KİMLİK', $sheetXml);
        $this->assertStringContainsString('NUFUSA KAYITLI MAHALLE KÖY', $sheetXml);
        $this->assertStringContainsString('Anne - 05554445566', $sheetXml);
        $this->assertStringContainsString('Baba - 05557778899', $sheetXml);
        $this->assertStringContainsString('Ahmet Emin Günden', $sheetXml);
    }

    public function test_ekayit_pdf_servisi_numarali_docx_sablonlarini_siraya_gore_bulur(): void
    {
        $servis = app(EkayitPdfService::class);
        $reflection = new \ReflectionClass($servis);
        $method = $reflection->getMethod('varsayilanCokluSablonYollari');
        $method->setAccessible(true);

        $sonuc = $method->invoke($servis);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $sonuc);
        $this->assertNotEmpty($sonuc);
        $this->assertSame([
            '1kayit_dilekcesi.docx',
            '2kayit_dilekce2.docx',
            '3toplu_dilekceler.docx',
            '4saglik.docx',
            '5hafta_sonu_izin.docx',
            '6piknik_gezi.docx',
            '7foto_izin.docx',
            '8sozlesme.docx',
            '9taahhut_senedi.docx',
        ], $sonuc->map(fn (string $yol): string => basename($yol))->all());
    }

    private function hazirlaSpacesTestDiski(): void
    {
        $this->spaces_test_disk_koku = storage_path('framework/testing/disks/spaces-'.Str::uuid());

        File::ensureDirectoryExists($this->spaces_test_disk_koku);

        config()->set('filesystems.disks.spaces', [
            'driver' => 'local',
            'root' => $this->spaces_test_disk_koku,
            'url' => 'https://cdn.test',
            'visibility' => 'public',
            'throw' => false,
        ]);
    }
}
