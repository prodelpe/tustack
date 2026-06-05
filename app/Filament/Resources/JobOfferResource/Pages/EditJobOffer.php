<?php

namespace App\Filament\Resources\JobOfferResource\Pages;

use App\Filament\Resources\JobOfferResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJobOffer extends EditRecord
{
    protected static string $resource = JobOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
