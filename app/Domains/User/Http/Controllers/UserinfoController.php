<?php

namespace App\Domains\User\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Auth\Services\Client;
use Illuminate\Http\Request;

class UserinfoController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'token' => 'string|required',
        ]);
        $hydra = new Client();
        $hydra->getToken($data['token'], ['openid', 'profile']);

        // Dead code may be used in the future
    }
}
