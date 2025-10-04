@extends('layouts.app')

@section('content')
<h1>Modifica alimento</h1>
@if (session('ok'))
  <div class="alert alert-success">{{ session('ok') }}</div>
@endif
@if ($errors->any())
  <div class="alert alert-danger"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ route('alimentazione.alimenti.update', $alimento) }}">
  @method('PUT')
  @include('alimentazione.alimenti._form')
</form>
@endsection
