<?php

namespace App\Domains\User\Http\Controllers;

use App\Domains\User\Models\App;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppsController extends Controller
{
    public function index()
    {
        return inertia('Apps/Index');
    }

    public function create() {}

    public function store(Request $request) {}

    public function show(App $app) {}

    public function edit(App $app) {}

    public function update(Request $request) {}

    public function destroy(App $app) {}
}
