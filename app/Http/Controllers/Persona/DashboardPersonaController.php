<?php

namespace App\Http\Controllers\Persona;

use App\Http\Controllers\Controller;


class DashboardPersonaController extends Controller
{
    public function index()
    {
        return view('persona.dashboard');
    }
}

