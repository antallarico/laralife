<?php

namespace App\Http\Controllers\Sociale;

use App\Http\Controllers\Controller;

class DashboardSocialeController extends Controller
{
    public function index()
    {
        return view('sociale.dashboard');
    }
}
