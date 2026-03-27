<?php

namespace App\Filament\Resources;

use App\Enums\RaporPeriyot;
use App\Filament\Resources\BagisOtomatikRaporResource\Pages;
use App\Models\BagisOtomatikRapor;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

class BagisOtomatikRaporResource extends Resource
{
    protected static ?string $model = BagisOtomatikRapor::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Bağış Otomatik Raporlar';

    protected static ?string $modelLabel = 'Bağış Otomatik Rapor';

    protected static ?string $pluralModelLabel = 'Bağış Otomatik Raporlar';

    protected static ?string $navigationGroup = 'Bağış Yönetimi';

    protected static ?int $navigationSort = 30;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Radio::make('periyot')
                ->label('Periyot')
                ->options(RaporPeriyot::secenekler())
                ->required()
                ->inline()
                ->columnSpanFull(),
            TagsInput::make('alicilar')
                ->label('Alıcı E-postaları')
                ->required()
                ->splitKeys(['Tab', ',', ';'])
                ->nestedRecursiveRules(['email'])
                ->rules(['array', 'min:1'])
                ->helperText('Birden fazla e-posta için virgül (,) veya noktalı virgül (;) kullanabilirsiniz.')
                ->dehydrateStateUsing(fn (?array $state): array => collect($state ?? [])
                    ->flatMap(function (string $deger): array {
                        $parcalar = preg_split('/[;,]+/', $deger) ?: [];

                        return array_values(array_filter(array_map('trim', $parcalar), fn (string $eposta) => $eposta !== ''));
                    })
                    ->values()
                    ->all())
                ->columnSpanFull(),
            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true),
            DateTimePicker::make('son_gonderim')
                ->label('Son Gönderim')
                ->disabled()
                ->dehydrated(false)
                ->seconds(false)
                ->hiddenOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('periyot')
                    ->label('Periyot')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $periyot = $state instanceof RaporPeriyot
                            ? $state
                            : RaporPeriyot::tryFrom((string) $state);

                        return $periyot?->label() ?? '-';
                    })
                    ->sortable(),
                TextColumn::make('alicilar')
                    ->label('Alıcılar')
                    ->formatStateUsing(function ($state): string {
                        $alicilar = collect((array) $state)->filter()->values();

                        return $alicilar->take(2)->implode(', ');
                    })
                    ->description(function ($state): ?string {
                        $adet = collect((array) $state)->filter()->count();

                        if ($adet <= 2) {
                            return null;
                        }

                        return '+' . ($adet - 2) . ' daha';
                    })
                    ->searchable(),
                ToggleColumn::make('aktif')
                    ->label('Aktif')
                    ->sortable(),
                TextColumn::make('son_gonderim')
                    ->label('Son Gönderim')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make()->label('Düzenle'),
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
                DeleteAction::make()->label('Sil'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBagisOtomatikRaporlar::route('/'),
            'create' => Pages\CreateBagisOtomatikRapor::route('/create'),
            'edit' => Pages\EditBagisOtomatikRapor::route('/{record}/edit'),
        ];
    }
}
