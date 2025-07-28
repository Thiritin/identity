<?php

namespace App\Domains\Admin\Filament\Resources\AppResource\Pages;

use App\Domains\Admin\Filament\Resources\AppResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApp extends CreateRecord
{
    protected static string $resource = AppResource::class;
}
