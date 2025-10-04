@extends('layouts.app')

@section('content')
<h1>Nuovo alimento</h1>
@if ($errors->any())
  <div class="alert alert-danger"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ route('alimentazione.alimenti.store') }}">
  @include('alimentazione.alimenti._form')
</form>
@endsection
