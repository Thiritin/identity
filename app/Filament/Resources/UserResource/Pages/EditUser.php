<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\BackupCodeService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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

            // Generate Backup Codes
            Action::make('generateBackupCodes')
                ->label('Generate Backup Codes')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Generate Backup Codes')
                ->modalDescription('This will generate new backup codes for the user, invalidating any existing ones. Share these codes with the user securely.')
                ->action(function () {
                    $service = new BackupCodeService();
                    $plaintextCodes = $service->generate();
                    $service->storeForUser($this->record, $plaintextCodes);

                    $formatted = array_map(
                        fn (string $code) => BackupCodeService::formatForDisplay($code),
                        $plaintextCodes
                    );

                    Notification::make()
                        ->title('Backup Codes Generated')
                        ->body(implode('  |  ', $formatted))
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
