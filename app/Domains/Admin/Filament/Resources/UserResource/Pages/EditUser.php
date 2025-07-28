<?php

namespace App\Domains\Admin\Filament\Resources\UserResource\Pages;

use App\Domains\Admin\Filament\Resources\UserResource;
use Filament\Pages\Actions\Action;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('impersonate')
                ->action(function () {
                    Auth::user()->impersonate($this->record);

                    return redirect()->route('dashboard');
                })
                ->requiresConfirmation()
                ->hidden(fn () => ! Auth::user()->can('impersonate', $this->record))
                ->color('warning'),
            DeleteAction::make(),
        ];
    }
}
