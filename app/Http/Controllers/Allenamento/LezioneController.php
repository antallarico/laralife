<?php

namespace App\Http\Controllers\Allenamento;

use App\Http\Controllers\Controller;
use App\Models\Lezione;
use Illuminate\Http\Request;

class LezioneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
	{
		$lezioni = Lezione::orderBy('titolo')->get();
		return view('allenamento.lezioni.index', compact('lezioni'));
	}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('allenamento.lezioni.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
	{
		$request->validate([
			'titolo' => 'required|string|max:255',
			'descrizione' => 'nullable|string',
			'link' => 'nullable|url',
			'durata' => 'nullable|integer|min:1',
			'tipo' => 'nullable|string|max:100',
			'piattaforma' => 'nullable|string|max:100',
		]);

		Lezione::create($request->all());

		return redirect()->route('lezioni.index')->with('success', 'Lezione salvata con successo.');
	}


    /**
     * Display the specified resource.
     */
    public function show(Lezione $lezione)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lezione $lezione)
    {
        return view('allenamento.lezioni.edit', compact('lezione'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lezione $lezione)
    {
        $request->validate([
			'titolo' => 'required|string|max:255',
			'descrizione' => 'nullable|string',
			'link' => 'nullable|url',
			'durata' => 'nullable|integer|min:1',
			'tipo' => 'nullable|string|max:100',
			'piattaforma' => 'nullable|string|max:100',
		]);

		$lezione->update($request->all());

		return redirect()->route('lezioni.index')->with('success', 'Lezione aggiornata con successo.');
	}
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lezione $lezione)
    {
        $lezione->delete();
		return redirect()->route('lezioni.index')->with('success', 'Lezione eliminata.');
    }
}
