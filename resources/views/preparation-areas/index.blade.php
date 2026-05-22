@extends('layouts.master')

@section('title', 'Áreas de Preparación')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Áreas de Preparación</h5>
        <a href="{{ route('preparation-areas.create') }}" class="btn btn-primary">
            <i class="ti tabler-plus me-1"></i> Nueva Área
        </a>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table" id="preparation-areas-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Orden</th>
                    <th>Imprime Ticket</th>
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
    GF.createAjaxDataTable('#preparation-areas-table', {
        ajax: "{{ route('preparation-areas.datatable') }}",
        responsive: true,
        columns: [
            { data: 'name' },
            { data: 'sort_order' },
            { data: 'print_ticket', orderable: false, searchable: false },
            { data: 'status', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [2, 3, 4], render: function (data) { return data; } }
        ]
    });
</script>
@endpush
