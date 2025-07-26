<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Domains\User\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

/**
 * @property User $record
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {

        return [
            // Reset 2FA
            Action::make('Reset 2FA')
                ->label('Reset 2FA')
                ->requiresConfirmation()
                ->color('danger')
                ->visible(fn (User $record) => $record->twoFactors()->exists())
                ->action(function () {
                    $this->record->resetTwoFactorAuth();
                }),
        ];
    }
}
