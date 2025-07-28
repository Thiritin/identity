<?php

namespace App\Domains\Admin\Filament\Resources\GroupResource\Pages;

use App\Domains\Admin\Filament\Resources\GroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGroup extends CreateRecord
{
    protected static string $resource = GroupResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
