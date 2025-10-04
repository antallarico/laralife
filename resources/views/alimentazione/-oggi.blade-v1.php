@extends('layouts.app')

@section('content')
<h1>Alimentazione — {{ $day->isoFormat('dddd D MMMM YYYY') }}</h1>

@foreach($slots as $slot)
  <div class="card mb-3">
    <div class="card-header text-capitalize">{{ $slot->tipo_pasto }}</div>
    <div class="card-body">
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
            <td>Totale {{ $slot->tipo_pasto }}</td>
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
