<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\Client;
use Illuminate\Http\Request;

class UserinfoController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'token' => 'string|required'
        ]);
        $hydra = new Client();
        $hydra->getToken($data['token'], ['openid', 'profile']);

        // Dead code may be used in the future
    }
}
