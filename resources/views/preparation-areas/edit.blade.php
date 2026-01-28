@extends('layouts.master')

@section('title', 'Editar Área de Preparación')

@section('content')
<div class="card mb-4">
    <h5 class="card-header">Editar Área de Preparación</h5>
    <div class="card-body">
        <form action="{{ route('preparation-areas.update', $preparationArea) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label" for="name">Nombre</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $preparationArea->name }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="description">Descripción</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ $preparationArea->description }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="printer_name">Nombre de Impresora (Windows)</label>
                <input type="text" class="form-control" id="printer_name" name="printer_name" value="{{ $preparationArea->printer_name }}" placeholder="Ej: EPSON-KITCHEN (Dejar vacío para usar default)">
                <div class="form-text">Si está vacío, se usará la impresora global configurada en Ajustes.</div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="sort_order">Orden (Prioridad)</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ $preparationArea->sort_order ?? 0 }}">
                </div>
                <div class="col-md-6 mb-3">
                     <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" id="print_ticket" name="print_ticket" {{ $preparationArea->print_ticket ? 'checked' : '' }}>
                        <label class="form-check-label" for="print_ticket">Imprime Ticket</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="status" name="status" {{ $preparationArea->status ? 'checked' : '' }}>
                    <label class="form-check-label" for="status">Activo</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('preparation-areas.index') }}" class="btn btn-label-secondary">Cancelar</a>
        </form>
    </div>
</div>
@endsection
