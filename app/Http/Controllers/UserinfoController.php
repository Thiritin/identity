<?php

namespace App\Http\Controllers;

use App\Services\Hydra;
use Illuminate\Http\Request;

class UserinfoController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'token' => 'string|required'
        ]);
        $hydra = new Hydra();
        $hydra->getToken($data['token'], ['openid', 'profile']);

        // Dead code may be used in the future
    }
}
