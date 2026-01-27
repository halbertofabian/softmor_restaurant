@extends('layouts.master')
@section('title', 'Editar Sucursal')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Editar Sucursal</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('branches.update', $branch) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Sucursal</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $branch->name) }}" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="phone" value="{{ old('phone', $branch->phone) }}" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" class="form-control" name="address" value="{{ old('address', $branch->address) }}" />
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isActive" name="is_active" value="1" {{ $branch->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Sucursal Activa</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                    <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
