@extends('layouts.app')

@section('title', 'Storico Esecuzioni')

@section('content')
    <h2 class="mb-4">Storico Allenamento ({{ $start->format('d/m/Y') }} – {{ $end->format('d/m/Y') }})</h2>

    <div class="mb-3 d-flex gap-2">
        <a href="{{ route('planner.allenamento.storico', ['view' => $view, 'offset' => $offset - 1]) }}" class="btn btn-outline-primary">← Indietro</a>
        <a href="{{ route('planner.allenamento.storico', ['view' => $view, 'offset' => $offset + 1]) }}" class="btn btn-outline-primary">Avanti →</a>
        <div class="ms-auto">
            <a href="{{ route('planner.allenamento.storico', ['view' => 'settimana']) }}" class="btn btn-sm {{ $view === 'settimana' ? 'btn-primary' : 'btn-outline-secondary' }}">Settimana</a>
            <a href="{{ route('planner.allenamento.storico', ['view' => 'mese']) }}" class="btn btn-sm {{ $view === 'mese' ? 'btn-primary' : 'btn-outline-secondary' }}">Mese</a>
        </div>
    </div>

    <table class="table table-bordered text-center align-top">
        <thead>
            <tr>
                @foreach (['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'] as $giorno)
                    <th style="width: 14.28%;">{{ $giorno }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php $current = $start->copy(); @endphp
            @while ($current->lte($end))
                <tr>
                    @for ($i = 0; $i < 7; $i++)
                        @php
                            $data = $current->toDateString();
                            $giorno = $esecuzioni[$data] ?? collect();
                            $isToday = $current->isToday();
                            $totalMinutes = $giorno->sum('minuti_effettivi');
                            $badgeClass = $totalMinutes < 60 ? 'bg-danger' : 'bg-success';
                        @endphp
                        <td class="{{ $isToday ? 'table-primary' : '' }}">
                            <div class="fw-bold mb-1">{{ $current->format('d/m') }}</div>

                            @if ($giorno->isEmpty())
                                <div class="text-muted small">Nessuna attività</div>
                            @else
                                <div class="small mb-2">
                                    <span class="badge {{ $badgeClass }}">
                                        {{ floor($totalMinutes / 60) > 0 ? floor($totalMinutes / 60) . 'h ' : '' }}{{ $totalMinutes % 60 }}m
                                    </span>
                                </div>
                                <div class="d-flex flex-column gap-1 text-start small">
                                    @foreach ($giorno as $es)
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                {{ $es->lezione->titolo ?? 'N/A' }}
                                                <span class="badge {{ $es->stato === 'saltata' ? 'bg-warning text-dark' : 'bg-secondary' }} ms-1">
                                                    {{ ucfirst($es->stato) }}
                                                </span>
                                            </div>
                                            <span class="badge {{ $es->minuti_effettivi < 60 ? 'bg-danger' : 'bg-success' }}">
                                                {{ $es->minuti_effettivi }}m
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @php $current->addDay(); @endphp
                        </td>
                    @endfor
                </tr>
            @endwhile
        </tbody>
    </table>
@endsection


