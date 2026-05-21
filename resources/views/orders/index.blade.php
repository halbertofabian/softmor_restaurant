@extends('layouts.master')
@section('title', 'Comandas Activas')
@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Comandas Activas</h5>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table" id="orders-table">
            <thead>
                <tr>
                    <th>Comanda #</th>
                    <th>Mesa</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Creada</th>
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
    GF.createAjaxDataTable('#orders-table', {
        ajax: "{{ route('orders.datatable') }}",
        responsive: true,
        columns: [
            { data: 'id' },
            { data: 'table' },
            { data: 'status', orderable: false, searchable: false },
            { data: 'total' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [2, 5], render: function (data) { return data; } }
        ]
    });
</script>
@endpush
