@extends('layouts.app')

@section('title', 'Storico Esecuzioni')

@section('content')

<div class="d-flex align-items-center gap-2 mb-3">
  <a href="{{ route('planner.allenamento.index') }}" class="btn btn-outline-secondary">⬅ Torna al planner</a>

  <div class="ms-auto btn-group">
    <a href="{{ route('planner.allenamento.storico', ['view' => 'settimana', 'offset' => $offset ?? 0]) }}"
       class="btn btn-sm {{ ($view ?? 'settimana') === 'settimana' ? 'btn-primary' : 'btn-outline-secondary' }}">
      Settimana
    </a>
    <a href="{{ route('planner.allenamento.storico', ['view' => 'mese', 'offset' => $offset ?? 0]) }}"
       class="btn btn-sm {{ ($view ?? 'settimana') === 'mese' ? 'btn-primary' : 'btn-outline-secondary' }}">
      Mese
    </a>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
  <a href="{{ route('planner.allenamento.storico', ['view' => $view, 'offset' => ($offset ?? 0) - 1]) }}"
     class="btn btn-outline-primary">← Indietro</a>

  <h5 class="mb-0">Dal {{ $start->format('d/m/Y') }} al {{ $end->format('d/m/Y') }}</h5>

  <a href="{{ route('planner.allenamento.storico', ['view' => $view, 'offset' => ($offset ?? 0) + 1]) }}"
     class="btn btn-outline-primary">Avanti →</a>
</div>

@php
  // $esecuzioni può arrivare già raggruppato per data (array associativo) oppure come Collection piatta.
  $byDate = is_array($esecuzioni)
    ? collect($esecuzioni)
    : (method_exists($esecuzioni, 'groupBy')
        ? $esecuzioni->groupBy(fn($e) => ($e->data instanceof \Carbon\Carbon ? $e->data : \Carbon\Carbon::parse($e->data))->toDateString())
        : collect());

  // costruttore badge stato
  $badgeStato = function ($stato) {
    $s = strtolower((string)$stato);
    return match ($s) {
      'completata','completato','ok' => 'bg-success',
      'parziale'                     => 'bg-info',
      'saltata','skipped'            => 'bg-warning text-dark',
      'annullata','cancellata'       => 'bg-secondary',
      default                        => 'bg-secondary',
    };
  };
@endphp

@if($byDate->isEmpty())
  <div class="alert alert-light text-muted">Nessuna esecuzione nel periodo selezionato.</div>
@else
  <div class="vstack gap-4">
    @foreach($byDate->sortKeys() as $data => $lista)
      @php
        $carbon = \Carbon\Carbon::parse($data);
        $totMin = $lista->sum(fn($e) => (int)($e->minuti_effettivi ?? 0));
        $h = intdiv($totMin, 60); $m = $totMin % 60;
      @endphp

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="fw-bold">
            {{ $carbon->isoFormat('dddd D/MM/YYYY') }}
            @if($carbon->isToday())
              <span class="badge bg-primary ms-2">Oggi</span>
            @endif
          </div>
          <div>
            <span class="badge {{ $totMin < 60 ? 'bg-danger' : 'bg-success' }}">
              Totale: {{ $h > 0 ? ($h.'h ') : '' }}{{ $m }}m
            </span>
          </div>
        </div>

        <ul class="list-group list-group-flush">
          @foreach($lista as $es)
            <li class="list-group-item d-flex justify-content-between align-items-start">
              <div class="me-3">
                <div class="fw-semibold">
                  {{ $es->lezione->titolo ?? '—' }}
                  <span class="badge {{ $badgeStato($es->stato ?? '') }} ms-2">
                    {{ ucfirst($es->stato ?? '—') }}
                  </span>
                </div>
                @if(!empty($es->note))
                  <div class="text-muted small">{{ $es->note }}</div>
                @endif
              </div>
              <div class="text-nowrap">
                <span class="badge {{ (int)($es->minuti_effettivi ?? 0) < 60 ? 'bg-danger' : 'bg-success' }}">
                  {{ (int)($es->minuti_effettivi ?? 0) }}m
                </span>
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    @endforeach
  </div>
@endif

@endsection


