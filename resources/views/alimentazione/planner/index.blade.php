@extends('layouts.app')


@section('title', 'Pianificazione Pasti')

<style>
/* input quantità: più piccolo, sobrio, senza spinner */
.qty-input{
  width: 32px;               /* più stretto */
  height: 24px;              /* compatto */
  line-height: 24px;
  padding: 0 .25rem;
  font-size: .8rem;
  border: 1px solid #d0d7de; /* bordo sottile */
  border-radius: .25rem;
}
.qty-input:focus{
  outline: none;
  box-shadow: none;
  border-color: #86b7fe;     /* focus Bootstrap-like */
}
/* rimuovi frecce (Chrome/Edge/Safari) */
.qty-input[type=number]::-webkit-outer-spin-button,
.qty-input[type=number]::-webkit-inner-spin-button{
  -webkit-appearance: none;
  margin: 0;
}
/* rimuovi frecce (Firefox e generico) */
.qty-input[type=number]{
  -moz-appearance: textfield;
  appearance: textfield;
}

/* bottone salvataggio compatto */
.qty-save.btn{
  padding: .125rem .4rem;
  font-size: .75rem;
  line-height: 1;
}
</style>


@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Pianificazione Pasti ({{ $start->format('d/m/Y') }} – {{ $end->format('d/m/Y') }})</h2>

    <div class="mb-3 d-flex gap-2">
        <a href="{{ route('planner.alimentazione.index', ['offset' => $offset - 1]) }}" class="btn btn-outline-primary">← Indietro</a>
        <a href="{{ route('planner.alimentazione.index', ['offset' => $offset + 1]) }}" class="btn btn-outline-primary">Avanti →</a>
        <a href="{{ route('planner.alimentazione.oggi') }}" class="btn btn-outline-secondary ms-auto">📅 Oggi</a>
    </div>

    <table class="table table-bordered planner-table text-center align-top">
        <thead>
            <tr>
                @foreach (['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'] as $giorno)
                    <th>{{ $giorno }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php $current = $start->copy(); @endphp
            @while ($current->lte($end))
                <tr>
                    @for ($i = 0; $i < 7; $i++)
                        @php
                            $data = $current->toDateString();
                            /** @var \Illuminate\Support\Collection $pasti */
                            $pasti = $planner[$data] ?? collect(); // <-- collection di Planning del giorno
                            $isToday = $current->isToday();
                            $gruppati = $pasti->groupBy('tipo_pasto');

                            $totali = ['kcal' => 0, 'carbo' => 0, 'prot' => 0, 'grassi' => 0];

                            // helper per mostrare l'unità della riga dispensa (enum o string)
                            $unit = function($stock) {
                                if (!$stock) return '';
                                return $stock->unita instanceof \BackedEnum ? $stock->unita->value : $stock->unita;
                            };
                        @endphp
                        <td class="{{ $isToday ? 'table-primary' : '' }}" style="min-height: 150px;">
                            <div class="fw-bold">{{ $current->format('d/m') }}</div>

                            @foreach (['colazione', 'pranzo', 'cena', 'spuntino', 'libero'] as $tipoPasto)
  @php $voci = $gruppati[$tipoPasto] ?? collect(); @endphp

  <div class="text-start mb-2">
    <div class="fw-semibold text-uppercase small text-muted">
      {{ $tipoPasto ? ucfirst($tipoPasto) : 'Libero' }}
    </div>

    <ul class="list-unstyled small js-meal-list"
        data-date="{{ $data }}"
        data-pasto="{{ $tipoPasto ?? '' }}"
        style="min-height:12px; border:1px dashed rgba(0,0,0,.08); padding:4px;">

      @forelse ($voci as $item)
        @php
          // --- CALCOLO TOTALI (enum-safe + fallback legacy) ---
          $stock = $item->riferibile;
          $a     = $stock?->alimento;
          $q     = (int)($item->quantita ?? 0);

          $uRaw  = $stock ? ($stock->unita instanceof \BackedEnum ? $stock->unita->value : $stock->unita) : '';
          $u     = strtolower(trim((string)($uRaw ?? '')));
          if (in_array($u, ['pz','pezzo','pezzi'], true)) $u = 'u';

          $kcal   = (int)($a?->kcal_ref     ?? $a?->calorie     ?? 0);
          $carbo  = (int)($a?->carbo_ref_g  ?? $a?->carboidrati ?? 0);
          $prot   = (int)($a?->prot_ref_g   ?? $a?->proteine    ?? 0);
          $grassi = (int)($a?->grassi_ref_g ?? $a?->grassi      ?? 0);

          $bnAny = $a?->base_nutrizionale ?? '';
          if     ($bnAny instanceof \BackedEnum) { $bnRaw = $bnAny->value; }
          elseif (is_string($bnAny))              { $bnRaw = $bnAny; }
          else                                    { $bnRaw = ''; }

          $bn = strtolower(trim($bnRaw));
          $bn = str_replace([' ', 'grammi'], ['', 'g'], $bn);
          $bn = str_replace(['gr'], ['g'], $bn);
          $bn = str_replace(['unità','unita','pezzo','pezzi','pz'], ['unit','unit','unit','unit','unit'], $bn);
          if     (str_starts_with($bn, '100g'))  $bn = '100g';
          elseif (str_starts_with($bn, '100ml')) $bn = '100ml';
          elseif ($bn === 'unit')                 $bn = 'unit';

          $mult = 0;
          if ($q > 0) {
            if     ($bn === '100g'  && $u === 'g')  $mult = $q / 100;
            elseif ($bn === '100ml' && $u === 'ml') $mult = $q / 100;
            elseif ($bn === 'unit'  && $u === 'u')  $mult = $q;
            if ($mult === 0) {
              if ($u === 'g' || $u === 'ml') $mult = $q / 100;
              elseif ($u === 'u')            $mult = $q;
            }
          }

          if ($mult > 0) {
            $totali['kcal']   += (int) round($kcal   * $mult);
            $totali['carbo']  += (int) round($carbo  * $mult);
            $totali['prot']   += (int) round($prot   * $mult);
            $totali['grassi'] += (int) round($grassi * $mult);
          }
        @endphp

        <li class="d-flex justify-content-between align-items-start js-item" data-id="{{ $item->id }}">
  <div class="me-2">
    🍽️ {{ $a?->display_name ?? $a?->nome ?? 'N/A' }}
    {{-- input quantità inline --}}
    <form method="POST" action="{{ route('planner.alimentazione.updateQty', $item->id) }}" class="d-inline ms-2 align-middle">
      @csrf @method('PATCH')
      <input
        type="number"
        name="quantita"
        class="qty-input js-no-drag"
        inputmode="numeric"
        min="0" step="1"
        value="{{ $q }}"
        aria-label="Quantità"
      >
      <span class="small text-muted">{{ $u }}</span>
      <button class="btn btn-sm js-no-drag" title="Salva quantità">💾</button>
    </form>
  

  {{-- elimina --}}
  <form action="{{ route('planner.alimentazione.destroy', $item->id) }}"
        method="POST" style="display: contents;"
        onsubmit="return confirm('Confermi l\'eliminazione?')">
    @csrf @method('DELETE')
    <button class="btn btn-sm btn-link text-danger p-0 js-no-drag" title="Rimuovi">✖</button>
  </form>
  </div>
</li>

      @empty
        {{-- lista vuota: target drop --}}
      @endforelse
    </ul>
  </div>
@endforeach

                                
                            

                            <div class="mt-2">
                                <form action="{{ route('planner.alimentazione.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="data" value="{{ $data }}">
                                    <div class="d-flex flex-column gap-1">
                                        <select name="dispensa_id" class="form-select form-select-sm">
                                            <option value="">➕ Seleziona alimento (dispensa)...</option>
                                            @foreach ($dispensa as $d)
                                                <option value="{{ $d->id }}">
                                                    {{ $d->alimento?->display_name ?? $d->alimento?->nome ?? '—' }}
                                                    @if ($d->n_pezzi)
                                                        ({{ $d->n_pezzi }} pz)
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>

                                        <input type="number" name="quantita" class="form-control form-control-sm" placeholder="Quantità (es. 100)" min="1" step="1">

                                        <select name="tipo_pasto" class="form-select form-select-sm">
                                            <option value="">Seleziona pasto</option>
                                            <option value="colazione">Colazione</option>
                                            <option value="pranzo">Pranzo</option>
                                            <option value="cena">Cena</option>
                                            <option value="spuntino">Spuntino</option>
                                            <option value="libero">Libero</option>
                                        </select>

                                        <button class="btn btn-sm btn-primary mt-1" type="submit">➕ Aggiungi</button>
                                    </div>
                                </form>
                            </div>
							
							@if ($pasti->isNotEmpty())
  <hr class="my-2">
  <div class="text-start mt-1 small">
    <div class="fw-semibold">Totale giornata</div>
    <div>Cal: {{ $totali['kcal'] }}</div>
    <div>Carb: {{ $totali['carbo'] }}</div>
    <div>Prot: {{ $totali['prot'] }}</div>
    <div>Gras: {{ $totali['grassi'] }}</div>
  </div>
@endif

{{-- DEBUG (temporaneo): vedi i numeri anche se 0, poi puoi rimuoverlo --}}
{{-- <!-- debug totali {{ json_encode($totali) }} --> --}}

							
							
                        </td>
                        @php $current->addDay(); @endphp
                    @endfor
                </tr>
            @endwhile
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
(function() {
  // Prende token CSRF da meta (assicurati che nel layout ci sia <meta name="csrf-token" content="{{ csrf_token() }}">)
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const lists = document.querySelectorAll('.js-meal-list');

  lists.forEach(list => {
    new Sortable(list, {
      group: 'planner-meals',  // drag tra qualsiasi lista (giorni/pasti)
      animation: 150,
      handle: undefined,       // oppure metti una maniglietta
      onEnd: async (evt) => {
        const li = evt.item;
        const id = li.getAttribute('data-id');
        const ul = li.parentElement;
        const date = ul.getAttribute('data-date');
        const tipo = ul.getAttribute('data-pasto') || null;
        const order = Array.from(ul.querySelectorAll('.js-item')).indexOf(li);

        try {
          const resp = await fetch("{{ route('planner.alimentazione.reorder') }}", {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ id, date, tipo_pasto: tipo, order })
          });
          if (!resp.ok) throw new Error('HTTP ' + resp.status);
          // opzionale: feedback visivo
          li.classList.add('bg-success','bg-opacity-10');
          setTimeout(()=>li.classList.remove('bg-success','bg-opacity-10'), 400);
        } catch (e) {
          alert('Riordino non riuscito: ' + e.message);
          // ricarica per riallineare
          location.reload();
        }
      }
    });
  });
})();
</script>

@endsection
