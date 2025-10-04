@extends('layouts.app')

@section('title', 'Storico consumi')

@section('content')
<div class="container">
  <div class="d-flex align-items-center gap-2 mb-3">
    <h4 class="mb-0">Storico consumi ({{ $start->format('d/m/Y') }} – {{ $end->format('d/m/Y') }})</h4>
    <div class="ms-auto">
      <a class="btn btn-sm {{ $view==='settimana' ? 'btn-primary' : 'btn-outline-secondary' }}"
         href="{{ route('alimentazione.storico', ['view'=>'settimana']) }}">Settimana</a>
      <a class="btn btn-sm {{ $view==='mese' ? 'btn-primary' : 'btn-outline-secondary' }}"
         href="{{ route('alimentazione.storico', ['view'=>'mese']) }}">Mese</a>
    </div>
  </div>

  <div class="mb-3 d-flex gap-2">
    <a class="btn btn-outline-primary"
       href="{{ route('alimentazione.storico', ['view'=>$view, 'offset'=>$offset-1]) }}">← Indietro</a>
    <a class="btn btn-outline-primary"
       href="{{ route('alimentazione.storico', ['view'=>$view, 'offset'=>$offset+1]) }}">Avanti →</a>
    <a class="btn btn-outline-secondary ms-auto"
       href="{{ route('alimentazione.oggi') }}">📅 Oggi</a>
  </div>

  @php
    $cursor = $start->copy();
  @endphp

  <table class="table table-bordered align-top text-center">
    <thead>
      <tr>
        @foreach (['Lun','Mar','Mer','Gio','Ven','Sab','Dom'] as $g)
          <th style="width:14.28%">{{ $g }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @while ($cursor->lte($end))
        <tr>
          @for ($i=0; $i<7; $i++)
            @php
              $dateStr = $cursor->toDateString();
              $rows    = $byDate[$dateStr] ?? collect();
              $tot     = $totalsByDate[$dateStr] ?? ['kcal'=>0,'carbo_g'=>0,'prot_g'=>0,'grassi_g'=>0];
              $isToday = $cursor->isToday();
            @endphp
            <td class="{{ $isToday ? 'table-primary' : '' }}" style="vertical-align: top;">
              <div class="fw-bold mb-1">{{ $cursor->format('d/m') }}</div>

              @if ($rows->isEmpty())
                <div class="text-muted small">—</div>
              @else
                <div class="text-start small mb-2">
                  <ul class="list-unstyled mb-0">
                    @foreach ($rows->groupBy(fn($r) => $r->planning->tipo_pasto ?? 'libero') as $pasto => $list)
                      <li class="mb-1">
                        <div class="text-uppercase text-muted">{{ ucfirst($pasto) }}</div>
                        @foreach ($list as $r)
                          <div class="d-flex justify-content-between">
                            <span>{{ $r->alimento->nome }}</span>
                            <span class="text-muted">{{ $r->quantita }} {{ $r->unita->value }}</span>
                          </div>
                        @endforeach
                      </li>
                    @endforeach
                  </ul>
                </div>

                <div class="text-start small text-muted">
                  <div><strong>Totali</strong></div>
                  <div>Cal: {{ $tot['kcal'] }}</div>
                  <div>Carb: {{ $tot['carbo_g'] }}</div>
                  <div>Prot: {{ $tot['prot_g'] }}</div>
                  <div>Gras: {{ $tot['grassi_g'] }}</div>
                </div>
              @endif
            </td>
            @php $cursor->addDay(); @endphp
          @endfor
        </tr>
      @endwhile
    </tbody>
  </table>
</div>
@endsection
