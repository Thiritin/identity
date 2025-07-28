<?php

namespace App\Domains\Admin\Filament\Resources\ActivityResource\Pages;

use App\Domains\Admin\Filament\Resources\ActivityResource;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
