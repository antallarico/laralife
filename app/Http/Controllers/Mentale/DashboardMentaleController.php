<?php

namespace App\Http\Controllers\Mentale;

use App\Http\Controllers\Controller;

class DashboardMentaleController extends Controller
{
    public function index()
    {
        return view('mentale.dashboard');
    }
}
