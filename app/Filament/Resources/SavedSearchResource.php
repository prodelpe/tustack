<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavedSearchResource\Pages;
use App\Models\SavedSearch;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SavedSearchResource extends Resource
{
    protected static ?string $model = SavedSearch::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Saved Searches';

    protected static ?string $navigationGroup = 'Users';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('filters_description')
                    ->label('Filters')
                    ->getStateUsing(function (SavedSearch $record) {
                        return $record->describe();
                    }),
                TextColumn::make('last_notified_at')
                    ->label('Last notified')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('never_notified')
                    ->label('Never notified')
                    ->query(function (Builder $query) {
                        $query->whereNull('last_notified_at');
                    }),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavedSearches::route('/'),
        ];
    }
}
