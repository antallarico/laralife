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
@if ($errors->any())
  <div class="alert alert-danger"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<table class="table align-middle">
  <thead>
    <tr>
      <th>Alimento (Marca)</th>
      <th>Categoria</th>
      <th class="text-end">Disponibile</th>
      <th class="text-end">Pezzi</th>
      <th>Posizione</th>
      <th>Scadenza</th>
      <th>Note</th>
      <th class="text-end">Azioni</th>
    </tr>
  </thead>
  <tbody>
    @foreach($righe as $r)
      <tr>
        <td>{{ $r->alimento->display_name }}</td>
        <td>{{ $r->alimento->categoria ?? '—' }}</td>
        <td class="text-end">
          {{ $r->quantita_disponibile }}
          {{ $r->unita instanceof \BackedEnum ? $r->unita->value : $r->unita }}
        </td>
        <td class="text-end">{{ $r->n_pezzi ?? '—' }}</td>
        <td>{{ $r->posizione ?? '—' }}</td>
        <td>{{ optional($r->scadenza)->format('d/m/Y') ?? '—' }}</td>
        <td>{{ $r->note ?? '—' }}</td>
        <td class="text-end">
			  <form method="POST" action="{{ route('planner.alimentazione.store') }}" class="d-inline">
    @csrf
    <input type="hidden" name="data" value="{{ now()->toDateString() }}">
    <input type="hidden" name="dispensa_id" value="{{ $r->id }}">
    <input type="hidden" name="tipo_pasto" value="libero">
    <input type="hidden" name="quantita" value="1">
    <button type="submit" class="btn btn-sm btn-outline-success" title="Aggiungi al planner (oggi / Libero)">＋</button>
  </form>			
		
          <button class="btn btn-sm btn-outline-secondary" type="button"
                  data-bs-toggle="collapse" data-bs-target="#editRow{{ $r->id }}">
            Modifica
          </button>
          <form action="{{ route('alimentazione.dispensa.destroy', $r->id) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Eliminare questa riga di dispensa?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">Elimina</button>
          </form>
        </td>
      </tr>

      <tr class="collapse" id="editRow{{ $r->id }}">
  <td colspan="8">
    <form method="POST" action="{{ route('alimentazione.dispensa.update', $r->id) }}" class="row g-2">
      @csrf @method('PUT')

      @php
        $u = $r->unita instanceof \BackedEnum ? $r->unita->value : $r->unita; // g | ml | u
      @endphp

      <div class="col-md-2">
        <label class="form-label mb-1">Disponibile ({{ $u }})</label>
        <input type="number" name="quantita_disponibile" class="form-control"
               min="0" step="1"
               value="{{ old('quantita_disponibile', $r->quantita_disponibile) }}"
               placeholder="es. 100">
        <div class="form-text">Valore nella stessa unità ({{ $u }})</div>
      </div>

      <div class="col-md-2">
        <label class="form-label mb-1">Pezzi</label>
        <input type="number" name="n_pezzi" class="form-control"
               min="0" step="1"
               value="{{ old('n_pezzi', $r->n_pezzi) }}"
               placeholder="es. 6">
      </div>

      <div class="col-md-2">
        <label class="form-label mb-1">Posizione</label>
        <input type="text" name="posizione" class="form-control" placeholder="Posizione"
               value="{{ old('posizione', $r->posizione) }}">
      </div>

      <div class="col-md-2">
        <label class="form-label mb-1">Scadenza</label>
        <input type="date" name="scadenza" class="form-control"
               value="{{ old('scadenza', optional($r->scadenza)->toDateString()) }}">
      </div>

      <div class="col-md-3">
        <label class="form-label mb-1">Note</label>
        <input type="text" name="note" class="form-control" placeholder="Note"
               value="{{ old('note', $r->note) }}">
      </div>

      <div class="col-md-1 d-grid">
        <label class="form-label mb-1 d-none d-md-block">&nbsp;</label>
        <button class="btn btn-success">Salva</button>
      </div>
    </form>
  </td>
</tr>

    @endforeach
  </tbody>
</table>

{{ $righe->links() }}
@endsection
