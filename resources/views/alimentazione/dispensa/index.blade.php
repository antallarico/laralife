@extends('layouts.app')

@section('content')
<h1>Dispensa</h1>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form method="GET" action="{{ route('alimentazione.dispensa.index') }}" class="row g-2 flex-grow-1 me-3">
    <div class="col-md-3">
      <input type="text" name="q" class="form-control" placeholder="Cerca per nome/marca/distributore" value="{{ $q }}">
    </div>
    <div class="col-md-3">
      <select name="categoria" class="form-select">
        <option value="">— Categoria —</option>
        @foreach($categorie as $c)
          <option value="{{ $c }}" @selected($categoria===$c)>{{ $c }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <select name="posizione" class="form-select">
        <option value="">— Posizione —</option>
        @foreach($posizioni as $p)
          <option value="{{ $p }}" @selected($posizione===$p)>{{ $p }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <select name="entro_giorni" class="form-select" title="Scadenza entro">
        <option value="0"  @selected($entroGiorni===0)>Scadenza —</option>
        <option value="7"  @selected($entroGiorni===7)>entro 7 gg</option>
        <option value="14" @selected($entroGiorni===14)>entro 14 gg</option>
        <option value="30" @selected($entroGiorni===30)>entro 30 gg</option>
      </select>
    </div>
    <div class="col-md-1">
      <button class="btn btn-outline-primary w-100">Filtra</button>
    </div>
  </form>

  <a class="btn btn-primary" href="{{ route('alimentazione.dispensa.create') }}">Nuovo</a>
</div>

@if(session('ok'))
  <div class="alert alert-success">{{ session('ok') }}</div>
@endif
@if(session('warning'))
  <div class="alert alert-warning">{{ session('warning') }}</div>
@endif
@if ($errors->any())
  <div class="alert alert-danger"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<table class="table align-middle">
  <thead>
    <tr>
      <th>Alimento (Marca)</th>
      <th>Categoria</th>
      <th>Disponibilità</th>
      <th>Posizione</th>
      <th>Scadenza</th>
      <th>Note</th>
      <th class="text-end">Azioni</th>
    </tr>
  </thead>
  <tbody>
    @foreach($righe as $r)
      @php
        $u = $r->unita instanceof \BackedEnum ? $r->unita->value : $r->unita;
        $totale = $r->totale_disponibile; // accessor dal model
        $hasParziale = $r->ha_parziale_aperto; // accessor
      @endphp
      <tr class="{{ $totale === 0 ? 'table-warning' : '' }}">
        <td>
          {{ $r->alimento->display_name }}
          @if($totale === 0)
            <span class="badge bg-warning text-dark">Esaurito</span>
          @endif
        </td>
        <td>{{ $r->alimento->categoria ?? '—' }}</td>
        <td>
          {{-- Visualizzazione chiara di aperto + chiusi --}}
          @if($hasParziale)
            <div class="d-flex flex-column">
              <span class="text-primary fw-bold">
                🔓 {{ $r->quantita_parziale }}{{ $u }} aperto
              </span>
              @if($r->n_pezzi > 0)
                <span class="text-muted small">
                  + {{ $r->quantita_per_pezzo }}{{ $u }}×{{ $r->n_pezzi }}pz chiusi
                </span>
              @endif
              <span class="fw-bold">= {{ $totale }}{{ $u }} totale</span>
            </div>
          @else
            <span>
              @if($r->n_pezzi > 0)
                {{ $r->quantita_per_pezzo }}{{ $u }}×{{ $r->n_pezzi }}pz
              @else
                0{{ $u }}
              @endif
              @if($totale > 0)
                <span class="text-muted small">({{ $totale }}{{ $u }} tot)</span>
              @endif
            </span>
          @endif
        </td>
        <td>{{ $r->posizione ?? '—' }}</td>
        <td>
          @if($r->scadenza)
            @php
              $giorni = now()->diffInDays($r->scadenza, false);
              $classe = $giorni < 0 ? 'text-danger' : ($giorni <= 7 ? 'text-warning' : '');
            @endphp
            <span class="{{ $classe }}">
              {{ $r->scadenza->format('d/m/Y') }}
              @if($giorni < 0)
                (scaduto)
              @elseif($giorni <= 7)
                ({{ $giorni }}gg)
              @endif
            </span>
          @else
            —
          @endif
        </td>
        <td>{{ $r->note ?? '—' }}</td>
        <td class="text-end">
          {{-- Aggiungi al planner oggi --}}
          <form method="POST" action="{{ route('planner.alimentazione.store') }}" class="d-inline">
            @csrf
            <input type="hidden" name="data" value="{{ now()->toDateString() }}">
            <input type="hidden" name="dispensa_id" value="{{ $r->id }}">
            <input type="hidden" name="tipo_pasto" value="libero">
            <input type="hidden" name="quantita" value="1">
            <button type="submit" class="btn btn-sm btn-outline-success" title="Aggiungi al planner (oggi / Libero)">＋</button>
          </form>

          {{-- Chiudi parziale (se presente) --}}
          @if($hasParziale)
            <form method="POST" action="{{ route('alimentazione.dispensa.chiudi-parziale', $r->id) }}" class="d-inline"
                  onsubmit="return confirm('Chiudere il pezzo aperto? (Lo imposterà a 0)')">
              @csrf @method('POST')
              <button class="btn btn-sm btn-outline-warning" title="Chiudi pezzo aperto">🔒</button>
            </form>
          @endif

          {{-- Modifica --}}
          <button class="btn btn-sm btn-outline-secondary" type="button"
                  data-bs-toggle="collapse" data-bs-target="#editRow{{ $r->id }}">
            ✏️
          </button>

          {{-- Elimina --}}
          <form action="{{ route('alimentazione.dispensa.destroy', $r->id) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Eliminare questa riga di dispensa?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">🗑️</button>
          </form>
        </td>
      </tr>

      {{-- Riga collassabile per modifica --}}
      <tr class="collapse" id="editRow{{ $r->id }}">
        <td colspan="7">
          <form method="POST" action="{{ route('alimentazione.dispensa.update', $r->id) }}" class="row g-2 p-2 bg-light">
            @csrf @method('PUT')

            <div class="col-md-2">
              <label class="form-label mb-1 small">Aperto ({{ $u }})</label>
              <input type="number" name="quantita_parziale" class="form-control form-control-sm"
                     min="0" step="1"
                     value="{{ old('quantita_parziale', $r->quantita_parziale) }}"
                     placeholder="es. 40">
              <div class="form-text">Pezzo aperto</div>
            </div>

            <div class="col-md-2">
              <label class="form-label mb-1 small">Chiusi ({{ $u }})</label>
              <input type="number" name="quantita_disponibile" class="form-control form-control-sm"
                     min="0" step="1"
                     value="{{ old('quantita_disponibile', $r->quantita_disponibile) }}"
                     placeholder="es. 200">
              <div class="form-text">Pezzi chiusi</div>
            </div>

            <div class="col-md-1">
              <label class="form-label mb-1 small">N. Pezzi</label>
              <input type="number" name="n_pezzi" class="form-control form-control-sm"
                     min="0" step="1"
                     value="{{ old('n_pezzi', $r->n_pezzi) }}"
                     placeholder="es. 2">
            </div>

            <div class="col-md-2">
              <label class="form-label mb-1 small">Posizione</label>
              <input type="text" name="posizione" class="form-control form-control-sm"
                     value="{{ old('posizione', $r->posizione) }}">
            </div>

            <div class="col-md-2">
              <label class="form-label mb-1 small">Scadenza</label>
              <input type="date" name="scadenza" class="form-control form-control-sm"
                     value="{{ old('scadenza', optional($r->scadenza)->toDateString()) }}">
            </div>

            <div class="col-md-2">
              <label class="form-label mb-1 small">Note</label>
              <input type="text" name="note" class="form-control form-control-sm"
                     value="{{ old('note', $r->note) }}">
            </div>

            <div class="col-md-1 d-grid">
              <label class="form-label mb-1 d-none d-md-block">&nbsp;</label>
              <button class="btn btn-sm btn-success">💾 Salva</button>
            </div>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>

{{ $righe->links() }}
@endsection