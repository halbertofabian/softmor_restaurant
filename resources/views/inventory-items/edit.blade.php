@extends('layouts.master')
@section('title', 'Editar materia prima')
@section('content')
<div class="card mb-4"><h5 class="card-header">Editar materia prima</h5><div class="card-body">
    <form action="{{ route('inventory-items.update', $inventoryItem) }}" method="POST">@csrf @method('PUT')
        @include('inventory-items.partials.form', ['item' => $inventoryItem])
        <button class="btn btn-primary">Actualizar</button>
        <a href="{{ route('inventory-items.index') }}" class="btn btn-label-secondary">Cancelar</a>
    </form>
</div></div>
@endsection
