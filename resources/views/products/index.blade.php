@extends('layouts.master')
@section('title', 'Productos')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Productos</h5>
        <a href="{{ route('products.create') }}" class="btn btn-primary">Nuevo Producto</a>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>
                        @switch($product->type)
                            @case('dish') <span class="badge bg-label-primary">Platillo</span> @break
                            @case('drink') <span class="badge bg-label-info">Bebida</span> @break
                            @case('finished') <span class="badge bg-label-warning">Terminado</span> @break
                            @case('extra') <span class="badge bg-label-secondary">Extra</span> @break
                        @endswitch
                    </td>
                    <td>{{ $product->category->name ?? 'N/A' }}</td>
                    <td>${{ number_format($product->price, 2) }}</td>
                    <td>
                        @if($product->controls_inventory)
                            {{ $product->stock }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($product->status)
                            <span class="badge bg-label-success">Activo</span>
                        @else
                            <span class="badge bg-label-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-icon btn-text-secondary"><i class="ti tabler-edit"></i></a>
                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline">
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
