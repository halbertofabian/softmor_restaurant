@extends('layouts.master')
@section('title', 'Materias primas')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Materias primas</h5>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('inventory-items.report') }}" class="btn btn-label-primary"><i class="ti tabler-report-analytics me-1"></i>Ver reporte</a>
            <a href="{{ route('inventory-items.create') }}" class="btn btn-primary"><i class="ti tabler-plus me-1"></i>Nueva materia prima</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Nombre</th><th>Existencia</th><th>Mínimo</th><th>Recetas</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td class="{{ $item->min_stock !== null && $item->stock <= $item->min_stock ? 'text-danger fw-semibold' : '' }}">
                        {{ number_format((float) $item->stock, 3) }} {{ $item->base_unit }}
                        @if($item->base_unit === 'g')
                            <span class="text-muted"> = {{ number_format((float) $item->stock / 1000, 3) }} kg</span>
                        @elseif($item->base_unit === 'ml')
                            <span class="text-muted"> = {{ number_format((float) $item->stock / 1000, 3) }} l</span>
                        @endif
                    </td>
                    <td>
                        @if($item->min_stock === null)
                            -
                        @else
                            {{ number_format((float) $item->min_stock, 3) }} {{ $item->base_unit }}
                            @if($item->base_unit === 'g')
                                <span class="text-muted"> = {{ number_format((float) $item->min_stock / 1000, 3) }} kg</span>
                            @elseif($item->base_unit === 'ml')
                                <span class="text-muted"> = {{ number_format((float) $item->min_stock / 1000, 3) }} l</span>
                            @endif
                        @endif
                    </td>
                    <td>{{ $item->recipe_items_count }}</td>
                    <td><span class="badge bg-label-{{ $item->status ? 'success' : 'secondary' }}">{{ $item->status ? 'Activo' : 'Inactivo' }}</span></td>
                    <td>
                        <a href="{{ route('inventory-items.show', $item) }}" class="btn btn-sm btn-icon btn-text-primary" title="Ver movimientos"><i class="ti tabler-history"></i></a>
                        <a href="{{ route('inventory-items.edit', $item) }}" class="btn btn-sm btn-icon btn-text-secondary"><i class="ti tabler-edit"></i></a>
                        <form action="{{ route('inventory-items.destroy', $item) }}" method="POST" class="d-inline">@csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-text-danger" data-gf-confirm="auto" data-gf-entity="la materia prima" data-gf-name="{{ $item->name }}"><i class="ti tabler-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No hay materias primas registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($items->hasPages())<div class="card-footer">{{ $items->links() }}</div>@endif
</div>
@endsection
