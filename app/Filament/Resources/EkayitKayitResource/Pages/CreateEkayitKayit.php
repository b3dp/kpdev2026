<?php

namespace App\Filament\Resources\EkayitKayitResource\Pages;

use App\Data\TurkiyeIller;
use App\Filament\Resources\EkayitKayitResource;
use App\Jobs\EkayitSmsJob;
use App\Models\EkayitKayit;
use App\Models\EkayitKimlikBilgisi;
use App\Models\EkayitOgrenciBilgisi;
use App\Models\EkayitOkulBilgisi;
use App\Models\EkayitSinif;
use App\Models\EkayitVeliBilgisi;
use App\Models\Uye;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateEkayitKayit extends CreateRecord
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
                    ->options(\App\Enums\EkayitDurumu::secenekler())
                    ->default('beklemede'),
            ])->columns(2),

            Section::make('Öğrenci Bilgileri')->schema([
                TextInput::make('ogr_ad_soyad')->label('Ad Soyad')->required()->maxLength(255),
                TextInput::make('ogr_tc_kimlik')->label('TC Kimlik')
                    ->required()->length(11)->numeric(),
                TextInput::make('ogr_telefon')->label('Cep Telefonu')->tel()->maxLength(20),
                TextInput::make('ogr_eposta')->label('E-posta')->email()->maxLength(255),
                TextInput::make('ogr_dogum_yeri')->label('Doğum Yeri')->nullable()->maxLength(255),
                DatePicker::make('ogr_dogum_tarihi')->label('Doğum Tarihi')->required(),
                TextInput::make('ogr_baba_adi')->label('Baba Adı')->nullable()->maxLength(255),
                TextInput::make('ogr_anne_adi')->label('Anne Adı')->nullable()->maxLength(255),
                Textarea::make('ogr_adres')->label('Adres')->nullable()->rows(2)->columnSpanFull(),
                Select::make('ogr_ikamet_il')->label('İkamet İl')->nullable()
                    ->options(fn () => collect(TurkiyeIller::tumu())->mapWithKeys(fn ($il) => [$il => $il])->all())
                    ->searchable(),
                TextInput::make('ogr_ikamet_ilce')->label('İkamet İlçesi')->nullable()->maxLength(100),
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

    public function getFormStatePath(): string
    {
        return 'data';
    }

    protected function handleRecordCreation(array $data): EkayitKayit
    {
        // TC + Dönem teklik kontrolü
        $sinif = EkayitSinif::findOrFail($data['sinif_id']);
        $tcKimlik = $data['ogr_tc_kimlik'] ?? '';

        $mevcutKayit = EkayitKayit::withTrashed()
            ->whereHas('ogrenciBilgisi', fn ($q) => $q->where('tc_kimlik', $tcKimlik))
            ->whereHas('sinif', fn ($q) => $q->where('donem_id', $sinif->donem_id))
            ->first();

        if ($mevcutKayit) {
            Notification::make()->danger()
                ->title('Tekrar Kayıt')
                ->body('Bu TC kimlik numarasıyla bu dönem için zaten başvuru mevcut (Kayıt #'.$mevcutKayit->id.').')
                ->send();

            $this->halt();
        }

        // Üye eşleştirme
        $uyeId = null;
        $uye = Uye::where('telefon', $data['vel_telefon_1'] ?? '')
            ->orWhere('eposta', $data['vel_eposta'] ?? '')
            ->first();
        if ($uye) {
            $uyeId = $uye->id;
        }

        // Ana kayıt
        $kayit = EkayitKayit::create([
            'sinif_id'  => $data['sinif_id'],
            'durum'     => $data['durum'] ?? 'beklemede',
            'uye_id'    => $uyeId,
        ]);

        // Öğrenci bilgileri
        EkayitOgrenciBilgisi::create([
            'kayit_id'    => $kayit->id,
            'ad_soyad'    => $data['ogr_ad_soyad'],
            'tc_kimlik'   => $tcKimlik,
            'telefon'     => $data['ogr_telefon'] ?? null,
            'eposta'      => $data['ogr_eposta'] ?? null,
            'dogum_yeri'  => $data['ogr_dogum_yeri'] ?? null,
            'dogum_tarihi'=> $data['ogr_dogum_tarihi'],
            'baba_adi'    => $data['ogr_baba_adi'] ?? null,
            'anne_adi'    => $data['ogr_anne_adi'] ?? null,
            'adres'       => $data['ogr_adres'] ?? null,
            'ikamet_il'   => $data['ogr_ikamet_il'] ?? null,
            'ikamet_ilce' => $data['ogr_ikamet_ilce'] ?? null,
        ]);

        // Kimlik bilgileri
        EkayitKimlikBilgisi::create([
            'kayit_id'                 => $kayit->id,
            'kayitli_il'               => $data['kim_kayitli_il'] ?? null,
            'kayitli_ilce'             => $data['kim_kayitli_ilce'] ?? null,
            'kayitli_mahalle_koy'      => $data['kim_kayitli_mahalle_koy'] ?? null,
            'cilt_no'                  => $data['kim_cilt_no'] ?? null,
            'aile_sira_no'             => $data['kim_aile_sira_no'] ?? null,
            'sira_no'                  => $data['kim_sira_no'] ?? null,
            'cuzdanin_verildigi_yer'   => $data['kim_cuzdanin_verildigi_yer'] ?? null,
            'kimlik_seri_no'           => $data['kim_kimlik_seri_no'] ?? null,
            'kan_grubu'                => $data['kim_kan_grubu'] ?? null,
        ]);

        // Okul bilgileri
        EkayitOkulBilgisi::create([
            'kayit_id'      => $kayit->id,
            'okul_adi'      => $data['okl_okul_adi'] ?? null,
            'okul_numarasi' => $data['okl_okul_numarasi'] ?? null,
            'sube'          => $data['okl_sube'] ?? null,
            'not'           => $data['okl_not'] ?? null,
        ]);

        // Veli bilgileri
        EkayitVeliBilgisi::create([
            'kayit_id'   => $kayit->id,
            'ad_soyad'   => $data['vel_ad_soyad'],
            'eposta'     => $data['vel_eposta'] ?? null,
            'telefon_1'  => $data['vel_telefon_1'],
            'telefon_2'  => $data['vel_telefon_2'] ?? null,
        ]);

        // Başvuru alındı SMS'i
        $telefon1 = $data['vel_telefon_1'] ?? null;
        if (filled($telefon1)) {
            dispatch(new EkayitSmsJob(
                $kayit->id,
                'basvuru_alindi',
                $telefon1,
                false,
                auth()->id() ?? 1
            ));
        }

        return $kayit;
    }
}
