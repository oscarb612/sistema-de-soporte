@extends('layouts.app')

@section('content')
<div class="page-head">
    <h1>Nueva empresa</h1>
</div>
<div class="panel">
    <form method="post" action="{{ route('companies.store') }}">
        @csrf
        @include('companies.form')
    </form>
</div>
@endsection
