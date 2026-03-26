<?php

namespace App\Filament\Resources;

use App\Enums\RaporPeriyot;
use App\Filament\Resources\BagisOtomatikRaporResource\Pages;
use App\Models\BagisOtomatikRapor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                ->required(),
            TagsInput::make('alicilar')
                ->label('Alıcı E-postaları')
                ->required(),
            Toggle::make('aktif')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('periyot')->label('Periyot')->badge(),
                TextColumn::make('alicilar')->label('Alıcılar')->listWithLineBreaks(),
                IconColumn::make('aktif')->label('Aktif')->boolean(),
                TextColumn::make('son_gonderim')->label('Son Gönderim')->since(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('simdi_gonder')
                    ->label('Şimdi Gönder')
                    ->color('success')
                    ->action(function (): void {
                        Notification::make()
                            ->title('Rapor gönderimi kuyruğa alınacak şekilde sonraki fazda bağlanacak.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBagisOtomatikRapors::route('/'),
            'create' => Pages\CreateBagisOtomatikRapor::route('/create'),
            'edit' => Pages\EditBagisOtomatikRapor::route('/{record}/edit'),
        ];
    }
}
