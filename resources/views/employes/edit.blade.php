@extends('employes.app')

@section('content')
    <h1>Modifier employé</h1>
    <form method="POST" action="{{ route('employes.update', $employe->id) }}">
        @method('PUT')
        @include('employes.form')
    </form>
@endsection
