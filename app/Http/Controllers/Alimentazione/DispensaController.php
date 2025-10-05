<?php

namespace App\Http\Controllers\Alimentazione;

use App\Http\Controllers\Controller;
use App\Models\Alimento;
use App\Models\AlimentoDispensa;
use App\Services\Nutrizione\PantryService;
use App\Enums\Unita;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Carbon\Carbon;

class DispensaController extends Controller
{
    public function index(Request $request): View
    {
        $q          = trim((string)$request->get('q', ''));
        $categoria  = (string)$request->get('categoria', '');
        $posizione  = (string)$request->get('posizione', '');
        $entroGiorni = (int)$request->get('entro_giorni', 0);

        $query = AlimentoDispensa::query()
            ->select('alimenti_dispensa.*')
            ->join('alimenti', 'alimenti.id', '=', 'alimenti_dispensa.alimento_id')
            ->with('alimento');

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function($w) use ($like) {
                $w->where('alimenti.nome', 'LIKE', $like)
                  ->orWhere('alimenti.marca', 'LIKE', $like)
                  ->orWhere('alimenti.distributore', 'LIKE', $like);
            });
        }
        if ($categoria !== '') {
            $query->where('alimenti.categoria', $categoria);
        }
        if ($posizione !== '') {
            $query->where('alimenti_dispensa.posizione', $posizione);
        }
        if ($entroGiorni > 0) {
            $limit = Carbon::today()->addDays($entroGiorni)->toDateString();
            $query->whereNotNull('alimenti_dispensa.scadenza')
                  ->where('alimenti_dispensa.scadenza', '<=', $limit);
        }

        $righe = $query->orderBy('alimenti.nome')
            ->orderByRaw('LOWER(alimenti.nome)')
            ->orderByRaw('LOWER(alimenti.marca)')
            ->paginate(500)->withQueryString();

        // Per i filtri (dropdown)
        $categorie = Alimento::whereNotNull('categoria')->distinct()->orderBy('categoria')->pluck('categoria');
        $posizioni = AlimentoDispensa::whereNotNull('posizione')->distinct()->orderBy('posizione')->pluck('posizione');

        return view('alimentazione.dispensa.index', compact('righe','categorie','posizioni','q','categoria','posizione','entroGiorni'));
    }

    public function create(): View
    {
        $alimenti = Alimento::query()
            ->orderByRaw('LOWER(nome)')
            ->orderByRaw('LOWER(marca)')
            ->get(['id','nome','marca','unita_preferita']);
        return view('alimentazione.dispensa.create', compact('alimenti'));
    }
    
    public function store(Request $request): RedirectResponse
    {
        $alimentoId = (int) $request->input('alimento_id');
        $quantita   = (int) $request->input('quantita');
        $unitaStr   = (string) $request->input('unita', 'g');

        if ($alimentoId <= 0 || $quantita <= 0 || !in_array($unitaStr, ['g','ml','u'], true)) {
            return back()->with('err', 'Dati mancanti o non validi.')->withInput();
        }

        PantryService::aggiungiStock(
            $alimentoId,
            $quantita,
            Unita::from($unitaStr),
            $request->filled('n_pezzi') ? (int)$request->input('n_pezzi') : null,
            $request->input('posizione', 'Dispensa'),
            $request->input('scadenza') ?: null,
            $request->input('note') ?: null
        );

        return redirect()->route('alimentazione.dispensa.index')->with('ok', 'Dispensa aggiornata.');
    }

    public function update(Request $request, AlimentoDispensa $dispensa): RedirectResponse
    {
        $data = $request->validate([
            'posizione'         => ['nullable','string','max:100'],
            'scadenza'          => ['nullable','date'],
            'note'              => ['nullable','string','max:1000'],
            'n_pezzi'           => ['nullable','integer','min:0','max:100000'],
            'quantita_per_pezzo' => ['nullable','integer','min:0','max:999999'], // RINOMINATO
            'quantita_parziale' => ['nullable','integer','min:0','max:999999'],
        ]);

        // normalizza stringhe vuote a null
        foreach ($data as $k => $v) {
            if ($v === '') $data[$k] = null;
        }

        $dispensa->fill($data)->save();

        return back()->with('ok', 'Riga dispensa aggiornata.');
    }

    public function destroy(AlimentoDispensa $dispensa): RedirectResponse
    {
        $dispensa->delete();
        return back()->with('ok', 'Riga dispensa eliminata.');
    }

    /** Azzera il parziale aperto (chiude il pezzo) */
    public function chiudiParziale(int $id): RedirectResponse
    {
        PantryService::chiudiParziale($id);
        return back()->with('ok', 'Pezzo aperto chiuso.');
    }
}