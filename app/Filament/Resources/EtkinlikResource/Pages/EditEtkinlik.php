<?php

namespace App\Filament\Resources\EtkinlikResource\Pages;

use App\Filament\Resources\EtkinlikResource;
use App\Jobs\AiEtkinlikIsleJob;
use App\Jobs\GorselOptimizeJob;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEtkinlik extends EditRecord
{
    protected static string $resource = EtkinlikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ai_islemleri')
                ->label('AI İşlemlerini Başlat')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['ai_islendi' => false]);
                    AiEtkinlikIsleJob::dispatch($this->record->id);

                    Notification::make()
                        ->title('AI işlemi sıraya alındı.')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $etkinlik = $this->record;

        $anaGorsel = data_get($this->data, 'ana_gorsel_gecici');
        if (filled($anaGorsel)) {
            dispatch_sync(new GorselOptimizeJob($etkinlik->id, 'etkinlik', 'ana_gorsel', $anaGorsel, 1));
        }

        $galeriGorseller = array_values(array_filter((array) data_get($this->data, 'galeri_gorseller', [])));
        $baslangicSirasi = ((int) $etkinlik->gorseller()->max('sira')) + 1;
        foreach ($galeriGorseller as $sira => $geciciYol) {
            dispatch_sync(new GorselOptimizeJob($etkinlik->id, 'etkinlik', 'galeri_gorseli', $geciciYol, $baslangicSirasi + $sira));
        }
    }
}
