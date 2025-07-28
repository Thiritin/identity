<?php

namespace App\Domains\Admin\Filament\Resources\UserResource\Pages;

use App\Domains\Admin\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
