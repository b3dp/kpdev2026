<?php

namespace App\Filament\Resources\HaberResource\Pages;

use App\Enums\HaberDurumu;
use App\Filament\Resources\HaberResource;
use App\Jobs\GorselOptimizeJob;
use App\Jobs\OnayEpostasiGonderJob;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHaber extends EditRecord
{
    protected static string $resource = HaberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ai_islemleri')
                ->label('AI İşlemlerini Başlat')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn () => auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']))
                ->hidden(fn () => $this->record->durum !== HaberDurumu::Taslak)
                ->modalHeading('AI İşlemleri')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat')
                ->modalContent(fn () => view('filament.haber-ai-modal', ['haberId' => $this->record->id])),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $haber = $this->record;
        $gorseller = array_values(array_filter((array) data_get($this->data, 'gorseller', [])));

        if (! empty($gorseller)) {
            dispatch_sync(new GorselOptimizeJob($haber->id, $gorseller));
        }

        if ($haber->durum === HaberDurumu::Incelemede) {
            OnayEpostasiGonderJob::dispatch($haber->id);
        }
    }
}
