@extends('layouts.app')

@section('content')
<h1>Nuovo elemento in dispensa</h1>

@if ($errors->any())
  <div class="alert alert-danger"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('alimentazione.dispensa.store') }}" class="row g-3">
  @csrf
  <div class="col-md-6">
  <label class="form-label">Alimento *</label>
  <select name="alimento_id" id="alimento_id" class="form-select" required>
    <option value="">— Seleziona —</option>
    @foreach($alimenti as $a)
      <option value="{{ $a->id }}"
              data-unita-preferita="{{ $a->unita_preferita }}">
        {{ $a->display_name }}
      </option>
    @endforeach
  </select>
</div>
  <div class="col-md-3">
    <label class="form-label">Quantità *</label>
    <input type="number" step="1" min="1" name="quantita" class="form-control" required>
  </div>
  <div class="col-md-3">
  <label class="form-label">Unità *</label>
  <select name="unita" id="unita" class="form-select" required>
    <option value="g">g</option>
    <option value="ml">ml</option>
    <option value="u">u</option>
  </select>
</div>
  <div class="col-md-3">
    <label class="form-label">N. pezzi (opz.)</label>
    <input type="number" min="1" name="n_pezzi" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Posizione</label>
	@php $pos = old('posizione', 'Dispensa'); @endphp
    <select name="posizione" class="form-select" required>
    <option value="Dispensa"    @selected($pos==='Dispensa')>Dispensa</option>
    <option value="Frigo"       @selected($pos==='Frigo')>Frigo</option>
    <option value="Congelatore" @selected($pos==='Congelatore')>Congelatore</option>
    <option value="Altro"       @selected($pos==='Altro')>Altro</option>
	</select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Scadenza</label>
    <input type="date" name="scadenza" class="form-control">
  </div>
  <div class="col-md-12">
    <label class="form-label">Note</label>
    <input type="text" name="note" class="form-control">
  </div>

  <div class="col-12">
    <button class="btn btn-primary">Salva</button>
    <a href="{{ route('alimentazione.dispensa.index') }}" class="btn btn-outline-secondary">Annulla</a>
  </div>
</form>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const selAlimento = document.getElementById('alimento_id');
  const selUnita    = document.getElementById('unita');

  function syncUnita() {
    const opt = selAlimento.selectedOptions[0];
    if (!opt) return;
    const up = opt.getAttribute('data-unita-preferita');
    if (up === 'g' || up === 'ml' || up === 'u') selUnita.value = up;
  }

  selAlimento.addEventListener('change', syncUnita);
  // se vuoi impostare subito appena si apre la pagina
  syncUnita();
});
</script>
@endpush
@endsection
