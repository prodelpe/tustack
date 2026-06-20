<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SavedSearchesRelationManager extends RelationManager
{
    protected static string $relationship = 'savedSearches';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->label('Filters')
                    ->getStateUsing(fn($record) => $record->describe()),
                TextColumn::make('created_at')
                    ->label('Saved at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->actions([
                DeleteAction::make(),
            ]);
    }
}
