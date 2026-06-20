<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\SavedSearchesRelationManager;
use App\Models\User;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Toggle::make('is_admin')->label('Admin'),
            Toggle::make('alerts_enabled')->label('Alerts enabled'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                IconColumn::make('telegram_chat_id')
                    ->label('Telegram')
                    ->boolean()
                    ->getStateUsing(fn(User $record) => filled($record->telegram_chat_id)),
                IconColumn::make('alerts_enabled')
                    ->label('Alerts')
                    ->boolean(),
                TextColumn::make('saved_searches_count')
                    ->label('Saved searches')
                    ->counts('savedSearches')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_admin')->label('Admin'),
                TernaryFilter::make('telegram_chat_id')
                    ->label('Has Telegram')
                    ->nullable(),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            SavedSearchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit'  => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
