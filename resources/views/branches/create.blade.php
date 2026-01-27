@extends('layouts.master')
@section('title', 'Nueva Sucursal')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Nueva Sucursal</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('branches.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Sucursal</label>
                        <input type="text" class="form-control" name="name" placeholder="Ej. Matriz, Centro, Norte" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="phone" placeholder="Opcional" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" class="form-control" name="address" placeholder="Opcional" />
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Guardar Sucursal</button>
                    <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
