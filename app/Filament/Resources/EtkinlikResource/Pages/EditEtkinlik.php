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

        $anaGorsel = $this->tekDosyaYolu(data_get($this->data, 'gorsel_lg'));
        if (filled($anaGorsel)) {
            dispatch_sync(new GorselOptimizeJob($etkinlik->id, 'etkinlik', 'ana_gorsel', $anaGorsel, 1));
        }

        $galeriGorseller = $this->cokluDosyaYollari((array) data_get($this->data, 'galeri_gorseller', []));
        $baslangicSirasi = ((int) $etkinlik->gorseller()->max('sira')) + 1;
        foreach ($galeriGorseller as $sira => $geciciYol) {
            dispatch_sync(new GorselOptimizeJob($etkinlik->id, 'etkinlik', 'galeri_gorseli', $geciciYol, $baslangicSirasi + $sira));
        }
    }

    private function tekDosyaYolu(mixed $deger): ?string
    {
        if (is_string($deger) && filled($deger)) {
            return $deger;
        }

        if (is_array($deger)) {
            foreach ($deger as $oge) {
                if (is_string($oge) && filled($oge)) {
                    return $oge;
                }
            }
        }

        return null;
    }

    private function cokluDosyaYollari(array $degerler): array
    {
        $sonuc = [];

        foreach ($degerler as $deger) {
            if (is_string($deger) && filled($deger)) {
                $sonuc[] = $deger;
                continue;
            }

            if (is_array($deger)) {
                foreach ($deger as $oge) {
                    if (is_string($oge) && filled($oge)) {
                        $sonuc[] = $oge;
                    }
                }
            }
        }

        return array_values($sonuc);
    }
}
