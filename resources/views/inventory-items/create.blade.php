@extends('layouts.master')
@section('title', 'Nueva materia prima')
@section('content')
<div class="card"><h5 class="card-header">Nueva materia prima</h5><div class="card-body">
    <form action="{{ route('inventory-items.store') }}" method="POST">@csrf
        @include('inventory-items.partials.form', ['item' => null])
        <button class="btn btn-primary">Guardar</button>
        <a href="{{ route('inventory-items.index') }}" class="btn btn-label-secondary">Cancelar</a>
    </form>
</div></div>
@endsection
