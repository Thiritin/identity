<?php

namespace App\Filament\Resources\AppCategories\Pages;

use App\Filament\Resources\AppCategories\AppCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAppCategory extends EditRecord
{
    protected static string $resource = AppCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
