<?php

namespace App\Domains\Admin\Filament\Resources\AppResource\Pages;

use App\Domains\Admin\Filament\Resources\AppResource;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApps extends ListRecords
{
    protected static string $resource = AppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
