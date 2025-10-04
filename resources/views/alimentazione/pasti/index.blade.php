@extends('layouts.app')

@section('title', 'Pasti del Giorno')

@section('content')
<div class="container">
    <h2 class="mb-4">🍽️ Pasti del {{ \Carbon\Carbon::parse($planning->data)->format('d/m/Y') }}</h2>

    <a href="{{ route('alimentazione.pasti.create', $planning->id) }}" class="btn btn-success mb-3">➕ Aggiungi Alimento</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @forelse ($planning->pasti->groupBy('tipo_pasto') as $tipo_pasto => $items)
        <h5 class="mt-4">{{ ucfirst($tipo_pasto) }}</h5>
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Orario</th>
                    <th>Alimento</th>
                    <th>Marca</th>
                    <th>Categoria</th>
                    <th>Note</th>
                    <th style="width: 130px;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->orario ?? '-' }}</td>
                        <td>{{ $item->alimento->nome ?? '-' }}</td>
                        <td>{{ $item->alimento->marca ?? '-' }}</td>
                        <td>{{ $item->alimento->categoria ?? '-' }}</td>
                        <td>{{ $item->note ?? '-' }}</td>
                        <td>
                            <a href="{{ route('alimentazione.pasti.edit', $item->id) }}" class="btn btn-sm btn-primary">✏️</a>
                            <form action="{{ route('alimentazione.pasti.destroy', $item->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Sei sicuro di voler rimuovere questo alimento?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">🗑️</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p class="text-muted">Nessun alimento pianificato per questo giorno.</p>
    @endforelse
</div>
@endsection
