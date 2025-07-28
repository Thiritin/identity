<?php

namespace App\Domains\Admin\Filament\Resources\GroupResource\Pages;

use App\Domains\Admin\Filament\Resources\GroupResource;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGroups extends ListRecords
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
