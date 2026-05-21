@extends('layouts.master')
@section('title', 'Productos')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Productos</h5>
        <a href="{{ route('products.create') }}" class="btn btn-primary">Nuevo Producto</a>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table" id="products-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Sabores</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endpush

@push('scripts')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.min.js') }}"></script>
<script>
    GF.createAjaxDataTable('#products-table', {
        ajax: "{{ route('products.datatable') }}",
        responsive: true,
        columns: [
            { data: 'name' },
            { data: 'type', orderable: false, searchable: false },
            { data: 'category' },
            { data: 'price' },
            { data: 'flavors', orderable: false, searchable: false },
            { data: 'stock', orderable: false, searchable: false },
            { data: 'status', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [1, 4, 5, 6, 7], render: function (data) { return data; } }
        ]
    });
</script>
@endpush
