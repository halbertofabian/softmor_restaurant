@extends('layouts.master')

@section('title', 'Categorías de Gastos')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti tabler-tags me-2 text-primary"></i> Categorías de Gastos
                    </h5>
                    <a href="{{ route('cash-registers.report', ['cash_register' => 'current']) }}" class="btn btn-label-secondary">
                        <i class="ti tabler-arrow-left me-1"></i> Volver al Corte
                    </a>
                </div>
                
                <div class="card-body">
                    <!-- Formulario de Creación con Estilo del Tema -->
                    <form action="{{ route('expense-categories.store') }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label" for="name">Nombre de la Categoría</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti tabler-tag"></i></span>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Ej. Proveedores" required />
                            </div>
                        </div>
                        
                        <div class="col-md-5">
                            <label class="form-label" for="description">Descripción (Opcional)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti tabler-file-description"></i></span>
                                <input type="text" id="description" name="description" class="form-control" placeholder="Descripción breve" />
                            </div>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti tabler-plus me-1"></i> Crear
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabla de Lista -->
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @forelse($categories as $category)
                            <tr>
                                <td>
                                    <span class="fw-medium">{{ $category->name }}</span>
                                </td>
                                <td class="text-muted">
                                    {{ $category->description ?? '-' }}
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('expense-categories.destroy', $category) }}" method="POST" class="d-inline-block" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-icon btn-label-danger" data-bs-toggle="tooltip" title="Eliminar">
                                            <i class="ti tabler-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="text-muted mb-2">
                                        <i class="ti tabler-folder-off fs-1"></i>
                                    </div>
                                    <p class="mb-0">No hay categorías registradas aún.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
