<?php

namespace App\Domains\Admin\Filament\Resources\GroupResource\Pages;

use App\Domains\Admin\Filament\Resources\GroupResource;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
