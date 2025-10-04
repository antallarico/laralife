@extends('layouts.app')

@section('title', 'Aggiungi Alimento a Pasto')

@section('content')
<div class="container">
    <h2 class="mb-4">➕ Aggiungi Alimento al {{ \Carbon\Carbon::parse($planning->data)->format('d/m/Y') }}</h2>

    <form action="{{ route('alimentazione.pasti.store', $planning->id) }}" method="POST">
        @csrf

        <input type="hidden" name="planning_id" value="{{ $planning->id }}">

        <div class="mb-3">
            <label for="tipo_pasto" class="form-label">Tipo Pasto *</label>
            <select name="tipo_pasto" id="tipo_pasto" class="form-select" required>
                <option value="">-- Seleziona --</option>
                @foreach (['colazione', 'spuntino', 'pranzo', 'merenda', 'cena'] as $pasto)
                    <option value="{{ $pasto }}" {{ old('tipo_pasto') == $pasto ? 'selected' : '' }}>
                        {{ ucfirst($pasto) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="alimento_id" class="form-label">Alimento *</label>
            <select name="alimento_id" id="alimento_id" class="form-select" required>
                <option value="">-- Seleziona --</option>
                @foreach ($alimenti as $alimento)
                    <option value="{{ $alimento->id }}" {{ old('alimento_id') == $alimento->id ? 'selected' : '' }}>
                        {{ $alimento->nome }} {{ $alimento->marca ? '(' . $alimento->marca . ')' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="orario" class="form-label">Orario</label>
            <input type="time" name="orario" id="orario" class="form-control" value="{{ old('orario') }}">
        </div>

        <div class="mb-3">
            <label for="note" class="form-label">Note</label>
            <textarea name="note" id="note" class="form-control" rows="2">{{ old('note') }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">Salva</button>
        <a href="{{ route('alimentazione.pasti.index', $planning->id) }}" class="btn btn-secondary">Annulla</a>
    </form>
</div>
@endsection
