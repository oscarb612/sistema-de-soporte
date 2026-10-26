@extends('layouts.app')

@section('content')
<div class="page-head">
    <h1>Nuevo cliente</h1>
</div>
<div class="panel">
    <form method="post" action="{{ route('clients.store') }}">
        @csrf
        @include('clients.form')
    </form>
</div>
@endsection
