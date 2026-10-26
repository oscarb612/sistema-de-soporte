@extends('layouts.app')
@section('content')
<h1 style="margin-bottom:20px;">Nuevo servicio</h1>
<form method="post" action="{{ route('services.store') }}">@csrf @include('services.form')</form>
@endsection
