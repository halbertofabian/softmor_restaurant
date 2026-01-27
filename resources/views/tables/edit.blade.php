@extends('layouts.master')
@section('title', 'Editar Mesa')
@section('content')
<div class="card mb-4">
    <h5 class="card-header">Editar Mesa</h5>
    <div class="card-body">
        <form action="{{ route('tables.update', $table) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label" for="name">Nombre / Número</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $table->name }}" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="zone">Zona</label>
                    <input type="text" class="form-control" id="zone" name="zone" value="{{ $table->zone }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="capacity">Capacidad (Personas)</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" value="{{ $table->capacity }}" min="1">
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ $table->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Activa</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('tables.index') }}" class="btn btn-label-secondary">Cancelar</a>
        </form>
    </div>
</div>
@endsection
