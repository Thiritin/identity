<?php

namespace App\Filament\Convention\Resources\ConventionResource\Pages;

use App\Filament\Convention\Resources\ConventionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConvention extends EditRecord
{
    protected static string $resource = ConventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
