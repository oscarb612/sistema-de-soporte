@extends('layouts.app')
@section('content')
<div class="page-head"><h1>Nueva recepción Recuperadata</h1></div>
<div class="panel"><form method="post" action="{{ route('data-recovery.store') }}" enctype="multipart/form-data">@csrf @include('data-recovery.form')</form></div>
@endsection
