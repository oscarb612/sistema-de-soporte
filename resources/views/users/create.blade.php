@extends('layouts.app')

@section('content')
<div class="page-head">
    <h1>Nuevo usuario</h1>
</div>
<div class="panel">
    <form method="post" action="{{ route('users.store') }}">
        @csrf
        @include('users.form')
    </form>
</div>
@endsection
