@extends('layouts.master')

@section('title', 'Categorías')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Categorías</h5>
        <a href="{{ route('categories.create') }}" class="btn btn-primary">Nueva Categoría</a>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>
                        @if($category->status)
                            <span class="badge bg-label-success">Activo</span>
                        @else
                            <span class="badge bg-label-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-icon btn-text-secondary"><i class="ti tabler-edit"></i></a>
                        <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline">
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
