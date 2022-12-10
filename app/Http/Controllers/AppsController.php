<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\User;
use Illuminate\Http\Request;

class AppsController extends Controller
{
    public function index()
    {
        return inertia("Apps/Index");
    }

    public function create()
    {
    }

    public function store(Request $request)
    {
    }

    public function show(App $app)
    {

    }

    public function edit(App $app)
    {
    }

    public function update(Request $request)
    {
        App::where('client_id', '=', '1');

        $user = User::where('name', '=', 'Tin')->first();
        $user->name = "Martin";
        $user->whereName('Tin')->first();
    }

    public function destroy(App $app)
    {
    }
}
