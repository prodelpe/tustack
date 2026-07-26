<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommandLogResource\Pages;
use App\Models\CommandLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommandLogResource extends Resource
{
    protected static ?string $model = CommandLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationLabel = 'Command Logs';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('command')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'jobs:fetch'       => 'info',
                        'companies:enrich' => 'warning',
                        'searches:notify'  => 'success',
                        default            => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'success' => 'success',
                        'failed'  => 'danger',
                        'running' => 'warning',
                        default   => 'gray',
                    }),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(fn (CommandLog $record) => $record->duration ?? '—'),
                TextColumn::make('stats_summary')
                    ->label('Stats')
                    ->getStateUsing(function (CommandLog $record) {
                        if (! $record->stats) return '—';

                        return match ($record->command) {
                            'jobs:fetch'       => 'Queries: ' . data_get($record->stats, 'total_queries', '?') . ' · failed: ' . data_get($record->stats, 'failed', 0),
                            'companies:enrich' => 'Companies: ' . data_get($record->stats, 'total', '?') . ' · failed: ' . data_get($record->stats, 'failed', 0),
                            'searches:notify'  => 'Dispatched: ' . data_get($record->stats, 'dispatched', 0),
                            default            => '—',
                        };
                    }),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(60)
                    ->placeholder('—')
                    ->color('danger'),
                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('command')
                    ->options([
                        'jobs:fetch'       => 'jobs:fetch',
                        'companies:enrich' => 'companies:enrich',
                        'searches:notify'  => 'searches:notify',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'failed'  => 'Failed',
                        'running' => 'Running',
                    ]),
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
            'index' => Pages\ListCommandLogs::route('/'),
        ];
    }
}
