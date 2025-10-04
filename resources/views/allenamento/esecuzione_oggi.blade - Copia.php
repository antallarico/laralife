@extends('layouts.app')

@section('title', 'Agenda di Oggi')

@section('content')
    <h2 class="mb-4">Agenda Allenamento – {{ \Carbon\Carbon::today()->isoFormat('dddd D MMMM YYYY') }}</h2>

    @php
        $oggi = \Carbon\Carbon::today()->toDateString();
        $oggi_planning = $planning->where('data', $oggi)->sortBy('ordine');
        $totale_minuti = $oggi_planning->sum(fn($p) => $p->lezione->durata ?? 0);
    @endphp

    <p class="mb-3">
        <strong>Totale previsto:</strong>
        <span class="badge {{ $totale_minuti < 60 ? 'bg-danger' : 'bg-success' }}">
            {{ floor($totale_minuti / 60) > 0 ? floor($totale_minuti / 60) . 'h ' : '' }}{{ $totale_minuti % 60 }}m
        </span>
    </p>

    @if ($oggi_planning->isEmpty())
        <div class="alert alert-info">Nessuna lezione pianificata per oggi.</div>
    @else
        <ul class="list-group mb-4">
            @foreach($planning as $item)
    @php $es = $esecuzioni[$item->riferimento_id] ?? null; @endphp
    <li class="list-group-item">
        <form method="POST" action="{{ route('planner.allenamento.salva') }}">
            @csrf
            <input type="hidden" name="lezione_id" value="{{ $item->riferimento_id }}">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong>{{ $item->lezione->titolo ?? 'N/A' }}</strong>
                    <small class="text-muted">({{ $item->lezione->durata ?? '?' }}m previsti)</small>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <input type="number" name="minuti_effettivi" value="{{ $es->minuti_effettivi ?? '' }}"
                        class="form-control form-control-sm" style="width: 80px;" placeholder="minuti" required>

                    <select name="stato" class="form-select form-select-sm" style="width: 120px;">
                        @foreach(['in_corso'=>'In corso','completata'=>'Completata','saltata'=>'Saltata'] as $v=>$l)
                            <option value="{{ $v }}" @if(($es->stato ?? '') === $v) selected @endif>{{ $l }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-sm btn-success">Salva</button>
                </div>
            </div>

            <div class="mb-1">
                <textarea name="note" class="form-control form-control-sm" rows="1" placeholder="Note...">{{ $es->note ?? '' }}</textarea>
            </div>
        </form>
    </li>
@endforeach

        </ul>
    @endif
@endsection
