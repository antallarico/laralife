@extends('layouts.app')

@section('content')
<h1>Alimentazione — {{ $day->isoFormat('dddd D MMMM YYYY') }}</h1>

@php
  $ORDINE_PASTI = ['colazione','pranzo','cena','spuntino','libero'];
  // normalizza gli slot per tipo: NULL/'' → 'libero'
  $slotsByTipo = $slots->keyBy(function($s){
    $t = $s->tipo_pasto;
    return ($t === null || $t === '') ? 'libero' : $t;
  });

  // TOT GIORNO (somma dei totali per slot, basati sui consumi registrati)
  $dayTotals = ['kcal'=>0,'carbo_g'=>0,'prot_g'=>0,'grassi_g'=>0];
  foreach ($slots as $s) {
    $t = $totali[$s->id] ?? null;
    if ($t) {
      $dayTotals['kcal']     += (float)($t['kcal']     ?? 0);
      $dayTotals['carbo_g']  += (float)($t['carbo_g']  ?? 0);
      $dayTotals['prot_g']   += (float)($t['prot_g']   ?? 0);
      $dayTotals['grassi_g'] += (float)($t['grassi_g'] ?? 0);
    }
  }
  foreach ($dayTotals as &$v) { $v = (int) round($v); } unset($v);
@endphp

{{-- Box riassunto totale giornata --}}
<div class="alert alert-secondary d-flex flex-wrap align-items-center gap-3">
  <strong class="me-2 mb-0">Totale giornata</strong>
  <span>Cal: {{ $dayTotals['kcal'] }}</span>
  <span>Carb: {{ $dayTotals['carbo_g'] }}</span>
  <span>Prot: {{ $dayTotals['prot_g'] }}</span>
  <span>Gras: {{ $dayTotals['grassi_g'] }}</span>
</div>

@foreach($ORDINE_PASTI as $tipo)
  @php /** @var \App\Models\Planning|null $slot */ $slot = $slotsByTipo[$tipo] ?? null; @endphp
  @if(!$slot) @continue @endif

  <div class="card mb-3">
    <div class="card-header text-capitalize">{{ ucfirst($tipo) }}</div>
    <div class="card-body">
      {{-- FORM: aggiungi consumo manuale per questo pasto --}}
      <form method="POST" action="{{ route('alimentazione.consumi.store', $slot->id) }}" class="row g-2 mb-3">
        @csrf
        <div class="col-md-5">
          <select name="alimento_id" class="form-select" required>
            <option value="">— Alimento —</option>
            @foreach($alimenti as $a)
              <option value="{{ $a->id }}">{{ $a->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <input type="number" step="0.01" min="0.01" name="quantita" class="form-control" placeholder="Q.tà" required>
        </div>
        <div class="col-md-2">
          <select name="unita" class="form-select" required>
            <option value="g">g</option>
            <option value="ml">ml</option>
            <option value="u">u</option>
          </select>
        </div>
        <div class="col-md-2 form-check mt-2">
          <input class="form-check-input" type="checkbox" name="scarica_dispensa" id="scarica{{ $slot->id }}" checked>
          <label class="form-check-label" for="scarica{{ $slot->id }}">Scarica dispensa</label>
        </div>
        <div class="col-md-1">
          <button class="btn btn-primary w-100">Aggiungi</button>
        </div>
      </form>

      @php
        // elenco pianificato per questo pasto
        $plannedList = $plannedByTipo[$tipo] ?? collect();
      @endphp

      @if($plannedList->isNotEmpty())
        <div class="mb-2 small">
          <div class="text-muted fw-semibold">Pianificato</div>
          <ul class="list-unstyled mb-0">
            @foreach($plannedList as $it)
              @php
                $stock = $it->riferibile;                 // App\Models\AlimentoDispensa
                $a     = $stock?->alimento;               // App\Models\Alimento
                $uRaw  = $stock ? ($stock->unita instanceof \BackedEnum ? $stock->unita->value : $stock->unita) : '';
                $u     = strtolower($uRaw ?: '');         // 'g' | 'ml' | 'u'
                $qPlan = (int)($it->quantita ?? 0);
              @endphp

              <li class="d-flex justify-content-between align-items-center">
                <div>
                  🍽️ {{ $a?->display_name ?? $a?->nome ?? '—' }}
                  <small class="text-muted ms-1">
                    @if($qPlan > 0) ({{ $qPlan }} {{ $u }}) pianificati @else (quantità da definire) @endif
                  </small>
                </div>

                {{-- MINI-FORM: registra consumo della voce pianificata --}}
                <form method="POST" action="{{ route('alimentazione.consumi.store', $it->id) }}" class="d-flex align-items-center gap-2">
                  @csrf
                  @if($stock && $stock->alimento_id)
                    <input type="hidden" name="alimento_id" value="{{ $stock->alimento_id }}">
                  @endif
                  <input type="hidden" name="unita" value="{{ $u }}">

                  <input type="number"
                         name="quantita"
                         min="0" step="1"
                         value="{{ $qPlan > 0 ? $qPlan : '' }}"
                         placeholder="q.tà"
                         class="form-control form-control-sm"
                         style="width: 86px">

                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="sd_plan_{{ $it->id }}" name="scarica_dispensa" value="1" checked>
                    <label class="form-check-label small" for="sd_plan_{{ $it->id }}">Scarica</label>
                  </div>

                  <button class="btn btn-sm btn-primary">Registra</button>
                </form>
              </li>
            @endforeach
          </ul>
        </div>
      @endif

      <table class="table table-sm">
        <thead><tr>
          <th>Alimento</th><th class="text-end">Q.tà</th><th class="text-end">kcal</th>
          <th class="text-end">C</th><th class="text-end">P</th><th class="text-end">G</th><th></th>
        </tr></thead>
        <tbody>
        @foreach($slot->alimentiPasti as $r)
          <tr>
            <td>{{ $r->alimento->nome }}</td>
            <td class="text-end">{{ $r->quantita }} {{ $r->unita->value }}</td>
            <td class="text-end">{{ $r->kcal }}</td>
            <td class="text-end">{{ $r->carbo_g }}</td>
            <td class="text-end">{{ $r->prot_g }}</td>
            <td class="text-end">{{ $r->grassi_g }}</td>
            <td class="text-end">
              <form action="{{ route('alimentazione.consumi.destroy', $r->id) }}" method="POST" class="d-inline">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm">X</button>
              </form>
            </td>
          </tr>
        @endforeach
        </tbody>
        <tfoot>
          @php $t = $totali[$slot->id] ?? ['kcal'=>0,'carbo_g'=>0,'prot_g'=>0,'grassi_g'=>0]; @endphp
          <tr class="fw-bold">
            <td>Totale {{ ucfirst($tipo) }}</td>
            <td></td>
            <td class="text-end">{{ $t['kcal'] }}</td>
            <td class="text-end">{{ $t['carbo_g'] }}</td>
            <td class="text-end">{{ $t['prot_g'] }}</td>
            <td class="text-end">{{ $t['grassi_g'] }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
@endforeach
@endsection

