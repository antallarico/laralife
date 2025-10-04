<?php

namespace App\Http\Controllers\Allenamento;

use App\Http\Controllers\Controller;

class DashboardAllenamentoController extends Controller
{
    public function index()
    {
        return view('allenamento.dashboard');
    }
}
