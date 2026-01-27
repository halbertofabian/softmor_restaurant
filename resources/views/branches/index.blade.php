@extends('layouts.master')
@section('title', 'Sucursales')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Sucursales</h5>
        <a href="{{ route('branches.create') }}" class="btn btn-primary">
            <i class="ti tabler-plus me-1"></i> Nueva Sucursal
        </a>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse($branches as $branch)
                <tr>
                    <td><strong>{{ $branch->name }}</strong></td>
                    <td>{{ $branch->phone ?? '-' }}</td>
                    <td>{{ $branch->address ?? '-' }}</td>
                    <td>
                        @if($branch->is_active)
                            <span class="badge bg-label-success">Activa</span>
                        @else
                            <span class="badge bg-label-secondary">Inactiva</span>
                        @endif
                    </td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                <i class="ti tabler-dots-vertical"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('branches.edit', $branch) }}"><i class="ti tabler-pencil me-1"></i> Editar</a>
                                <form action="{{ route('branches.destroy', $branch) }}" method="POST" onsubmit="return confirm('¿Eliminar sucursal?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger"><i class="ti tabler-trash me-1"></i> Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No hay sucursales registradas.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $branches->links() }}
    </div>
</div>
@endsection
