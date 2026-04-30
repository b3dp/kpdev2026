<?php

namespace App\Filament\Pages;

use App\Jobs\ExcelSmsGonderimJob;
use App\Models\SmsExcelGonderim;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ExcelSmsGonderSayfasi extends Page implements \Filament\Forms\Contracts\HasForms
{
    use \App\Support\PanelYetkiKontrolu;
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'SMS Yönetimi';

    protected static ?string $navigationLabel = 'Excel\'den SMS Gönder';

    protected static ?int $navigationSort = 32;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static string $view = 'filament.pages.excel-sms-gonder-sayfasi';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return static::izinVarMi('pazarlama_sms.gonder');
    }

    public function mount(): void
    {
        $this->form->fill([
            'dosya' => null,
            'mesaj' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('dosya')
                    ->label('Excel Dosyası')
                    ->required()
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(10240)
                    ->helperText('Tek kolon telefon listesi beklenir. Her satır 532xxxxxxx formatında olmalıdır.'),

                Textarea::make('mesaj')
                    ->label('Mesaj')
                    ->required()
                    ->rows(6)
                    ->maxLength(1000),
            ])
            ->statePath('data');
    }

    public function gonder(): void
    {
        $state = $this->form->getState();

        $rapor = SmsExcelGonderim::query()->create([
            'yonetici_id' => auth()->id(),
            'dosya' => (string) $state['dosya'],
            'mesaj' => (string) $state['mesaj'],
            'durum' => 'bekliyor',
        ]);

        ExcelSmsGonderimJob::dispatch(
            (string) $state['dosya'],
            (int) auth()->id(),
            (int) $rapor->id,
        );

        Notification::make()
            ->title('Excel SMS gönderimi başlatıldı')
            ->body('Sonucu SMS Yönetimi > Excel SMS Raporları ekranından takip edebilirsiniz.')
            ->success()
            ->send();

        $this->form->fill([
            'dosya' => null,
            'mesaj' => null,
        ]);
    }
}
