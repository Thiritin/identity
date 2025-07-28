<?php

namespace App\Domains\Admin\Filament\Resources\UserResource\Pages;

use App\Domains\Admin\Filament\Resources\UserResource;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
