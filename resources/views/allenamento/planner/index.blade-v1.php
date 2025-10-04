@extends('layouts.app')

@section('title', 'Planner Allenamento')

@section('content')
	
<div class="d-flex justify-content-between mb-3">
    <a href="{{ route('planner.allenamento.index', ['offset' => $offset - 7]) }}" class="btn btn-outline-primary">← Settimana precedente</a>
    <span class="fw-bold">Dal {{ $giorni->first()->format('d/m/Y') }} al {{ $giorni->last()->format('d/m/Y') }}</span>
    <a href="{{ route('planner.allenamento.index', ['offset' => $offset + 7]) }}" class="btn btn-outline-primary">Settimana successiva →</a>
</div>

<table class="table table-bordered text-center align-top mb-4 planner-table">
    <thead>
        <tr>
            @foreach ($giorni->slice(0, 7) as $giorno)
                <th @if ($giorno->isToday()) class="table-primary" @endif>
                    {{ $giorno->format('D d/m') }}<br>
                    @php
                        $minuti = $planning->where('data', $giorno->toDateString())->sum(fn($p) => $p->lezione->durata ?? 0);
                        $h = floor($minuti / 60);
                        $m = $minuti % 60;
                    @endphp
                    <span class="badge {{ $minuti < 60 ? 'bg-danger' : 'bg-info' }}">{{ $h > 0 ? "$h h" : '' }} {{ $m }} min</span>
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            @foreach ($giorni->slice(0, 7) as $giorno)
                <td>
                    <ul class="list-unstyled" data-planning-day="{{ $giorno->toDateString() }}">
                        @foreach ($planning->where('data', $giorno->toDateString())->sortBy('ordine') as $item)
                            <li style="font-size: 0.8rem;" data-id="{{ $item->id }}">
                                {{ $item->lezione->titolo ?? 'N/A' }} <span class="text-muted small">({{ $item->lezione->durata ?? '?' }}m)</span>
                                <form method="POST" action="{{ route('planner.allenamento.destroy', $item->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="color: red; border: none; background: none;" title="Rimuovi">🗑️</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                    <form method="POST" action="{{ route('planner.allenamento.store') }}">
                        @csrf
                        <input type="hidden" name="data" value="{{ $giorno->toDateString() }}">
                        <select name="lezione_id" required>
                            <option value="">-- scegli una lezione --</option>
                            @foreach ($lezioni as $lezione)
                                <option value="{{ $lezione->id }}">{{ $lezione->titolo }}</option>
                            @endforeach
                        </select>
                        <button type="submit">➕</button>
                    </form>
                </td>
            @endforeach
        </tr>
    </tbody>
</table>

<table class="table table-bordered text-center align-top planner-table">
    <thead>
        <tr>
            @foreach ($giorni->slice(7, 7) as $giorno)
                <th @if ($giorno->isToday()) class="table-primary" @endif>
                    {{ $giorno->format('D d/m') }}<br>
                    @php
                        $minuti = $planning->where('data', $giorno->toDateString())->sum(fn($p) => $p->lezione->durata ?? 0);
                        $h = floor($minuti / 60);
                        $m = $minuti % 60;
                    @endphp
                    <span class="badge {{ $minuti < 60 ? 'bg-danger' : 'bg-info' }}">{{ $h > 0 ? "$h h" : '' }} {{ $m }} min</span>
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            @foreach ($giorni->slice(7, 7) as $giorno)
                <td>
                    <ul class="list-unstyled" data-planning-day="{{ $giorno->toDateString() }}">
                        @foreach ($planning->where('data', $giorno->toDateString())->sortBy('ordine') as $item)
                            <li style="font-size: 0.8rem;" data-id="{{ $item->id }}">
                                {{ $item->lezione->titolo ?? 'N/A' }} <span class="text-muted small"> ({{ $item->lezione->durata ?? '?' }}m)</span>
                                <form method="POST" action="{{ route('planner.allenamento.destroy', $item->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="color: red; border: none; background: none;" title="Rimuovi">🗑️</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                    <form method="POST" action="{{ route('planner.allenamento.store') }}">
                        @csrf
                        <input type="hidden" name="data" value="{{ $giorno->toDateString() }}">
                        <select name="lezione_id" required>
                            <option value="">-- scegli una lezione --</option>
                            @foreach ($lezioni as $lezione)
                                <option value="{{ $lezione->id }}">{{ $lezione->titolo }}</option>
                            @endforeach
                        </select>
                        <button type="submit">➕</button>
                    </form>
                </td>
            @endforeach
        </tr>
    </tbody>
</table>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('ul[data-planning-day]').forEach(ul => {
    new Sortable(ul, {
      animation: 150,
      group: 'planner',
      onEnd: evt => {
        const ul = evt.to;
        const data = ul.dataset.planningDay;
        const ids = [...ul.children].map(li => li.dataset.id);

        fetch("{{ route('planner.allenamento.reorder') }}", {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ data, ids })
        });
      }
    });
  });
});
</script>
@endpush
