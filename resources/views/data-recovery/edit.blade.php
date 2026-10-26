@extends('layouts.app')
@section('content')
<div class="page-head"><h1>Editar {{ $case->code }}</h1></div>
<div class="panel"><form method="post" action="{{ route('data-recovery.update',$case) }}">@csrf @method('put') @include('data-recovery.form')</form></div>
@endsection
