<?php

namespace App\Http\Controllers\Alimentazione;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alimentazione\StoreConsumoRequest;
use App\Models\Alimento;
use App\Models\AlimentoPasto;
use App\Models\Planning;
use App\Models\AlimentoDispensa;
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

        // 🔍 DEBUG 4: Caricamento pagina oggi
        \Log::info('=== CARICAMENTO PAGINA OGGI ===', [
            'data_richiesta' => $day->toDateString(),
        ]);

        // crea slot base se mancanti
        $this->ensureMealSlotsForDate($day->toDateString());

        // ✅ FIX: Carica SOLO gli slot pasto (riferibile_type NULL), non le voci pianificate
        $slots = Planning::query()
            ->whereDate('data', $day->toDateString())
            ->whereNull('riferibile_type')  // ← Solo slot, non voci pianificate
            ->with(['alimentiPasti.alimento'])
            ->orderBy('ordine')
            ->get();

        // 🔍 DEBUG 5: Slot caricati
        \Log::info('Slot caricati dalla query', [
            'numero_slot' => $slots->count(),
            'slot_details' => $slots->map(fn($s) => [
                'id' => $s->id,
                'tipo_pasto' => $s->tipo_pasto,
                'riferibile_type' => $s->riferibile_type,
                'num_consumi' => $s->alimentiPasti->count(),
                'consumi_ids' => $s->alimentiPasti->pluck('id')->toArray(),
            ])->toArray(),
        ]);
        
        // 🔍 DEBUG 6: Tutti i consumi nel DB per oggi
        $tuttiConsumi = \App\Models\AlimentoPasto::with('planning')
            ->whereHas('planning', fn($q) => $q->whereDate('data', $day->toDateString()))
            ->get();
        
        \Log::info('Tutti i consumi nel DB per oggi', [
            'totale_consumi' => $tuttiConsumi->count(),
            'dettagli' => $tuttiConsumi->map(fn($c) => [
                'alimento_pasto_id' => $c->id,
                'planning_id' => $c->planning_id,
                'planning_tipo_pasto' => $c->planning->tipo_pasto ?? 'NULL',
                'planning_riferibile_type' => $c->planning->riferibile_type ?? 'NULL',
                'alimento' => $c->alimento->nome,
            ])->toArray(),
        ]);

        // Totali per pasto (sui consumi registrati)
        $totali = $slots->mapWithKeys(function ($slot) {
            $sum = [
                'kcal' => 0, 'carbo_g' => 0, 'prot_g' => 0, 'grassi_g' => 0
            ];
            foreach ($slot->alimentiPasti as $r) {
                $sum['kcal']     += (int)$r->kcal;
                $sum['carbo_g']  += (int)$r->carbo_g;
                $sum['prot_g']   += (int)$r->prot_g;
                $sum['grassi_g'] += (int)$r->grassi_g;
            }
            return [$slot->id => $sum];
        });

        // Alimenti per il form "aggiungi consumo"
        $alimenti = Alimento::orderBy('nome')->get(['id','nome','unita_preferita']);

        // Info dispensa per ogni alimento (per mostrare disponibilità parziale/chiusa)
        $dispenseInfo = AlimentoDispensa::with('alimento')
            ->get()
            ->keyBy('alimento_id');

        // ✅ FIX: Pianificato del giorno - carica le VOCI pianificate (riferibile_type popolato)
        $planned = Planning::query()
            ->whereDate('data', $day->toDateString())
            ->whereNotNull('riferibile_type')  // ← Solo voci pianificate
            ->where('riferibile_type', AlimentoDispensa::class)  // ← Solo alimentazione
            ->with('riferibile.alimento')
            ->orderBy('tipo_pasto')->orderBy('ordine')
            ->get();

        $plannedByTipo = $planned->groupBy(function ($p) {
            $t = $p->tipo_pasto;
            return ($t === null || $t === '') ? 'libero' : $t;
        });

        return view('alimentazione.oggi', compact('day','slots','totali','alimenti','dispenseInfo'))
            ->with('plannedByTipo', $plannedByTipo);
    }

    /** Registra un consumo su uno slot planning */
    public function store(StoreConsumoRequest $request, int $planningId): RedirectResponse
    {
        // 🔍 DEBUG 1: Cosa arriva al controller
        \Log::info('=== INIZIO store() ===', [
            'planning_id_ricevuto' => $planningId,
            'alimento_id' => $request->input('alimento_id'),
            'quantita' => $request->input('quantita'),
        ]);
        
        $validated = $request->validate([
            'alimento_id'      => ['required','exists:alimenti,id'],
            'quantita'         => ['required','integer','min:1','max:1000000'],
            'unita'            => ['required','in:g,ml,u'],
            'scarica_dispensa' => ['sometimes','boolean'],
            'modalita_scarico' => ['nullable','in:parziale,nuovo,chiusi'],
        ]);

        // Trova il planning passato (può essere TIPO 1 slot o TIPO 2 pianificato)
        $planning = Planning::findOrFail($planningId);
        
        // 🔍 DEBUG 2: Che tipo di planning è?
        \Log::info('Planning ricevuto', [
            'id' => $planning->id,
            'data' => $planning->data->toDateString(),
            'tipo_pasto' => $planning->tipo_pasto,
            'riferibile_type' => $planning->riferibile_type,
            'riferibile_id' => $planning->riferibile_id,
            'ordine' => $planning->ordine,
        ]);
        
        // ✅ FIX: Determina lo SLOT del pasto corretto
        if ($planning->tipo_pasto && $planning->riferibile_type === null) {
            // È già uno slot pasto (TIPO 1) → usa direttamente
            $slot = $planning;
            \Log::info('È già uno SLOT', ['slot_id' => $slot->id]);
        } else {
            // È una voce pianificata (TIPO 2) → trova lo slot corretto
            $tipoPasto = $planning->tipo_pasto ?? 'libero';
            $data = $planning->data->toDateString();
            
            \Log::info('È voce PIANIFICATA, cerco slot', [
                'data' => $data,
                'tipo_pasto' => $tipoPasto,
            ]);
            
            // Trova o crea lo slot del pasto per questo giorno
            $slot = Planning::firstOrCreate(
                ['data' => $data, 'tipo_pasto' => $tipoPasto],
                ['ordine' => 0]
            );
            
            \Log::info('Slot trovato/creato', [
                'slot_id' => $slot->id,
                'era_esistente' => $slot->wasRecentlyCreated ? 'NO (creato ora)' : 'SI',
            ]);
        }
        
        $alimento = Alimento::findOrFail($validated['alimento_id']);

        // Calcolo nutrienti
        $nutrienti = PantryService::calcolaNutrienti(
            $alimento, 
            (int)$validated['quantita'], 
            Unita::from($validated['unita'])
        );

        // Crea la riga consumo associata allo SLOT
        $row = AlimentoPasto::create([
            'planning_id'      => $slot->id,  // ✅ Ora usa lo slot corretto
            'alimento_id'      => $alimento->id,
            'quantita'         => (int)$validated['quantita'],
            'unita'            => Unita::from($validated['unita']),
            'scarica_dispensa' => (bool)($validated['scarica_dispensa'] ?? true),
            'kcal'             => $nutrienti['kcal'],
            'carbo_g'          => $nutrienti['carbo_g'],
            'prot_g'           => $nutrienti['prot_g'],
            'grassi_g'         => $nutrienti['grassi_g'],
        ]);
        
        // 🔍 DEBUG 3: Consumo creato
        \Log::info('AlimentoPasto creato', [
            'alimento_pasto_id' => $row->id,
            'planning_id_associato' => $row->planning_id,
            'alimento' => $alimento->nome,
            'quantita' => $row->quantita,
            'kcal' => $row->kcal,
        ]);

        // Scarico dispensa con modalità intelligente
        if ($row->scarica_dispensa) {
            $modalita = $validated['modalita_scarico'] ?? null;
            
            // ✅ Per TUTTE le unità (g, ml, u): gestione modalità scarico
            // Se modalità non specificata, usa default intelligente
            if ($modalita === null) {
                // Controlla se esiste un parziale aperto
                $stock = \App\Models\AlimentoDispensa::where('alimento_id', $alimento->id)->first();
                $hasParziale = $stock && $stock->quantita_parziale > 0;
                $modalita = $hasParziale ? 'parziale' : 'nuovo';
            }
            
            $usaParziale = ($modalita === 'parziale');
            $apriNuovo = ($modalita === 'nuovo');
            
            $risultato = PantryService::scaricaDispensa(
                $alimento->id, 
                (int)$row->quantita, 
                $row->unita,
                $usaParziale,
                $apriNuovo
            );
            
            $scaricato = $risultato['scaricato'];
            
            // Feedback dettagliato
            if ($scaricato < (int)$row->quantita) {
                return back()->with('warning', 
                    "Consumo aggiunto. Attenzione: scaricati solo {$scaricato} dalla dispensa (disponibilità insufficiente)."
                );
            }
            
            // Mostra da dove ha scaricato (opzionale, per debug/trasparenza)
            if ($risultato['da_parziale'] > 0 && $risultato['da_chiusi'] > 0) {
                return back()->with('ok', 
                    "Consumo aggiunto. Scaricati {$risultato['da_parziale']} da aperto + {$risultato['da_chiusi']} da nuovo pezzo."
                );
            }
        }

        \Log::info('=== FINE store() - SUCCESS ===');
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

        // Ripristina in dispensa solo se era stato scaricato
        if ($row->scarica_dispensa) {
            PantryService::aggiungiStock(
                $row->alimento_id,
                (int)$row->quantita,
                $row->unita instanceof \BackedEnum ? $row->unita : Unita::from($row->unita->value ?? $row->unita)
            );
        }

        $row->delete();

        return back()->with('ok','Riga eliminata e dispensa ripristinata.');
    }

    /** Crea gli slot base del giorno se mancanti */
    private function ensureMealSlotsForDate(string $date): void
    {
        $ordine = 0;
        foreach (['colazione','pranzo','cena','spuntino','libero'] as $tipo) {
            Planning::firstOrCreate(
                ['data' => $date, 'tipo_pasto' => $tipo],
                ['ordine' => $ordine++]
            );
        }
    }
    
    public function storico(\Illuminate\Http\Request $request): \Illuminate\View\View
    {
        $view   = $request->query('view', 'settimana');
        $offset = (int) $request->query('offset', 0);

        if ($view === 'mese') {
            $start = Carbon::now()->startOfMonth()->addMonths($offset);
            $end   = (clone $start)->endOfMonth();
        } else {
            $start = Carbon::now()->startOfWeek()->addWeeks($offset);
            $end   = (clone $start)->addDays(6);
            $view  = 'settimana';
        }

        $items = AlimentoPasto::with(['alimento', 'planning'])
            ->whereHas('planning', fn($q) => $q->whereBetween('data', [$start->toDateString(), $end->toDateString()]))
            ->get()
            ->sortBy([
                fn($r) => $r->planning->data->toDateString(),
                fn($r) => $r->planning->tipo_pasto ?? 'libero',
            ]);

        $byDate = $items->groupBy(fn($r) => $r->planning->data->toDateString());
        $totalsByDate = $byDate->map(fn($rows) => [
            'kcal'     => (int) $rows->sum('kcal'),
            'carbo_g'  => (int) $rows->sum('carbo_g'),
            'prot_g'   => (int) $rows->sum('prot_g'),
            'grassi_g' => (int) $rows->sum('grassi_g'),
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