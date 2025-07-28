<?php

namespace App\Domains\Admin\Filament\Resources\AppResource\Pages;

use App\Domains\Admin\Filament\Resources\AppResource;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApp extends EditRecord
{
    protected static string $resource = AppResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
