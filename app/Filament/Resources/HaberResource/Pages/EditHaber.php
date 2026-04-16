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
use Filament\Support\Enums\Alignment;

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
                    return auth()->check()
                        && auth()->user()->hasAnyRole(['Admin', 'Editör', 'Yazar', 'Halkla İlişkiler']);
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
                        && auth()->user()->hasAnyRole(['Yazar', 'Halkla İlişkiler'])
                        && in_array($durum, [HaberDurumu::Taslak, HaberDurumu::Incelemede], true);
                })
                ->requiresConfirmation()
                ->modalHeading('İncelemeye Gönder')
                ->modalDescription('Haber editör incelemesine gönderilecek. Devam etmek istiyor musunuz?')
                ->action(function (): void {
                    $this->record->update([
                        'durum' => HaberDurumu::Incelemede,
                        'onay_sms_gonderildi_at' => null,
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
                    $yayinTarihi = $this->record->yayin_tarihi;

                    if ($yayinTarihi && $yayinTarihi->isFuture()) {
                        $this->record->update([
                            'durum' => HaberDurumu::Planli,
                        ]);

                        return;
                    }

                    $this->record->update([
                        'durum' => HaberDurumu::Yayinda,
                        'yayin_tarihi' => $yayinTarihi ?? now(),
                    ]);
                }),
            DeleteAction::make(),
        ];
    }

    protected function getFooterActionsAlignment(): Alignment
    {
        return Alignment::Between;
    }

    protected function getFooterActions(): array
    {
        return [
            Action::make('ai_islemleri_footer')
                ->label(function (): string {
                    return $this->record->ai_islendi
                        ? 'AI İşlemlerini Tekrar Başlat'
                        : 'AI İşlemlerini Başlat';
                })
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn (): bool => auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör', 'Yazar', 'Halkla İlişkiler']))
                ->modalHeading('AI İşlemleri')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat')
                ->modalContent(fn () => view('filament.haber-ai-modal', ['haberId' => $this->record->id])),
            Action::make('incelemeye_gonder_footer')
                ->label('İncelemeye Gönder')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(function (): bool {
                    $durum = $this->record->durum instanceof HaberDurumu
                        ? $this->record->durum
                        : HaberDurumu::tryFrom((string) $this->record->durum);

                    return auth()->check()
                        && auth()->user()->hasAnyRole(['Yazar', 'Halkla İlişkiler'])
                        && in_array($durum, [
                            HaberDurumu::Taslak,
                            HaberDurumu::Incelemede,
                        ], true);
                })
                ->requiresConfirmation()
                ->modalHeading('İncelemeye Gönder')
                ->modalDescription('Haber editör incelemesine gönderilecek. Devam etmek istiyor musunuz?')
                ->action(function (): void {
                    $this->record->update([
                        'durum' => HaberDurumu::Incelemede,
                        'onay_sms_gonderildi_at' => null,
                    ]);

                    OnayEpostasiGonderJob::dispatch($this->record->id);

                    Notification::make()
                        ->title('Haber incelemeye gönderildi')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
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
