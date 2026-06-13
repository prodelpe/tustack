<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $globalSearchAttributeNames = null;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('province')
                    ->options(self::provinces())
                    ->searchable()
                    ->nullable(),
                Forms\Components\TextInput::make('location')
                    ->maxLength(255)
                    ->nullable(),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_offers_count')
                    ->counts('jobOffers')
                    ->label('Offers')
                    ->sortable(),
                Tables\Columns\TextColumn::make('province')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('province')
                    ->options(
                        fn () => Company::query()
                            ->whereNotNull('province')
                            ->distinct()
                            ->orderBy('province')
                            ->pluck('province', 'province')
                            ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    private static function provinces(): array
    {
        $provinces = [
            'Álava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila',
            'Badajoz', 'Barcelona', 'Burgos', 'Cáceres', 'Cádiz', 'Cantabria',
            'Castellón', 'Ciudad Real', 'Córdoba', 'Cuenca', 'Girona', 'Granada',
            'Guadalajara', 'Guipúzcoa', 'Huelva', 'Huesca', 'Islas Baleares',
            'Jaén', 'La Coruña', 'La Rioja', 'Las Palmas', 'León', 'Lleida',
            'Lugo', 'Madrid', 'Málaga', 'Murcia', 'Navarra', 'Ourense',
            'Palencia', 'Pontevedra', 'Salamanca', 'Santa Cruz de Tenerife',
            'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Teruel', 'Toledo',
            'Valencia', 'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza',
            'Ceuta', 'Melilla',
        ];

        return array_combine($provinces, $provinces);
    }
}
