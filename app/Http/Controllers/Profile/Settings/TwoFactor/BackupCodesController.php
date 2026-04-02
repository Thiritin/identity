<?php

namespace App\Http\Controllers\Profile\Settings\TwoFactor;

use App\Http\Controllers\Controller;
use App\Http\Requests\TwoFactor\BackupCodesRegenerateRequest;
use App\Services\BackupCodeService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BackupCodesController extends Controller
{
    public function __construct(private BackupCodeService $backupCodeService) {}

    public function regenerate(BackupCodesRegenerateRequest $request)
    {
        $data = $request->validated();

        if (! Hash::check($data['password'], auth()->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Invalid password']);
        }

        $plaintextCodes = $this->backupCodeService->generate();
        $this->backupCodeService->storeForUser(auth()->user(), $plaintextCodes);

        session()->flash('backup_codes', $plaintextCodes);

        return redirect()->route('settings.security.backup-codes');
    }
}
