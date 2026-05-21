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
        <table class="table" id="branches-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
    <div class="card-footer d-none">
        @if(isset($branches))
        {{ $branches->links() }}
        @endif
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
    GF.createAjaxDataTable('#branches-table', {
        ajax: "{{ route('branches.datatable') }}",
        responsive: true,
        columns: [
            { data: 'name' },
            { data: 'phone' },
            { data: 'address' },
            { data: 'status', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [0, 3, 4], render: function (data) { return data; } }
        ]
    });
</script>
@endpush
