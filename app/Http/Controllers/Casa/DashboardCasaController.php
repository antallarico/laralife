<?php

namespace App\Http\Controllers\Casa;

use App\Http\Controllers\Controller;

class DashboardCasaController extends Controller
{
    public function index()
    {
        return view('casa.dashboard');
    }
}
