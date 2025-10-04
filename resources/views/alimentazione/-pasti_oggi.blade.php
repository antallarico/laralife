@extends('layouts.app')

@section('title', 'Pasti di Oggi')

@section('content')
<div class="container">
    <h2 class="mb-4">🍽️ Pasti del {{ \Carbon\Carbon::parse($data_oggi)->format('d/m/Y') }}</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
	
    @foreach ($tipi_pasto as $tipo)
        @php
            $pianificati_filtro = $pianificati[$tipo] ?? collect();
            $non_pianificati_filtro = $non_pianificati[$tipo] ?? collect();
        @endphp

        @if ($pianificati_filtro->count() || $non_pianificati_filtro->count())
            <div class="mb-4">
                <h4 class="border-bottom pb-1">{{ ucfirst($tipo) }}</h4>

                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Alimento (quantità pianificata)</th>
                            <th>Quantità</th>
                            <th>Note</th>
                            <th>Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Pianificati --}}
                        @foreach ($pianificati_filtro as $p)
                            @php
                                $salvato = $registrati[$tipo][$p->alimento?->id ?? 0] ?? null;
                            @endphp
                            <form action="{{ route('alimentazione.pasti.store', $planning->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="tipo_pasto" value="{{ $tipo }}">
                                <input type="hidden" name="alimento_id" value="{{ $p->alimento?->id }}">
                                <input type="hidden" name="unita_misura" value="{{ $p->unita_misura ?? 'g' }}">

                                <tr>
                                    <td>
                                        <strong>{{ $p->alimento?->nome ?? 'N/A' }}</strong>
                                        @if ($p->alimento?->marca)
                                            <small class="text-muted">({{ $p->alimento->marca }})</small>
                                        @endif
                                        <span class="text-secondary small">{{ $p->quantita }} {{ $p->unita_misura ?? 'g' }}</span>
                                    </td>

                                    <td style="width: 140px">
                                        <input type="number" name="quantita" value="{{ $salvato->quantita ?? '' }}" class="form-control form-control-sm" min="0" step="1" required>
                                    </td>

                                    <td>
                                        <input type="text" name="note" value="{{ $salvato->note ?? '' }}" class="form-control form-control-sm" placeholder="Note">
                                    </td>

                                    <td style="width: 80px">
                                        <button class="btn btn-sm btn-success">✔</button>
                                    </td>
                                </tr>
                            </form>
                        @endforeach

                        {{-- Non pianificati --}}
                        @foreach ($non_pianificati_filtro as $pasto)
                            <form action="{{ route('alimentazione.pasti.store', $planning->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="tipo_pasto" value="{{ $tipo }}">
                                <input type="hidden" name="alimento_id" value="{{ $pasto->alimento->id }}">
                                <input type="hidden" name="unita_misura" value="{{ $pasto->unita_misura ?? 'g' }}">

                                <tr class="table-warning">
                                    <td>
                                        <strong>{{ $pasto->alimento->nome }}</strong>
                                        @if ($pasto->alimento->marca)
                                            <small class="text-muted">({{ $pasto->alimento->marca }})</small>
                                        @endif
                                        <span class="text-secondary small">(aggiunto manualmente)</span>
                                    </td>

                                    <td style="width: 140px">
                                        <input type="number" name="quantita" value="{{ $pasto->quantita ?? '' }}" class="form-control form-control-sm" min="0" step="1" required>
                                    </td>

                                    <td>
                                        <input type="text" name="note" value="{{ $pasto->note ?? '' }}" class="form-control form-control-sm" placeholder="Note">
                                    </td>

                                    <td style="width: 80px">
                                        <button class="btn btn-sm btn-success">✔</button>
                                    </td>
                                </tr>
                            </form>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

    <!-- Inserimento nuovo alimento -->
    <div class="border-top pt-4 mt-5">
        <h5>➕ Aggiungi alimento non pianificato</h5>
        <form action="{{ route('alimentazione.pasti.store', $planning->id) }}" method="POST" class="row g-2 align-items-end">
            @csrf

            <div class="col-md-2">
                <label class="form-label">Pasto</label>
                <select name="tipo_pasto" class="form-select" required>
                    <option value="">-- Seleziona --</option>
                    @foreach ($tipi_pasto as $tipo)
                        <option value="{{ $tipo }}">{{ ucfirst($tipo) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Alimento</label>
                <select name="alimento_id" class="form-select" required>
                    <option value="">-- Seleziona --</option>
                    @foreach ($alimenti as $alimento)
                        <option value="{{ $alimento->id }}">{{ $alimento->nome }} {{ $alimento->marca ? '(' . $alimento->marca . ')' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Quantità</label>
                <input type="number" name="quantita" class="form-control" min="0" step="1" required>
            </div>

            <div class="col-md-1">
                <label class="form-label">Unità</label>
                <input type="text" name="unita_misura" class="form-control" value="g">
            </div>

            <div class="col-md-3">
                <label class="form-label">Note</label>
                <input type="text" name="note" class="form-control">
            </div>

            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">+</button>
            </div>
        </form>
    </div>
</div>
@endsection
