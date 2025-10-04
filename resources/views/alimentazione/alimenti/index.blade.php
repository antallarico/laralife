@extends('layouts.app')

@section('content')
<h1>Alimenti</h1>

<a class="btn btn-primary mb-3" href="{{ route('alimentazione.alimenti.create') }}">Nuovo alimento</a>

@if(session('ok'))
  <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<table class="table table-sm align-middle">
  <thead>
    <tr>
      <th>Nome</th>
      <th>Marca</th>
      <th>Distributore</th>
      <th class="text-end">Calorie</th>
      <th class="text-end">Carbo</th>
      <th class="text-end">Proteine</th>
      <th class="text-end">Grassi</th>
      <th class="text-end">Prezzo</th>
      <th>Categoria</th>
      <th class="text-end">Azioni</th>
    </tr>
  </thead>
  <tbody>
    @foreach($alimenti as $a)
      <tr>
        <td>{{ $a->nome }}</td>
        <td>{{ $a->marca ?? '—' }}</td>
        <td>{{ $a->distributore ?? '—' }}</td>

        {{-- valori interi, come deciso --}}
        <td class="text-end">{{ $a->kcal_ref ?? 0 }}</td>
        <td class="text-end">{{ $a->carbo_ref_g ?? 0 }}</td>
        <td class="text-end">{{ $a->prot_ref_g ?? 0 }}</td>
        <td class="text-end">{{ $a->grassi_ref_g ?? 0 }}</td>

        {{-- prezzo: mostra 2 decimali se presente, altrimenti — --}}
        <td class="text-end">
          @if(!is_null($a->prezzo_medio))
            € {{ number_format((float)$a->prezzo_medio, 2, ',', '.') }}
          @else
            —
          @endif
        </td>

        <td>{{ $a->categoria ?? '—' }}</td>

        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="{{ route('alimentazione.alimenti.edit', $a) }}">Modifica</a>
          <form action="{{ route('alimentazione.alimenti.destroy', $a) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Eliminare definitivamente?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">Elimina</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>

{{ $alimenti->links() }}
@endsection


