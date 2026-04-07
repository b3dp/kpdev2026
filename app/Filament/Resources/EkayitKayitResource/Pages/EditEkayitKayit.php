<?php

namespace App\Filament\Resources\EkayitKayitResource\Pages;

use App\Data\TurkiyeIller;
use App\Filament\Resources\EkayitKayitResource;
use App\Models\EkayitKayit;
use App\Models\EkayitSinif;
use App\Models\Uye;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditEkayitKayit extends EditRecord
{
    protected static string $resource = EkayitKayitResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Kayıt Bilgisi')->schema([
                Select::make('sinif_id')
                    ->label('Sınıf')->required()
                    ->options(fn () => EkayitSinif::with('donem')
                        ->where('aktif', true)
                        ->orderBy('ad')
                        ->get()
                        ->mapWithKeys(fn (EkayitSinif $s) => [
                            $s->id => $s->ad.' ('.$s->donem?->ad.')',
                        ])->all())
                    ->searchable(),
                Select::make('durum')
                    ->label('Durum')->required()
                    ->options(\App\Enums\EkayitDurumu::secenekler()),
                Textarea::make('durum_notu')->label('Durum Notu')->nullable()->rows(2)->columnSpanFull(),
                Textarea::make('genel_not')->label('Genel Not')->nullable()->rows(2)->columnSpanFull(),
            ])->columns(2),

            Section::make('Öğrenci Bilgileri')->schema([
                TextInput::make('ogr_ad_soyad')->label('Ad Soyad')->required()->maxLength(255),
                TextInput::make('ogr_tc_kimlik')->label('TC Kimlik')
                    ->required()->length(11)->numeric(),
                TextInput::make('ogr_dogum_yeri')->label('Doğum Yeri')->nullable()->maxLength(255),
                DatePicker::make('ogr_dogum_tarihi')->label('Doğum Tarihi')->required(),
                TextInput::make('ogr_baba_adi')->label('Baba Adı')->nullable()->maxLength(255),
                TextInput::make('ogr_anne_adi')->label('Anne Adı')->nullable()->maxLength(255),
                Textarea::make('ogr_adres')->label('Adres')->nullable()->rows(2)->columnSpanFull(),
                Select::make('ogr_ikamet_il')->label('İkamet İl / İlçe')->nullable()
                    ->options(fn () => collect(TurkiyeIller::tumu())->mapWithKeys(fn ($il) => [$il => $il])->all())
                    ->searchable(),
            ])->columns(2),

            Section::make('Kimlik Bilgileri')->schema([
                TextInput::make('kim_kayitli_il')->label('Kayıtlı İl')->nullable()->maxLength(100),
                TextInput::make('kim_kayitli_ilce')->label('Kayıtlı İlçe')->nullable()->maxLength(100),
                TextInput::make('kim_kayitli_mahalle_koy')->label('Kayıtlı Mahalle/Köy')->nullable()->maxLength(255),
                TextInput::make('kim_cilt_no')->label('Cilt No')->nullable()->maxLength(50),
                TextInput::make('kim_aile_sira_no')->label('Aile Sıra No')->nullable()->maxLength(50),
                TextInput::make('kim_sira_no')->label('Sıra No')->nullable()->maxLength(50),
                TextInput::make('kim_cuzdanin_verildigi_yer')->label('Cüzdanın Verildiği Yer')->nullable()->maxLength(255),
                TextInput::make('kim_kimlik_seri_no')->label('Kimlik Seri No')->nullable()->maxLength(50),
                Select::make('kim_kan_grubu')->label('Kan Grubu')->nullable()
                    ->options(['A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-',
                               'AB+' => 'AB+', 'AB-' => 'AB-', '0+' => '0+', '0-' => '0-']),
            ])->columns(3),

            Section::make('Okul Bilgileri')->schema([
                TextInput::make('okl_okul_adi')->label('Okul Adı')->nullable()->maxLength(255),
                TextInput::make('okl_okul_numarasi')->label('Okul Numarası')->nullable()->maxLength(50),
                TextInput::make('okl_sube')->label('Şube')->nullable()->maxLength(10),
                Textarea::make('okl_not')->label('Not')->nullable()->rows(2)->columnSpanFull(),
            ])->columns(3),

            Section::make('Veli Bilgileri')->schema([
                TextInput::make('vel_ad_soyad')->label('Ad Soyad')->required()->maxLength(255),
                TextInput::make('vel_eposta')->label('E-posta')->nullable()->email()->maxLength(255),
                TextInput::make('vel_telefon_1')->label('Telefon 1 (WhatsApp)')->required()->maxLength(20),
                TextInput::make('vel_telefon_2')->label('Telefon 2')->nullable()->maxLength(20),
            ])->columns(2),
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var EkayitKayit $kayit */
        $kayit = $this->record;
        $kayit->loadMissing(['ogrenciBilgisi', 'kimlikBilgisi', 'okulBilgisi', 'veliBilgisi']);

        $ogr = $kayit->ogrenciBilgisi;
        $kim = $kayit->kimlikBilgisi;
        $okl = $kayit->okulBilgisi;
        $vel = $kayit->veliBilgisi;

        return array_merge($data, [
            // Öğrenci
            'ogr_ad_soyad'    => $ogr?->ad_soyad,
            'ogr_tc_kimlik'   => $ogr?->tc_kimlik,
            'ogr_dogum_yeri'  => $ogr?->dogum_yeri,
            'ogr_dogum_tarihi'=> $ogr?->dogum_tarihi?->toDateString(),
            'ogr_baba_adi'    => $ogr?->baba_adi,
            'ogr_anne_adi'    => $ogr?->anne_adi,
            'ogr_adres'       => $ogr?->adres,
            'ogr_ikamet_il'   => $ogr?->ikamet_il,
            // Kimlik
            'kim_kayitli_il'               => $kim?->kayitli_il,
            'kim_kayitli_ilce'             => $kim?->kayitli_ilce,
            'kim_kayitli_mahalle_koy'      => $kim?->kayitli_mahalle_koy,
            'kim_cilt_no'                  => $kim?->cilt_no,
            'kim_aile_sira_no'             => $kim?->aile_sira_no,
            'kim_sira_no'                  => $kim?->sira_no,
            'kim_cuzdanin_verildigi_yer'   => $kim?->cuzdanin_verildigi_yer,
            'kim_kimlik_seri_no'           => $kim?->kimlik_seri_no,
            'kim_kan_grubu'                => $kim?->kan_grubu,
            // Okul
            'okl_okul_adi'      => $okl?->okul_adi,
            'okl_okul_numarasi' => $okl?->okul_numarasi,
            'okl_sube'          => $okl?->sube,
            'okl_not'           => $okl?->not,
            // Veli
            'vel_ad_soyad'  => $vel?->ad_soyad,
            'vel_eposta'    => $vel?->eposta,
            'vel_telefon_1' => $vel?->telefon_1,
            'vel_telefon_2' => $vel?->telefon_2,
        ]);
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var EkayitKayit $kayit */
        $kayit = $record;

        // Üye yeniden eşleştir
        $uyeId = $kayit->uye_id;
        if (! $uyeId) {
            $uye = Uye::where('telefon', $data['vel_telefon_1'] ?? '')
                ->orWhere('eposta', $data['vel_eposta'] ?? '')
                ->first();
            $uyeId = $uye?->id;
        }

        $kayit->update([
            'sinif_id'    => $data['sinif_id'],
            'durum'       => $data['durum'],
            'durum_notu'  => $data['durum_notu'] ?? null,
            'genel_not'   => $data['genel_not'] ?? null,
            'uye_id'      => $uyeId,
        ]);

        // Öğrenci
        $kayit->ogrenciBilgisi()->updateOrCreate(['kayit_id' => $kayit->id], [
            'ad_soyad'    => $data['ogr_ad_soyad'],
            'tc_kimlik'   => $data['ogr_tc_kimlik'],
            'dogum_yeri'  => $data['ogr_dogum_yeri'] ?? null,
            'dogum_tarihi'=> $data['ogr_dogum_tarihi'],
            'baba_adi'    => $data['ogr_baba_adi'] ?? null,
            'anne_adi'    => $data['ogr_anne_adi'] ?? null,
            'adres'       => $data['ogr_adres'] ?? null,
            'ikamet_il'   => $data['ogr_ikamet_il'] ?? null,
        ]);

        // Kimlik
        $kayit->kimlikBilgisi()->updateOrCreate(['kayit_id' => $kayit->id], [
            'kayitli_il'             => $data['kim_kayitli_il'] ?? null,
            'kayitli_ilce'           => $data['kim_kayitli_ilce'] ?? null,
            'kayitli_mahalle_koy'    => $data['kim_kayitli_mahalle_koy'] ?? null,
            'cilt_no'                => $data['kim_cilt_no'] ?? null,
            'aile_sira_no'           => $data['kim_aile_sira_no'] ?? null,
            'sira_no'                => $data['kim_sira_no'] ?? null,
            'cuzdanin_verildigi_yer' => $data['kim_cuzdanin_verildigi_yer'] ?? null,
            'kimlik_seri_no'         => $data['kim_kimlik_seri_no'] ?? null,
            'kan_grubu'              => $data['kim_kan_grubu'] ?? null,
        ]);

        // Okul
        $kayit->okulBilgisi()->updateOrCreate(['kayit_id' => $kayit->id], [
            'okul_adi'      => $data['okl_okul_adi'] ?? null,
            'okul_numarasi' => $data['okl_okul_numarasi'] ?? null,
            'sube'          => $data['okl_sube'] ?? null,
            'not'           => $data['okl_not'] ?? null,
        ]);

        // Veli
        $kayit->veliBilgisi()->updateOrCreate(['kayit_id' => $kayit->id], [
            'ad_soyad'  => $data['vel_ad_soyad'],
            'eposta'    => $data['vel_eposta'] ?? null,
            'telefon_1' => $data['vel_telefon_1'],
            'telefon_2' => $data['vel_telefon_2'] ?? null,
        ]);

        return $kayit;
    }
}
