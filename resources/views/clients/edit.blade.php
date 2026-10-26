@extends('layouts.app')

@section('content')
<div class="page-head">
    <h1>Editar cliente</h1>
</div>
<div class="panel">
    <form method="post" action="{{ route('clients.update', $client) }}">
        @csrf
        @method('put')
        @include('clients.form')
    </form>
</div>
@endsection
