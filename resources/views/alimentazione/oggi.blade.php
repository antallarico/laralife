@extends('layouts.app')

@section('content')
<h1>Alimentazione — {{ $day->isoFormat('dddd D MMMM YYYY') }}</h1>

@php
  $ORDINE_PASTI = ['colazione','pranzo','cena','spuntino','libero'];
  $slotsByTipo = $slots->keyBy(function($s){
    $t = $s->tipo_pasto;
    return ($t === null || $t === '') ? 'libero' : $t;
  });

  // TOT GIORNO
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

{{-- Box totale giornata --}}
<div class="alert alert-secondary d-flex flex-wrap align-items-center gap-3">
  <strong class="me-2 mb-0">Totale giornata</strong>
  <span>Cal: {{ $dayTotals['kcal'] }}</span>
  <span>Carb: {{ $dayTotals['carbo_g'] }}</span>
  <span>Prot: {{ $dayTotals['prot_g'] }}</span>
  <span>Gras: {{ $dayTotals['grassi_g'] }}</span>
</div>

@foreach($ORDINE_PASTI as $tipo)
  @php $slot = $slotsByTipo[$tipo] ?? null; @endphp
  @if(!$slot) @continue @endif

  <div class="card mb-3">
    <div class="card-header text-capitalize d-flex justify-content-between align-items-center">
      <span>{{ ucfirst($tipo) }}</span>
      @php $t = $totali[$slot->id] ?? ['kcal'=>0,'carbo_g'=>0,'prot_g'=>0,'grassi_g'=>0]; @endphp
      <small class="text-muted">
        Tot: {{ $t['kcal'] }} kcal | C:{{ $t['carbo_g'] }} P:{{ $t['prot_g'] }} G:{{ $t['grassi_g'] }}
      </small>
    </div>
    <div class="card-body">

      @php $plannedList = $plannedByTipo[$tipo] ?? collect(); @endphp

      {{-- SEZIONE PIANIFICATO (prioritaria) --}}
      @if($plannedList->isNotEmpty())
        <div class="mb-3 border-bottom pb-3">
          <div class="text-muted fw-semibold mb-2">📋 Pianificato per oggi</div>
          @foreach($plannedList as $it)
            @php
              $stock = $it->riferibile;
              $a     = $stock?->alimento;
              $uRaw  = $stock ? ($stock->unita instanceof \BackedEnum ? $stock->unita->value : $stock->unita) : '';
              $u     = strtolower($uRaw ?: '');
              $qPlan = (int)($it->quantita ?? 0);
              
              // Info dispensa per questo alimento
              $dispInfo = $dispenseInfo[$stock?->alimento_id ?? 0] ?? null;
              $hasParziale = $dispInfo && $dispInfo->quantita_parziale > 0;
              $qtaParziale = $dispInfo ? $dispInfo->quantita_parziale : 0;
              
              // ✅ FIX: Mostra modalità scarico per TUTTE le unità (g, ml, u)
              $mostraModalita = in_array($u, ['g','ml','u'], true);
            @endphp

            <div class="card mb-2 bg-light">
              <div class="card-body p-2">
                <form method="POST" action="{{ route('alimentazione.consumi.store', $it->id) }}" class="row g-2 align-items-end">
                  @csrf
                  @if($stock && $stock->alimento_id)
                    <input type="hidden" name="alimento_id" value="{{ $stock->alimento_id }}">
                  @endif
                  <input type="hidden" name="unita" value="{{ $u }}">

                  <div class="col-auto">
                    <div class="fw-bold">🍽️ {{ $a?->display_name ?? $a?->nome ?? '—' }}</div>
                    <small class="text-muted">
                      @if($qPlan > 0) Pianificati: {{ $qPlan }}{{ $u }} @else Quantità da definire @endif
                    </small>
                  </div>

                  <div class="col-auto">
                    <label class="form-label mb-0 small">Quantità</label>
                    <input type="number" name="quantita" min="0" step="1"
                           value="{{ $qPlan > 0 ? $qPlan : '' }}" placeholder="q.tà"
                           class="form-control form-control-sm" style="width: 70px" required>
                  </div>

                  {{-- ✅ MODALITÀ SCARICO (per g/ml/u) --}}
                  @if($mostraModalita)
                    <div class="col-auto">
                      <label class="form-label mb-0 small">Da dove scaricare</label>
                      <div class="d-flex gap-2">
                        <label class="form-check form-check-inline mb-0">
                          <input class="form-check-input" type="radio" name="modalita_scarico" value="parziale" 
                                 {{ $hasParziale ? 'checked' : '' }}>
                          <span class="form-check-label small">
                            Aperto
                            @if($hasParziale)
                              <span class="text-success">({{ $qtaParziale }}{{ $u }})</span>
                            @else
                              <span class="text-muted">(nessuno)</span>
                            @endif
                          </span>
                        </label>
                        <label class="form-check form-check-inline mb-0">
                          <input class="form-check-input" type="radio" name="modalita_scarico" value="nuovo"
                                 {{ !$hasParziale ? 'checked' : '' }}>
                          <span class="form-check-label small">Apri nuovo</span>
                        </label>
                      </div>
                    </div>
                  @endif

                  <div class="col-auto">
                    <label class="form-check mb-0">
                      <input class="form-check-input" type="checkbox" name="scarica_dispensa" value="1" checked>
                      <span class="form-check-label small">Scarica</span>
                    </label>
                  </div>

                  <div class="col-auto">
                    <button class="btn btn-sm btn-primary">✅ Registra</button>
                  </div>
                </form>
              </div>
            </div>
          @endforeach
        </div>
      @endif

      {{-- FORM: aggiungi consumo NON pianificato --}}
      <details class="mb-3">
        <summary class="text-muted small" style="cursor: pointer;">➕ Aggiungi alimento non pianificato</summary>
        <form method="POST" action="{{ route('alimentazione.consumi.store', $slot->id) }}" class="row g-2 mt-2">
          @csrf
          <div class="col-md-4">
            <label class="form-label small">Alimento</label>
            <select name="alimento_id" id="alimento_{{ $slot->id }}" class="form-select form-select-sm" required>
              <option value="">— Seleziona —</option>
              @foreach($alimenti as $a)
                <option value="{{ $a->id }}" data-unita="{{ $a->unita_preferita }}">
                  {{ $a->nome }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small">Quantità</label>
            <input type="number" step="1" min="1" name="quantita" class="form-control form-control-sm" placeholder="Q.tà" required>
          </div>
          <div class="col-md-1">
            <label class="form-label small">Unità</label>
            <select name="unita" id="unita_{{ $slot->id }}" class="form-select form-select-sm" required>
              <option value="g">g</option>
              <option value="ml">ml</option>
              <option value="u">u</option>
            </select>
          </div>

          {{-- Modalità scarico (visibile per g/ml/u) --}}
          <div class="col-md-3" id="modalita_{{ $slot->id }}" style="display:none;">
            <label class="form-label small">Da dove</label>
            <div class="d-flex flex-column gap-1">
              <label class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="radio" name="modalita_scarico" value="parziale" checked>
                <span class="form-check-label small">
                  Aperto <span class="text-muted" id="info_parziale_{{ $slot->id }}"></span>
                </span>
              </label>
              <label class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="radio" name="modalita_scarico" value="nuovo">
                <span class="form-check-label small">Apri nuovo</span>
              </label>
            </div>
          </div>

          <div class="col-auto">
            <label class="form-label small d-block">&nbsp;</label>
            <label class="form-check form-check-inline mb-0">
              <input class="form-check-input" type="checkbox" name="scarica_dispensa" id="scarica{{ $slot->id }}" checked>
              <span class="form-check-label small">Scarica</span>
            </label>
          </div>
          <div class="col-auto">
            <label class="form-label small d-block">&nbsp;</label>
            <button class="btn btn-sm btn-primary">➕ Aggiungi</button>
          </div>
        </form>
      </details>

      {{-- TABELLA CONSUMI REGISTRATI --}}
      @if($slot->alimentiPasti->isNotEmpty())
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Alimento</th>
              <th class="text-end">Q.tà</th>
              <th class="text-end">kcal</th>
              <th class="text-end">C</th>
              <th class="text-end">P</th>
              <th class="text-end">G</th>
              <th class="text-center">Azioni</th>
            </tr>
          </thead>
          <tbody>
          @foreach($slot->alimentiPasti as $r)
            <tr>
              <td>{{ $r->alimento->nome }}</td>
              <td class="text-end">{{ $r->quantita }} {{ $r->unita->value }}</td>
              <td class="text-end">{{ $r->kcal }}</td>
              <td class="text-end">{{ $r->carbo_g }}</td>
              <td class="text-end">{{ $r->prot_g }}</td>
              <td class="text-end">{{ $r->grassi_g }}</td>
              <td class="text-center">
                <form action="{{ route('alimentazione.consumi.destroy', $r->id) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Eliminare questo consumo?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm">✖</button>
                </form>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      @else
        <p class="text-muted small mb-0">Nessun consumo registrato</p>
      @endif

    </div>
  </div>
@endforeach

@push('scripts')
<script>
// Script per form "aggiungi NON pianificato": mostra modalità scarico per g/ml/u
document.querySelectorAll('[id^="alimento_"]').forEach(sel => {
  const slotId = sel.id.split('_')[1];
  const unitaSel = document.getElementById('unita_' + slotId);
  const modalitaDiv = document.getElementById('modalita_' + slotId);
  const infoParziale = document.getElementById('info_parziale_' + slotId);
  
  const dispenseInfo = @json($dispenseInfo);
  
  function updateUI() {
    const alimentoId = sel.value;
    const unita = unitaSel.value;
    
    // ✅ Mostra modalità per g, ml E u
    if (unita === 'g' || unita === 'ml' || unita === 'u') {
      modalitaDiv.style.display = 'block';
      
      // Mostra info parziale se disponibile
      if (alimentoId && dispenseInfo[alimentoId]) {
        const stock = dispenseInfo[alimentoId];
        if (stock.quantita_parziale > 0) {
          infoParziale.textContent = `(${stock.quantita_parziale}${unita})`;
          infoParziale.classList.add('text-success');
        } else {
          infoParziale.textContent = '(nessuno)';
          infoParziale.classList.remove('text-success');
        }
      } else {
        infoParziale.textContent = '';
      }
    } else {
      modalitaDiv.style.display = 'none';
    }
    
    // Auto-sync unità da alimento
    const opt = sel.selectedOptions[0];
    if (opt && opt.dataset.unita && sel.value !== '') {
      unitaSel.value = opt.dataset.unita;
    }
  }
  
  sel.addEventListener('change', updateUI);
  unitaSel.addEventListener('change', updateUI);
  updateUI();
});
</script>
@endpush
@endsection