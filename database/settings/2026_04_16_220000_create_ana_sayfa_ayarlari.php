<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('ana_sayfa.ust_bant_metni', "Seferihisar, İzmir - 1966'dan beri");
        $this->migrator->add('ana_sayfa.baslik_ust', 'Geleceği');
        $this->migrator->add('ana_sayfa.baslik_vurgulu', 'Birlikte');
        $this->migrator->add('ana_sayfa.baslik_alt', 'İnşa Ediyoruz');
        $this->migrator->add('ana_sayfa.alt_metin', '58 yıldır Seferihisar gençlerinin eğitim hayatında yanlarındayız. Her bağışınız bir öğrencinin geleceğini şekillendiriyor.');
        $this->migrator->add('ana_sayfa.birinci_buton_metin', 'Bağış Yap');
        $this->migrator->add('ana_sayfa.birinci_buton_url', '/bagis');
        $this->migrator->add('ana_sayfa.ikinci_buton_metin', 'Öğrenci Kayıt');
        $this->migrator->add('ana_sayfa.ikinci_buton_url', '/kayit');
        $this->migrator->add('ana_sayfa.istatistik_1_sayi', '1.250');
        $this->migrator->add('ana_sayfa.istatistik_1_etiket', 'Aktif Öğrenci');
        $this->migrator->add('ana_sayfa.istatistik_2_sayi', '4.500+');
        $this->migrator->add('ana_sayfa.istatistik_2_etiket', 'Mezun');
        $this->migrator->add('ana_sayfa.istatistik_3_sayi', '58');
        $this->migrator->add('ana_sayfa.istatistik_3_etiket', 'Yıllık Tecrübe');
    }
};