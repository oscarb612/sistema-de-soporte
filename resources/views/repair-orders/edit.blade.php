@extends('layouts.app')

@section('content')
<div class="page-head">
    <h1>Editar orden {{ $order->code }}</h1>
</div>
<div class="panel">
    <form method="post" action="{{ route('repair-orders.update', $order) }}" enctype="multipart/form-data">
        @csrf
        @method('put')
        @include('repair-orders.form')
    </form>
</div>
@endsection
