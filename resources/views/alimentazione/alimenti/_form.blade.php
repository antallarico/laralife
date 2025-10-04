@csrf
<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Nome *</label>
    <input type="text" name="nome" class="form-control" required
           value="{{ old('nome', $alimento->nome) }}">
  </div>
  <div class="col-md-6">
    <label class="form-label">Marca</label>
    <input type="text" name="marca" class="form-control"
           value="{{ old('marca', $alimento->marca) }}">
  </div>

  <div class="col-md-6">
    <label class="form-label">Distributore</label>
    <input type="text" name="distributore" class="form-control"
           value="{{ old('distributore', $alimento->distributore) }}">
  </div>
  <div class="col-md-3">
    <label class="form-label">Prezzo medio (€)</label>
    <input type="number" step="0.01" min="0" name="prezzo_medio" class="form-control"
           value="{{ old('prezzo_medio', $alimento->prezzo_medio) }}">
  </div>
  @php
  $CATEGORIE = [
    'Bevande','Carne','Cereali','Dolci','Frutta','Grassi',
    'Integratori','Latticini','Legumi','Pesce','Salse','Verdura','Uova'
  ];
  $catValue = old('categoria', $alimento->categoria ?? '');
  @endphp

<div class="col-md-3">
  <label for="categoria" class="form-label">Categoria</label>
  <select name="categoria" id="categoria" class="form-select">
    <option value="">— Seleziona categoria —</option>
    @foreach ($CATEGORIE as $c)
      <option value="{{ $c }}" @selected($catValue === $c)>{{ $c }}</option>
    @endforeach
  </select>
</div>
  

  <div class="col-md-2">
    <label class="form-label">Calorie (ref)</label>
    <input type="number" step="1" min="0" name="kcal_ref" class="form-control"
           value="{{ old('kcal_ref', $alimento->kcal_ref) }}">
  </div>
  <div class="col-md-2">
    <label class="form-label">Grassi (g ref)</label>
    <input type="number" step="1" min="0" name="grassi_ref_g" class="form-control"
           value="{{ old('grassi_ref_g', $alimento->grassi_ref_g) }}">
  </div>
  <div class="col-md-2">
    <label class="form-label">Carboidrati (g ref)</label>
    <input type="number" step="1" min="0" name="carbo_ref_g" class="form-control"
           value="{{ old('carbo_ref_g', $alimento->carbo_ref_g) }}">
  </div>
  <div class="col-md-2">
    <label class="form-label">Proteine (g ref)</label>
    <input type="number" step="1" min="0" name="prot_ref_g" class="form-control"
           value="{{ old('prot_ref_g', $alimento->prot_ref_g) }}">
  </div>
    {{-- Riferimenti per i calcoli --}}
	@php
		// gestisce sia cast enum sia stringhe
		$bnDb = $alimento->base_nutrizionale instanceof \BackedEnum
            ? $alimento->base_nutrizionale->value
            : ($alimento->base_nutrizionale ?? '');

		$upDb = $alimento->unita_preferita instanceof \BackedEnum
            ? $alimento->unita_preferita->value
            : ($alimento->unita_preferita ?? '');

		// in edit usa SEMPRE DB (o old()); in create arrivano i default dal controller
		$bn = old('base_nutrizionale', $bnDb);
		$up = old('unita_preferita',   $upDb);
	@endphp
  <div class="col-md-2">
  <label class="form-label">Base nutrizionale *</label>
  <select name="base_nutrizionale" class="form-select" required>
    <option value="100g"  @selected($bn==='100g')>per 100 g</option>
    <option value="100ml" @selected($bn==='100ml')>per 100 ml</option>
    <option value="unit"  @selected($bn==='unit')>per 1 unità</option>
  </select>
</div>

<div class="col-md-2">
  <label class="form-label">Unità preferita *</label>
  <select name="unita_preferita" class="form-select" required>
    <option value="g"  @selected($up==='g')>g</option>
    <option value="ml" @selected($up==='ml')>ml</option>
    <option value="u"  @selected($up==='u')>u</option>
  </select>
</div>

  <div class="col-12">
    <label class="form-label">Note</label>
    <textarea name="note" class="form-control" rows="3">{{ old('note', $alimento->note) }}</textarea>
  </div>
</div>

<div class="mt-3">
  <button class="btn btn-primary">Salva</button>
  <a href="{{ route('alimentazione.alimenti.index') }}" class="btn btn-outline-secondary">Annulla</a>
</div>
