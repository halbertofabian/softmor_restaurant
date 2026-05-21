@extends('layouts.master')

@section('title', 'Categorías')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Categorías</h5>
        <a href="{{ route('categories.create') }}" class="btn btn-primary">Nueva Categoría</a>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table" id="categories-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Área por defecto</th>
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
    GF.createAjaxDataTable('#categories-table', {
        ajax: "{{ route('categories.datatable') }}",
        responsive: true,
        columns: [
            { data: 'name' },
            { data: 'preparation_area' },
            { data: 'status', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [2, 3], render: function (data) { return data; } }
        ]
    });
</script>
@endpush
