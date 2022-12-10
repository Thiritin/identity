<?php

namespace App\Http\Controllers;

use App\Models\App;
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

    public function update(Request $request, App $app)
    {
    }

    public function destroy(App $app)
    {
    }
}
