<?php

namespace App\Http\Controllers\Alimentazione;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alimentazione\StoreConsumoRequest;
use App\Models\Alimento;
use App\Models\AlimentoPasto;
use App\Models\Planning;
use App\Services\Nutrizione\PantryService;
use App\Enums\Unita;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PastiController extends Controller
{
    /** Mostra la pagina "oggi" (o data passata) con slot pasti e consumi */
    public function oggi(?string $data = null): View
    {
        $day = $data ? Carbon::parse($data) : Carbon::today();

        // crea slot base se mancanti
        $this->ensureMealSlotsForDate($day->toDateString());

        // Slot del giorno con consumi registrati
        $slots = Planning::alimentazione()
            ->delGiorno($day)
            ->with(['alimentiPasti.alimento'])
            ->orderBy('ordine')
            ->get();

        // Totali per pasto (sui consumi registrati)
        $totali = $slots->mapWithKeys(function ($slot) {
            $sum = [
                'kcal' => 0.0, 'carbo_g' => 0.0, 'prot_g' => 0.0, 'grassi_g' => 0.0
            ];
            foreach ($slot->alimentiPasti as $r) {
                $sum['kcal']     += (float)$r->kcal;
                $sum['carbo_g']  += (float)$r->carbo_g;
                $sum['prot_g']   += (float)$r->prot_g;
                $sum['grassi_g'] += (float)$r->grassi_g;
            }
            foreach ($sum as &$v) $v = round($v, 2);
            return [$slot->id => $sum];
        });

        // Alimenti per il form "aggiungi consumo"
        $alimenti = Alimento::orderBy('nome')->get(['id','nome','unita_preferita']);

        // Pianificato del giorno (da planner) raggruppato per tipo_pasto (NULL/'' => 'libero')
        $planned = Planning::alimentazione()
            ->whereDate('data', $day->toDateString())
            ->whereNotNull('riferibile_type')          // esclude gli slot "vuoti"
            ->with('riferibile.alimento')               // stock + alimento
            ->orderBy('tipo_pasto')->orderBy('ordine')
            ->get();

        $plannedByTipo = $planned->groupBy(function ($p) {
            $t = $p->tipo_pasto;
            return ($t === null || $t === '') ? 'libero' : $t;
        });

        return view('alimentazione.oggi', compact('day','slots','totali','alimenti'))
            ->with('plannedByTipo', $plannedByTipo);
    }

    /** Registra un consumo su uno slot planning */
    public function store(StoreConsumoRequest $request, int $planningId): RedirectResponse
    {
        $validated = $request->validate([
            'alimento_id'      => ['required','exists:alimenti,id'],
            'quantita'         => ['required','integer','min:1','max:1000000'],
            'unita'            => ['required','in:g,ml,u'],
            'scarica_dispensa' => ['sometimes','boolean'],
        ]);

        $slot = Planning::alimentazione()->findOrFail($planningId);
        $alimento = Alimento::findOrFail($validated['alimento_id']);

        // calcolo nutrienti snapshot per la quantità/unita date
        $kcal = $carbo = $prot = $grassi = 0;
        PantryService::calcolaNutrienti($alimento, (int)$validated['quantita'], Unita::from($validated['unita']), $kcal, $carbo, $prot, $grassi);

        $row = AlimentoPasto::create([
            'planning_id'  => $slot->id,
            'alimento_id'  => $alimento->id,
            'quantita'     => (int)$validated['quantita'],
            'unita'        => Unita::from($validated['unita']),
            'scarica_dispensa' => (bool)($validated['scarica_dispensa'] ?? true),
            'kcal'         => (int)round($kcal),
            'carbo_g'      => (int)round($carbo),
            'prot_g'       => (int)round($prot),
            'grassi_g'     => (int)round($grassi),
        ]);

        // scarico dispensa di default (se non deselezionato)
        if ($row->scarica_dispensa) {
            PantryService::scaricaDispensa($alimento->id, (int)$row->quantita, $row->unita);
        }

        return back()->with('ok', 'Consumo aggiunto.');
    }

    /** Elimina una riga di consumo (senza ripristino dispensa) */
    public function destroy(int $id): RedirectResponse
    {
        $row = AlimentoPasto::findOrFail($id);
        $row->delete();
        return back()->with('ok','Riga eliminata.');
    }

    /** Elimina riga di consumo e ripristina dispensa */
    public function destroyAndRestore(int $id): RedirectResponse
    {
        $row = AlimentoPasto::findOrFail($id);

        PantryService::aggiungiStock(
            $row->alimento_id,
            (int)$row->quantita,
            $row->unita instanceof \BackedEnum ? $row->unita : Unita::from($row->unita->value ?? $row->unita),
            null
        );

        $row->delete();

        return back()->with('ok','Riga eliminata e dispensa ripristinata.');
    }

    /** Crea gli slot base del giorno se mancanti */
    private function ensureMealSlotsForDate(string $date): void
    {
        $ordine = 0;
        foreach (['colazione','pranzo','cena','spuntino','libero'] as $tipo) {
            \App\Models\Planning::firstOrCreate(
                ['data' => $date, 'tipo_pasto' => $tipo],
                ['ordine' => $ordine++]
            );
        }
    }
	
	public function storico(\Illuminate\Http\Request $request): \Illuminate\View\View
	{
    $view   = $request->query('view', 'settimana'); // 'settimana' | 'mese'
    $offset = (int) $request->query('offset', 0);

    if ($view === 'mese') {
        $start = \Carbon\Carbon::now()->startOfMonth()->addMonths($offset);
        $end   = (clone $start)->endOfMonth();
    } else {
        $start = \Carbon\Carbon::now()->startOfWeek()->addWeeks($offset);
        $end   = (clone $start)->addDays(6);
        $view  = 'settimana';
    }

    // Carica consumi (alimenti_pasti) nel range, con alimento e planning (per data/tipo_pasto)
    $items = \App\Models\AlimentoPasto::with(['alimento', 'planning'])
        ->whereHas('planning', fn($q) => $q->whereBetween('data', [$start->toDateString(), $end->toDateString()]))
        ->get()
        ->sortBy([
            fn($r) => $r->planning->data->toDateString(),
            fn($r) => $r->planning->tipo_pasto ?? 'libero',
        ]);

    // Group per giorno e totali giorno
    $byDate = $items->groupBy(fn($r) => $r->planning->data->toDateString());
    $totalsByDate = $byDate->map(fn($rows) => [
        'kcal'     => (int) round($rows->sum('kcal')),
        'carbo_g'  => (int) round($rows->sum('carbo_g')),
        'prot_g'   => (int) round($rows->sum('prot_g')),
        'grassi_g' => (int) round($rows->sum('grassi_g')),
    ]);

    return view('alimentazione.storico', [
        'view'          => $view,
        'offset'        => $offset,
        'start'         => $start,
        'end'           => $end,
        'byDate'        => $byDate,
        'totalsByDate'  => $totalsByDate,
    ]);
	}

	
}
