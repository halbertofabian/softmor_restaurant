@extends('layouts.master')

@section('title', 'Editar Categoría')

@section('content')
<div class="card mb-4">
    <h5 class="card-header">Editar Categoría</h5>
    <div class="card-body">
        <form action="{{ route('categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label" for="name">Nombre</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $category->name }}" required>
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="status" name="status" {{ $category->status ? 'checked' : '' }}>
                    <label class="form-check-label" for="status">Activo</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('categories.index') }}" class="btn btn-label-secondary">Cancelar</a>
        </form>
    </div>
</div>
@endsection
