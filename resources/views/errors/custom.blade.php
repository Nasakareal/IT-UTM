@extends('layouts.app')

@section('content')
    <div class="alert alert-danger m-5">
        <h4>Error en la consulta:</h4>
        <pre>{{ $error }}</pre>
        <a href="{{ url()->previous() }}" class="btn btn-secondary mt-3">Regresar</a>
    </div>
@endsection
