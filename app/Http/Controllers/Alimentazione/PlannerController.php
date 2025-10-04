<?php

namespace App\Http\Controllers\Alimentazione;

use App\Http\Controllers\Controller;
use App\Models\Planning;
use App\Models\AlimentoDispensa;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlannerController extends Controller
{
    public function index(Request $request, int $offset = 0): View
	{
		// Se l'offset arriva da querystring, prendi quello
		$offset = (int) $request->get('offset', $offset);

		$start = \Carbon\Carbon::now()->startOfWeek()->addWeeks($offset);
		$end   = (clone $start)->addDays(13);

		// Carica pianificazioni ALIMENTAZIONE (polimorfico su AlimentoDispensa)
		$items = \App\Models\Planning::query()
			->alimentazione() // <-- se NON hai lo scope, vedi nota sotto
			->whereBetween('data', [$start->toDateString(), $end->toDateString()])
			->with('riferibile.alimento')
			->orderBy('data')->orderBy('ordine')
			->get();
	
		$planner  = $items->groupBy(fn ($p) => $p->data->toDateString());
		$dispensa = \App\Models\AlimentoDispensa::with('alimento')
			->orderByRaw('LOWER(posizione) IS NULL')
			->orderByRaw('LOWER(posizione)')
			->get();

		return view('alimentazione.planner.index', compact('start','end','offset','planner','dispensa'));
	}



    public function store(Request $request)
    {
		//dd($request->only('data','dispensa_id','tipo_pasto','quantita'));
		// 1) LOG: cosa arriva dal form
    //Log::info('planner.store request', $request->only('data','dispensa_id','tipo_pasto','quantita','ordine','note'));

        // validazione minima (puoi alleggerirla se vuoi)
        $data = $request->validate([
            'data'        => ['required','date'],
            'dispensa_id' => ['required','exists:alimenti_dispensa,id'],
            'tipo_pasto'  => ['nullable','string','max:50'],
            'quantita'    => ['nullable','integer','min:1'],
            'ordine'      => ['nullable','integer','min:0'],
            'note'        => ['nullable','string','max:500'],
        ]);

        // 2) LOG: cosa resta dopo la validate
    //Log::info('planner.store validated', $data);

    // (opzionale) default se empty
    //$data['tipo_pasto'] = $data['tipo_pasto'] ?? 'altro';

    // 3) CREA la riga
    //$row = \App\Models\
	Planning::create([
            'data'            => $data['data'],
            'ordine'          => $data['ordine'] ?? 0,
            'riferibile_type' => AlimentoDispensa::class,
            'riferibile_id'   => (int)$data['dispensa_id'],
            'tipo_pasto'      => $data['tipo_pasto'] ?? null,
            'quantita'        => $data['quantita'] ?? null,
            'note'            => $data['note'] ?? null,
        ]);
		// 4) LOG: cosa è stato scritto
    //Log::info('planner.store created', $row->only('id','data','riferibile_type','riferibile_id','tipo_pasto','quantita','ordine'));
        return back()->with('ok','Pasto pianificato.');
    }

    public function destroy(int $id)
    {
        // opzionale: garantisci che sia una riga di Alimentazione
        $row = Planning::alimentazione()->findOrFail($id);
        $row->delete();

        return back()->with('ok','Pianificazione rimossa.');
    }
	
	public function reorder(Request $request)
{
    $data = $request->validate([
        'id'         => ['required','integer','exists:planning,id'],
        'date'       => ['required','date'],
        'tipo_pasto' => ['nullable','string','max:50'],
        'order'      => ['required','integer','min:0'],
    ]);

    /** @var \App\Models\Planning $row */
    $row = Planning::alimentazione()->findOrFail($data['id']);

    $newDate  = \Carbon\Carbon::parse($data['date'])->toDateString();
    $newMeal  = $data['tipo_pasto'] ?? null;
    $newOrder = (int)$data['order'];

    DB::transaction(function () use ($row, $newDate, $newMeal, $newOrder) {
        // normalizza "old" (slot di origine)
        $oldDate = $row->data->toDateString();
        $oldMeal = $row->tipo_pasto;

        // 1) Togli l'elemento dalla lista di origine e ricompatta
        $origin = Planning::alimentazione()
            ->whereDate('data', $oldDate)
            ->where(function($q) use ($oldMeal) {
                $oldMeal === null ? $q->whereNull('tipo_pasto') : $q->where('tipo_pasto', $oldMeal);
            })
            ->where('id','<>',$row->id)
            ->orderBy('ordine')
            ->get();

        foreach ($origin as $i => $r) {
            $r->update(['ordine' => $i]);
        }

        // 2) Inserisci nella lista di destinazione alla posizione richiesta
        $dest = Planning::alimentazione()
            ->whereDate('data', $newDate)
            ->where(function($q) use ($newMeal) {
                $newMeal === null ? $q->whereNull('tipo_pasto') : $q->where('tipo_pasto', $newMeal);
            })
            ->orderBy('ordine')
            ->get();

        // ricrea l’array con l’elemento inserito in $newOrder
        $destIds = $dest->pluck('id')->all();
        array_splice($destIds, max(0,$newOrder), 0, [$row->id]);

        // aggiorna la riga spostata (data/pasto/ordine provvisorio)
        $row->update([
            'data'       => $newDate,
            'tipo_pasto' => $newMeal,
            'ordine'     => $newOrder,
        ]);

        // riposiziona tutti con ordine 0..N
        foreach (array_values($destIds) as $i => $id) {
            Planning::whereKey($id)->update(['ordine' => $i]);
        }
    });

    return response()->json(['ok' => true]);
	}
	
	public function updateQuantita(\Illuminate\Http\Request $request, int $id)
	{
    $row = \App\Models\Planning::alimentazione()->findOrFail($id);

    $data = $request->validate([
        'quantita' => ['required','integer','min:0','max:999999'],
    ]);

    $row->update(['quantita' => (int)$data['quantita']]);

    // Se la richiesta è fetch/ajax, rispondi json, altrimenti torna indietro
    if ($request->wantsJson()) {
        return response()->json(['ok' => true]);
    }
    return back()->with('ok','Quantità aggiornata.');
	}

}
