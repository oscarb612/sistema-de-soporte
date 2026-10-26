@extends('layouts.app')

@section('content')
<div class="page-head">
    <h1>Editar usuario</h1>
</div>
<div class="panel">
    <form method="post" action="{{ route('users.update', $managedUser) }}">
        @csrf
        @method('put')
        @include('users.form')
    </form>
</div>
@endsection
