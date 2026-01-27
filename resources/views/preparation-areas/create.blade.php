@extends('layouts.master')

@section('title', 'Nueva Área de Preparación')

@section('content')
<div class="card mb-4">
    <h5 class="card-header">Nueva Área de Preparación</h5>
    <div class="card-body">
        <form action="{{ route('preparation-areas.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="name">Nombre</label>
                <input type="text" class="form-control" id="name" name="name" required placeholder="Ej. Cocina">
            </div>
            <div class="mb-3">
                <label class="form-label" for="description">Descripción</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="sort_order">Orden (Prioridad)</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                </div>
                <div class="col-md-6 mb-3">
                     <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" id="print_ticket" name="print_ticket" checked>
                        <label class="form-check-label" for="print_ticket">Imprime Ticket</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                    <label class="form-check-label" for="status">Activo</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('preparation-areas.index') }}" class="btn btn-label-secondary">Cancelar</a>
        </form>
    </div>
</div>
@endsection
