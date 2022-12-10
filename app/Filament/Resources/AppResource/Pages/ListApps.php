<?php

namespace App\Filament\Resources\AppResource\Pages;

use App\Filament\Resources\AppResource;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApps extends ListRecords
{
    protected static string $resource = AppResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
