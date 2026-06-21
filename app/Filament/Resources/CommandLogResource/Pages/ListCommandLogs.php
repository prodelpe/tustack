<?php

namespace App\Filament\Resources\CommandLogResource\Pages;

use App\Filament\Resources\CommandLogResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListCommandLogs extends ListRecords
{
    protected static string $resource = CommandLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_jobs_fetch')
                ->label('Run jobs:fetch')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Run jobs:fetch')
                ->modalDescription('This will queue a new execution of jobs:fetch. It will run in the background.')
                ->action(function () {
                    Artisan::queue('jobs:fetch');

                    Notification::make()
                        ->title('jobs:fetch queued')
                        ->body('The command has been dispatched and will run shortly.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
