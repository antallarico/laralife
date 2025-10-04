<?php
namespace App\Http\Controllers\Allenamento;

use App\Http\Controllers\Controller;
use App\Models\Planning;
use App\Models\Lezione;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlannerController extends Controller
{
    public function index(Request $request, $offset = 0)
	{
    $offset = (int) $offset;

    $start = \Carbon\Carbon::now()->startOfWeek()->addWeeks($offset);
    $giorni = collect(range(0,13))->map(fn($i) => (clone $start)->addDays($i));

    $items = \App\Models\Planning::allenamento()
        ->whereBetween('data', [$giorni->first()->toDateString(), $giorni->last()->toDateString()])
        ->with('riferibile') // Lezione
        ->orderBy('data')->orderBy('ordine')
        ->get();

    $planning = $items; // il tuo Blade fa i where sul Collection

    $lezioni = \App\Models\Lezione::orderByRaw('LOWER(titolo)')->get(['id','titolo','durata']);

    return view('allenamento.planner.index', compact('offset','giorni','planning','lezioni'));
	}


    public function store(Request $request)
    {
        $data = $request->validate([
            'data'       => ['required','date'],
            'lezione_id' => ['required','exists:lezioni,id'],      // tabella lezioni
            'ordine'     => ['nullable','integer','min:0','max:1000'],
            'note'       => ['nullable','string','max:500'],
        ]);

        // ordine “in coda” al giorno
        $nextOrdine = Planning::allenamento()
            ->whereDate('data', $data['data'])
            ->max('ordine');
        $nextOrdine = is_null($nextOrdine) ? 0 : $nextOrdine + 1;

        Planning::create([
            'data'            => $data['data'],
            'ordine'          => $data['ordine'] ?? $nextOrdine,
            'riferibile_type' => Lezione::class,
            'riferibile_id'   => (int)$data['lezione_id'],
            'note'            => $data['note'] ?? null,
            // tipo_pasto/quantita restano NULL (non usati per allenamento)
        ]);

        return back()->with('ok','Allenamento pianificato.');
    }

    public function destroy(int $id)
    {
        $row = Planning::allenamento()->findOrFail($id);
        $row->delete();

        return back()->with('ok','Pianificazione rimossa.');
    }
	
	public function reorder(Request $request)
{
    $payload = $request->validate([
        'data' => ['required','date'],           // es. "2025-09-18"
        'ids'  => ['required','array'],
        'ids.*'=> ['integer','distinct','exists:planning,id'],
    ]);

    $date = \Carbon\Carbon::parse($payload['data'])->toDateString();
    $ids  = array_values($payload['ids']); // mantieni l'ordine passato dal client

    DB::transaction(function () use ($date, $ids) {
        // assicurati che stiamo toccando solo righe di ALLENAMENTO
        $rows = \App\Models\Planning::allenamento()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($ids as $i => $id) {
            if (!isset($rows[$id])) continue;
            $rows[$id]->update([
                'data'   => $date,
                'ordine' => $i,     // 0..N nell’ordine della lista
            ]);
        }
    });

    return response()->json(['ok' => true]);
	}
	

	public function esecuzioneOggi()
	{
    $oggi = now()->toDateString();

    // Pianificato di oggi (polimorfico: Lezione)
    $planning = \App\Models\Planning::allenamento()
        ->with('riferibile')               // = Lezione
        ->whereDate('data', $oggi)
        ->orderBy('ordine')
        ->get();

    // Esecuzioni già registrate oggi (keyBy su riferimento_id = lezione_id)
    $esecuzioni = \App\Models\Esecuzione::where('modulo','allenamento')
        ->where('data',$oggi)
        ->get()
        ->keyBy('riferimento_id');

    return view('allenamento.esecuzione_oggi', compact('planning','esecuzioni','oggi'));
	}

	public function salvaEsecuzione(\Illuminate\Http\Request $r)
	{
    $data = now()->toDateString();

    $r->validate([
        'lezione_id'       => 'required|integer|exists:lezioni,id',
        'minuti_effettivi' => 'required|integer|min:0|max:1440',
        'stato'            => 'required|in:completata,saltata,in_corso',
        'note'             => 'nullable|string|max:500',
    ]);

    \App\Models\Esecuzione::updateOrCreate(
        ['data' => $data, 'modulo' => 'allenamento', 'riferimento_id' => $r->lezione_id],
        $r->only(['minuti_effettivi','stato','note'])
    );

    return back()->with('success','Esecuzione aggiornata');
	}

	
	public function esecuzioneStorico(\Illuminate\Http\Request $request)
	{
    $view   = $request->query('view', 'settimana'); // 'settimana' o 'mese'
    $offset = (int) $request->query('offset', 0);

    $start = $view === 'mese'
        ? \Carbon\Carbon::now()->startOfMonth()->addMonths($offset)
        : \Carbon\Carbon::now()->startOfWeek()->addWeeks($offset);

    $end = $view === 'mese'
        ? $start->copy()->endOfMonth()
        : $start->copy()->addDays(6);

    $esecuzioni = \App\Models\Esecuzione::with('lezione')
        ->where('modulo','allenamento')
        ->whereBetween('data', [$start->toDateString(), $end->toDateString()])
        ->orderBy('data')->orderBy('created_at')
        ->get()
        ->groupBy('data')
		;

    return view('allenamento.esecuzione_storico', compact('esecuzioni','start','end','view','offset'));
	}

}
