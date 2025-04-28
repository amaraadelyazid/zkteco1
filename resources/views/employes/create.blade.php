@extends('app')

@section('content')
    <h1>Créer un employé</h1>
    <form method="POST" action="{{ route('employes.store') }}">
        @include('employes.form')
    </form>
@endsection
