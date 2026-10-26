@extends('layouts.app')
@section('content')
<h1 style="margin-bottom:20px;">Editar servicio</h1>
<form method="post" action="{{ route('services.update', $service) }}">@csrf @method('put') @include('services.form')</form>
@endsection
