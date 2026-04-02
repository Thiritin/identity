<?php

namespace App\Filament\Resources\AppCategories\Pages;

use App\Filament\Resources\AppCategories\AppCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppCategories extends ListRecords
{
    protected static string $resource = AppCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
