<?php

namespace App\Http\Controllers\Profile;

use App\Events\NewProfilePhotoEvent;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Storage;

class StoreAvatarController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'image' => [
                'image',
                'mimes:jpg,jpeg,png',
                'max:10240',
            ],
            'crop.height' => 'integer|required',
            'crop.width' => 'integer|required',
            'crop.x' => 'integer|required',
            'crop.y' => 'integer|required',
        ]);
        $image = $request->file('image');
        $image = Image::make($image)->crop($data['crop']['width'], $data['crop']['height'], $data['crop']['x'],
            $data['crop']['y'])->encode('webp', '100');
        $path = Str::random(40).".webp";
        Storage::disk('s3-avatars')->put($path, $image);
        NewProfilePhotoEvent::dispatch(Auth::user(), $path);
        if (!is_null(Auth::user()->profile_photo_path)) {
            if (Storage::disk('s3-avatars')->exists(Auth::user()->profile_photo_path)) {
                Storage::disk('s3-avatars')->delete(Auth::user()->profile_photo_path);
            }
        }
        $request->user()->update(['profile_photo_path' => $path]);
        return Redirect::route('settings.profile');
    }
}
