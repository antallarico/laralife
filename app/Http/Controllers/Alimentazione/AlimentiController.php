<?php

namespace App\Http\Controllers\Alimentazione;

use App\Http\Controllers\Controller;
use App\Models\Alimento;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AlimentiController extends Controller
{
    public function index(): View
    {
		$alimenti = Alimento::orderBy('nome')->paginate(250);
        return view('alimentazione.alimenti.index', compact('alimenti'));
    }

    public function create(): View
    {
        $alimento = new \App\Models\Alimento([
			'base_nutrizionale' => '100g',
			'unita_preferita'   => 'g',
		]);
        return view('alimentazione.alimenti.create', compact('alimento'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Alimento::create($data);

        return redirect()->route('alimentazione.alimenti.index')->with('ok', 'Alimento creato.');
    }

    public function edit(Alimento $alimento): View
    {
        return view('alimentazione.alimenti.edit', compact('alimento'));
    }

    public function update(Request $request, Alimento $alimento): RedirectResponse
    {
        $data = $this->validated($request);
        $alimento->update($data);
		//$alimento->forceFill($data)->saveOrFail();
        return redirect()->route('alimentazione.alimenti.index')->with('ok', 'Alimento aggiornato.');
    }

    public function destroy(Alimento $alimento): RedirectResponse
    {
        $alimento->delete();
        return redirect()->route('alimentazione.alimenti.index')->with('ok', 'Alimento eliminato.');
    }

    private function validated(Request $request): array
    {
        // Valida solo i campi effettivamente presenti nella tua tabella
        return $request->validate([
            'nome'              => ['required','string','max:255'],
            'marca'             => ['nullable','string','max:255'],
			'distributore'  	=> ['nullable','string','max:255'],
			'prezzo_medio'  	=> ['nullable','numeric','min:0','max:999999.99'],
			'categoria'     	=> ['nullable','string','max:255'],
			'note'          	=> ['nullable','string','max:2000'],           
            'base_nutrizionale' => ['required','in:100g,100ml,unit'],
            'unita_preferita'   => ['required','in:g,ml,u'],
            'kcal_ref'          => ['nullable','integer','min:0','max:65535'],
            'carbo_ref_g'       => ['nullable','integer','min:0','max:65535'],
            'prot_ref_g'        => ['nullable','integer','min:0','max:65535'],
            'grassi_ref_g'      => ['nullable','integer','min:0','max:65535'],
        ]);
    }
}
