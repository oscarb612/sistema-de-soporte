@extends('layouts.app')

@section('content')
<div class="page-head">
    <h1>Editar empresa</h1>
</div>
<div class="panel">
    <form method="post" action="{{ route('companies.update', $company) }}">
        @csrf
        @method('put')
        @include('companies.form')
    </form>
</div>
@endsection
