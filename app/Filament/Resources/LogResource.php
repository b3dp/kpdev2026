<?php

namespace App\Filament\Resources;

use App\Models\KurbanKayit;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Z3d0X\FilamentLogger\Resources\ActivityResource;

class LogResource extends ActivityResource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getNavigationGroup(): ?string
    {
        return 'Sistem';
    }

    public static function getNavigationLabel(): string
    {
        return 'Loglar';
    }

    public static function getLabel(): string
    {
        return 'Log';
    }

    public static function getPluralLabel(): string
    {
        return 'Loglar';
    }

    public static function canViewAny(): bool
    {
        return static::izinVarMi('loglar.listele');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('log_name')
                    ->badge()
                    ->color(fn (Model $record): string => static::logTipiRengi($record))
                    ->label(__('filament-logger::filament-logger.resource.label.type'))
                    ->formatStateUsing(fn ($state, Model $record): string => static::logTipiEtiketi($record))
                    ->sortable(),

                TextColumn::make('event')
                    ->label(__('filament-logger::filament-logger.resource.label.event'))
                    ->sortable(),

                TextColumn::make('subject_type')
                    ->label(__('filament-logger::filament-logger.resource.label.subject'))
                    ->formatStateUsing(function ($state, Model $record): string {
                        if (! $state) {
                            return '-';
                        }

                        return Str::of($state)->afterLast('\\')->headline().' # '.$record->subject_id;
                    }),

                TextColumn::make('causer.name')
                    ->label(__('filament-logger::filament-logger.resource.label.user')),

                TextColumn::make('created_at')
                    ->label(__('filament-logger::filament-logger.resource.label.logged_at'))
                    ->dateTime(config('filament-logger.datetime_format', 'd/m/Y H:i:s'), config('app.timezone'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([])
            ->filters([
                SelectFilter::make('tip')
                    ->label(__('filament-logger::filament-logger.resource.label.type'))
                    ->options(static::getOzellestirilmisLogTipleri())
                    ->query(function (Builder $query, array $data): Builder {
                        $deger = $data['value'] ?? null;

                        if (! filled($deger)) {
                            return $query;
                        }

                        if ($deger === 'kurban_birlesik') {
                            return $query->where(function (Builder $altSorgu): void {
                                $altSorgu
                                    ->where('log_name', 'kurban')
                                    ->orWhere(function (Builder $icSorgu): void {
                                        $icSorgu
                                            ->where('log_name', config('filament-logger.resources.log_name'))
                                            ->where('subject_type', KurbanKayit::class);
                                    });
                            });
                        }

                        return $query->where('log_name', $deger);
                    }),

                SelectFilter::make('subject_type')
                    ->label(__('filament-logger::filament-logger.resource.label.subject_type'))
                    ->options(static::getSubjectTypeList()),

                Filter::make('properties->old')
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['old'] ?? null)) {
                            return null;
                        }

                        return __('filament-logger::filament-logger.resource.label.old_attributes').$data['old'];
                    })
                    ->form([
                        \Filament\Forms\Components\TextInput::make('old')
                            ->label(__('filament-logger::filament-logger.resource.label.old'))
                            ->hint(__('filament-logger::filament-logger.resource.label.properties_hint')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! ($data['old'] ?? null)) {
                            return $query;
                        }

                        return $query->where('properties->old', 'like', "%{$data['old']}%");
                    }),

                Filter::make('properties->attributes')
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['new'] ?? null)) {
                            return null;
                        }

                        return __('filament-logger::filament-logger.resource.label.new_attributes').$data['new'];
                    })
                    ->form([
                        \Filament\Forms\Components\TextInput::make('new')
                            ->label(__('filament-logger::filament-logger.resource.label.new'))
                            ->hint(__('filament-logger::filament-logger.resource.label.properties_hint')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! ($data['new'] ?? null)) {
                            return $query;
                        }

                        return $query->where('properties->attributes', 'like', "%{$data['new']}%");
                    }),

                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('logged_at')
                            ->label(__('filament-logger::filament-logger.resource.label.logged_at'))
                            ->displayFormat(config('filament-logger.date_format', 'd/m/Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['logged_at'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', $date),
                        );
                    }),
            ]);
    }

    protected static function getOzellestirilmisLogTipleri(): array
    {
        $tipler = parent::getLogNameList();

        unset($tipler['kurban']);

        return array_merge([
            'kurban_birlesik' => 'Kurban',
        ], $tipler);
    }

    protected static function logTipiEtiketi(Model $record): string
    {
        $logAdi = (string) ($record->log_name ?? '');
        $subjectType = (string) ($record->subject_type ?? '');

        if ($logAdi === 'kurban') {
            return 'Kurban';
        }

        if ($logAdi === config('filament-logger.resources.log_name') && $subjectType === KurbanKayit::class) {
            return 'Kurban';
        }

        return $logAdi !== '' ? ucwords($logAdi) : '-';
    }

    protected static function logTipiRengi(Model $record): string
    {
        $logAdi = (string) ($record->log_name ?? '');
        $subjectType = (string) ($record->subject_type ?? '');

        if ($logAdi === 'kurban' || ($logAdi === config('filament-logger.resources.log_name') && $subjectType === KurbanKayit::class)) {
            return 'success';
        }

        return match ($logAdi) {
            config('filament-logger.models.log_name') => 'warning',
            config('filament-logger.access.log_name') => 'danger',
            config('filament-logger.notifications.log_name') => 'gray',
            config('filament-logger.resources.log_name') => 'success',
            default => 'gray',
        };
    }
}
