@extends('layouts.app')

@section('content')
<div class="page-head">
    <h1>Nueva orden de reparación</h1>
</div>
<div class="panel">
    <form method="post" action="{{ route('repair-orders.store') }}" enctype="multipart/form-data">
        @csrf
        @include('repair-orders.form')
    </form>
</div>
@endsection
