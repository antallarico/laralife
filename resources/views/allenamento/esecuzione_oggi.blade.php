@extends('layouts.app')

@section('title', 'Esecuzione Allenamenti - Oggi')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Attività di oggi ({{ \Carbon\Carbon::parse($oggi)->format('d/m/Y') }})</h4>
    <a href="{{ route('planner.allenamento.index') }}" class="btn btn-outline-secondary">⬅ Torna al planner</a>
  </div>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  @if($planning->isEmpty())
    <div class="alert alert-light text-muted">Nessuna lezione pianificata per oggi.</div>
  @else
    <div class="vstack gap-3">
      @foreach($planning as $p)
        @php
          $lez = $p->lezione ?? $p->riferibile; // compat
          $es  = $esecuzioni->get($lez->id ?? 0);
          $min = old('minuti_effettivi', $es->minuti_effettivi ?? null);
          $st  = old('stato', $es->stato ?? 'in_corso');
          $nt  = old('note', $es->note ?? '');
        @endphp

        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold">{{ $lez->titolo ?? '—' }}</div>
                <div class="text-muted small">Durata prevista: {{ $lez->durata ?? '—' }} min</div>
                @if(!empty($p->note))
                  <div class="text-muted small fst-italic mt-1">{{ $p->note }}</div>
                @endif
              </div>
            </div>

            <form method="POST" action="{{ route('planner.allenamento.esecuzione.salva') }}" class="row g-2 align-items-end mt-3">
              @csrf
              <input type="hidden" name="lezione_id" value="{{ $lez->id }}">

              <div class="col-12 col-md-2">
                <label class="form-label mb-1">Minuti effettivi</label>
                <input type="number" name="minuti_effettivi" class="form-control" min="0" max="1440" step="1" value="{{ $min }}">
              </div>

              <div class="col-12 col-md-2">
                <label class="form-label mb-1">Stato</label>
                <select name="stato" class="form-select">
                  <option value="in_corso"    {{ $st==='in_corso' ? 'selected' : '' }}>In corso</option>
                  <option value="completata"  {{ $st==='completata' ? 'selected' : '' }}>Completata</option>
                  <option value="saltata"     {{ $st==='saltata' ? 'selected' : '' }}>Saltata</option>
                </select>
              </div>

              <div class="col-12 col-md-5">
                <label class="form-label mb-1">Note</label>
                <input type="text" name="note" class="form-control" value="{{ $nt }}" placeholder="Note opzionali">
              </div>

              <div class="col-12 col-md-1 d-grid">
                <button class="btn btn-primary">💾 Salva</button>
              </div>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif
@endsection
