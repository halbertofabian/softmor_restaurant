@extends('layouts.master')

@section('title', 'Áreas de Preparación')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Áreas de Preparación</h5>
        <a href="{{ route('preparation-areas.create') }}" class="btn btn-primary">Nueva Área</a>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Orden</th>
                    <th>Imprime Ticket</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($areas as $area)
                <tr>
                    <td>{{ $area->name }}</td>
                    <td>{{ $area->sort_order }}</td>
                    <td>
                        @if($area->print_ticket)
                            <span class="badge bg-label-success">Sí</span>
                        @else
                            <span class="badge bg-label-secondary">No</span>
                        @endif
                    </td>
                    <td>
                        @if($area->status)
                            <span class="badge bg-label-success">Activo</span>
                        @else
                            <span class="badge bg-label-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('preparation-areas.edit', $area) }}" class="btn btn-sm btn-icon btn-text-secondary"><i class="ti tabler-edit"></i></a>
                        <form action="{{ route('preparation-areas.destroy', $area) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-text-danger" onclick="return confirm('¿Seguro?')"><i class="ti tabler-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
