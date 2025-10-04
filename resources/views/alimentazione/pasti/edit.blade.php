@extends('layouts.app')

@section('title', 'Modifica Alimento del Pasto')

@section('content')
<div class="container">
    <h2 class="mb-4">✏️ Modifica Alimento - {{ \Carbon\Carbon::parse($pasto->planning->data)->format('d/m/Y') }}</h2>

    <form action="{{ route('alimentazione.pasti.update', $pasto->id) }}" method="POST">
        @csrf
        @method('PUT')

        <input type="hidden" name="planning_id" value="{{ $pasto->planning_id }}">

        <div class="mb-3">
            <label for="tipo_pasto" class="form-label">Tipo Pasto *</label>
            <select name="tipo_pasto" id="tipo_pasto" class="form-select" required>
                @foreach (['colazione', 'spuntino', 'pranzo', 'merenda', 'cena'] as $tipo)
                    <option value="{{ $tipo }}" {{ old('tipo_pasto', $pasto->tipo_pasto) == $tipo ? 'selected' : '' }}>
                        {{ ucfirst($tipo) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="alimento_id" class="form-label">Alimento *</label>
            <select name="alimento_id" id="alimento_id" class="form-select" required>
                @foreach ($alimenti as $alimento)
                    <option value="{{ $alimento->id }}" {{ old('alimento_id', $pasto->alimento_id) == $alimento->id ? 'selected' : '' }}>
                        {{ $alimento->nome }} {{ $alimento->marca ? '(' . $alimento->marca . ')' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="orario" class="form-label">Orario</label>
            <input type="time" name="orario" id="orario" class="form-control" value="{{ old('orario', $pasto->orario) }}">
        </div>

        <div class="mb-3">
            <label for="note" class="form-label">Note</label>
            <textarea name="note" id="note" class="form-control" rows="2">{{ old('note', $pasto->note) }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Aggiorna</button>
        <a href="{{ route('alimentazione.pasti.index', $pasto->planning_id) }}" class="btn btn-secondary">Annulla</a>
    </form>
</div>
@endsection
