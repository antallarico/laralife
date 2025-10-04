@extends('layouts.app')

@section('content')
<h1>Dispensa</h1>

<form method="POST" action="{{ route('alimentazione.dispensa.add') }}" class="row g-2 mb-4">
  @csrf
  <div class="col-md-4">
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
      <option value="g">g</option><option value="ml">ml</option><option value="u">u</option>
    </select>
  </div>
  <div class="col-md-2">
    <input type="number" name="n_pezzi" class="form-control" placeholder="N. pezzi (opz.)">
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary w-100">Aggiungi</button>
  </div>
</form>

<table class="table">
  <thead><tr><th>Alimento</th><th class="text-end">Disponibile</th><th class="text-end">Pezzi</th></tr></thead>
  <tbody>
    @foreach($righe as $r)
      <tr>
        <td>{{ $r->alimento->nome }}</td>
        <td class="text-end">{{ $r->quantita_disponibile }} {{ $r->unita->value }}</td>
        <td class="text-end">{{ $r->n_pezzi ?? '—' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
@endsection
