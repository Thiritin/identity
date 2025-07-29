<?php

namespace App\Domains\Staff\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::guard('staff')->user();
        
        return inertia('Staff/Profile/EditProfile', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'profile_photo_url' => $user->profile_photo_url,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'nickname' => $user->nickname,
                'phone_numbers' => $user->phone_numbers,
                'telegram_username' => $user->telegram_username,
                'address_line_1' => $user->address_line_1,
                'address_line_2' => $user->address_line_2,
                'city' => $user->city,
                'state_province' => $user->state_province,
                'postal_code' => $user->postal_code,
                'country' => $user->country,
                'date_of_birth' => $user->date_of_birth,
                'languages' => $user->languages,
                'credit_as' => $user->credit_as,
                'joined_ef_year' => $user->joined_ef_year,
                'first_ef_year' => $user->first_ef_year,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::guard('staff')->user();

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'phone_numbers' => ['nullable', 'array'],
            'phone_numbers.*' => ['string', 'max:50'],
            'telegram_username' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_]{5,32}$/'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:2'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', 'max:10'],
            'credit_as' => ['nullable', 'string', 'max:255'],
            'joined_ef_year' => ['nullable', 'integer', 'min:1993', 'max:' . date('Y')],
            'first_ef_year' => ['nullable', 'string', 'max:50'],
            'profile_photo' => ['nullable', 'image', 'max:2048'], // 2MB max
        ]);

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $validated['profile_photo_path'] = $path;
            unset($validated['profile_photo']);
        }

        // Update the user
        $user->update($validated);

        // Mark profile as completed if it wasn't already
        if (!$user->profile_completed_at && $this->isProfileComplete($user)) {
            $user->update(['profile_completed_at' => now()]);
        }

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }

    private function isProfileComplete($user): bool
    {
        return $user->first_name && 
               $user->last_name && 
               $user->city && 
               $user->country &&
               $user->languages &&
               count($user->languages) > 0;
    }
}