<?php

namespace App\Filament\Resources\HaberResource\Pages;

use App\Enums\HaberDurumu;
use App\Filament\Resources\HaberResource;
use App\Jobs\GorselOptimizeJob;
use App\Jobs\OnayEpostasiGonderJob;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditHaber extends EditRecord
{
    protected static string $resource = HaberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ai_islemleri')
                ->label(function (): string {
                    return $this->record->ai_islendi
                        ? 'AI İşlemlerini Tekrar Başlat'
                        : 'AI İşlemlerini Başlat';
                })
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(function (): bool {
                    $durum = $this->record->durum instanceof HaberDurumu
                        ? $this->record->durum
                        : HaberDurumu::tryFrom((string) $this->record->durum);

                    return auth()->check()
                        && auth()->user()->hasAnyRole(['Admin', 'Editör', 'Yazar'])
                        && in_array($durum, [HaberDurumu::Taslak, HaberDurumu::Incelemede], true);
                })
                ->modalHeading('AI İşlemleri')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat')
                ->modalContent(fn () => view('filament.haber-ai-modal', ['haberId' => $this->record->id])),
            Action::make('incelemeye_gonder')
                ->label('İncelemeye Gönder')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(function (): bool {
                    $durum = $this->record->durum instanceof HaberDurumu
                        ? $this->record->durum
                        : HaberDurumu::tryFrom((string) $this->record->durum);

                    return auth()->check()
                        && auth()->user()->hasRole('Yazar')
                        && $durum === HaberDurumu::Taslak;
                })
                ->requiresConfirmation()
                ->modalHeading('İncelemeye Gönder')
                ->modalDescription('Haber editör incelemesine gönderilecek. Devam etmek istiyor musunuz?')
                ->action(function (): void {
                    $this->record->update([
                        'durum' => HaberDurumu::Incelemede,
                    ]);

                    OnayEpostasiGonderJob::dispatch($this->record->id);

                    Notification::make()
                        ->title('Haber incelemeye gönderildi')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
            Action::make('yayinla')
                ->label('Yayına Al')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(function (): bool {
                    $durum = $this->record->durum instanceof HaberDurumu
                        ? $this->record->durum
                        : HaberDurumu::tryFrom((string) $this->record->durum);

                    return auth()->check()
                        && auth()->user()->hasRole('Editör')
                        && $durum !== HaberDurumu::Yayinda;
                })
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'durum' => HaberDurumu::Yayinda,
                        'yayin_tarihi' => $this->record->yayin_tarihi ?? now(),
                    ]);
                }),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $haber = $this->record;

        if (auth()->user()?->hasRole('Editör') && $this->record->wasChanged()) {
            $degisiklikler = $this->record->getChanges();
            unset($degisiklikler['updated_at']);

            if (! empty($degisiklikler)) {
                activity('haber_revize')
                    ->causedBy(auth()->user())
                    ->performedOn($this->record)
                    ->withProperties(['degisiklikler' => $degisiklikler])
                    ->log('Editör revizyonu yapıldı');
            }
        }

        // Ana görsel
        $anaGorsel = data_get($this->data, 'ana_gorsel_gecici');
        $anaGorsel = is_array($anaGorsel) ? (array_values($anaGorsel)[0] ?? null) : $anaGorsel;
        if (filled($anaGorsel) && is_string($anaGorsel)) {
            dispatch_sync(new GorselOptimizeJob($haber->id, 'haber', 'ana_gorsel', $anaGorsel, 1));
        }

        // Galeri görselleri
        $galeriGorseller = array_values(array_filter((array) data_get($this->data, 'galeri_gorseller', [])));
        $baslangicSirasi = ((int) $haber->gorseller()->max('sira')) + 1;
        foreach ($galeriGorseller as $sira => $geciciYol) {
            dispatch_sync(new GorselOptimizeJob($haber->id, 'haber', 'galeri_gorseli', $geciciYol, $baslangicSirasi + $sira));
        }

        if ($haber->durum === HaberDurumu::Incelemede) {
            OnayEpostasiGonderJob::dispatch($haber->id);
        }
    }
}
