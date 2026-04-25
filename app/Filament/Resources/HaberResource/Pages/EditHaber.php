<?php

namespace App\Filament\Resources\HaberResource\Pages;

use App\Enums\HaberDurumu;
use App\Filament\Resources\HaberResource;
use App\Jobs\GorselOptimizeJob;
use App\Jobs\OnayEpostasiGonderJob;
use App\Models\HaberAiRevizyonu;
use App\Models\HaberKategorisi;
use App\Services\HaberAiRevizyonService;
use App\Services\HaberKategoriEslestirmeService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;

class EditHaber extends EditRecord
{
    protected static string $resource = HaberResource::class;

    public function getTitle(): string
    {
        $baslik = trim((string) ($this->record?->baslik ?? ''));
        $id = (int) ($this->record?->id ?? 0);

        if ($baslik === '') {
            return $id > 0 ? "Haber #{$id} Düzenle" : 'Haber Düzenle';
        }

        return ($id > 0 ? "#{$id} " : '') . 'Haber - ' . \Illuminate\Support\Str::limit($baslik, 50);
    }

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
            Action::make('ai_karsilastirma')
                ->label('AI Karşılaştırma')
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->visible(fn (): bool => $this->record->aiRevizyonlari()->exists())
                ->modalHeading('AI Karşılaştırma')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat')
                ->modalWidth('7xl')
                ->modalContent(fn () => view('filament.haber-ai-karsilastirma', [
                    'haber' => $this->record->fresh(['aiRevizyonlari.olusturanYonetici']),
                ])),
            Action::make('ai_surumu_uygula')
                ->label('AI Sürümünü Uygula')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (): bool => (bool) $this->record->aiRevizyonlari()->exists())
                ->requiresConfirmation()
                ->modalHeading('AI sürümü uygulansın mı?')
                ->modalDescription('Son AI revizyonundaki içerik, özet ve meta description mevcut haberin üzerine uygulanacak.')
                ->action(function (): void {
                    $revizyon = $this->record->aiRevizyonlari()->latest('created_at')->first();

                    if (! $revizyon || ! app(HaberAiRevizyonService::class)->revizyonuUygula($revizyon)) {
                        Notification::make()
                            ->title('AI sürümü uygulanamadı')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record = $this->record->fresh();

                    Notification::make()
                        ->title('AI sürümü uygulandı')
                        ->success()
                        ->send();
                }),
            Action::make('orijinale_geri_don')
                ->label('Orijinale Geri Dön')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->visible(fn (): bool => (bool) $this->record->aiRevizyonlari()->exists())
                ->requiresConfirmation()
                ->modalHeading('Orijinal sürüme dönülsün mü?')
                ->modalDescription('Son AI revizyonundan önceki başlık, içerik, özet ve meta description geri yüklenecek.')
                ->action(function (): void {
                    $revizyon = $this->record->aiRevizyonlari()->latest('created_at')->first();

                    if (! $revizyon || ! app(HaberAiRevizyonService::class)->revizyonuGeriAl($revizyon)) {
                        Notification::make()
                            ->title('Orijinal sürüme dönülemedi')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record = $this->record->fresh();

                    Notification::make()
                        ->title('Orijinal sürüm geri yüklendi')
                        ->success()
                        ->send();
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
            Action::make('ai_karsilastirma_footer')
                ->label('AI Karşılaştırma')
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->visible(fn (): bool => $this->record->aiRevizyonlari()->exists())
                ->modalHeading('AI Karşılaştırma')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat')
                ->modalWidth('7xl')
                ->modalContent(fn () => view('filament.haber-ai-karsilastirma', [
                    'haber' => $this->record->fresh(['aiRevizyonlari.olusturanYonetici']),
                ])),
        ];
    }

    protected function afterSave(): void
    {
        $haber = $this->record;

        $this->kategorileriKaydet();
        $haber = $this->record->fresh();

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

    private function kategorileriKaydet(): void
    {
        $kategoriIdleri = collect([(int) ($this->data['kategori_id'] ?? 0)])
            ->merge((array) ($this->data['ek_kategori_idleri'] ?? []))
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($kategoriIdleri->isEmpty()) {
            return;
        }

        $kategoriler = HaberKategorisi::query()
            ->whereIn('id', $kategoriIdleri->all())
            ->get(['id', 'ad', 'slug']);

        $sonuclar = $kategoriIdleri
            ->map(function (int $kategoriId, int $index) use ($kategoriler): ?array {
                $kategori = $kategoriler->firstWhere('id', $kategoriId);

                if (! $kategori) {
                    return null;
                }

                return [
                    'id' => $kategori->id,
                    'ad' => $kategori->ad,
                    'slug' => $kategori->slug,
                    'skor' => $index === 0 ? 100 : 95,
                ];
            })
            ->filter()
            ->values()
            ->all();

        app(HaberKategoriEslestirmeService::class)->haberIcinKategorileriKaydet($this->record, $sonuclar, 'manuel');
    }
}
