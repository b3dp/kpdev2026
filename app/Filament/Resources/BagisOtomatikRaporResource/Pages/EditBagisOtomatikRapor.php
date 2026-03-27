<?php

namespace App\Filament\Resources\BagisOtomatikRaporResource\Pages;

use App\Filament\Resources\BagisOtomatikRaporResource;
use App\Models\BagisOtomatikRapor;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

class EditBagisOtomatikRapor extends EditRecord
{
    protected static string $resource = BagisOtomatikRaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('simdi_gonder')
                ->label('Şimdi Gönder')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->action(function (BagisOtomatikRapor $record): void {
                    try {
                        $cikisKodu = Artisan::call('bagis:rapor-gonder', [
                            'periyot' => $record->periyot->value,
                            '--tarih' => 'bugun',
                        ]);

                        if ($cikisKodu !== 0) {
                            $hataMesaji = trim(Artisan::output());
                            throw new RuntimeException($hataMesaji === '' ? 'Bilinmeyen hata' : $hataMesaji);
                        }

                        Notification::make()
                            ->title('Rapor gönderildi')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Rapor gönderilemedi: '.$exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }
}
