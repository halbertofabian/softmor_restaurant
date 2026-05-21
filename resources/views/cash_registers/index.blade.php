@extends('layouts.master')

@section('title', 'Historial de Cajas')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                 <i class="ti tabler-history me-2"></i>Historial de Cortes
            </h5>
            <div class="d-flex gap-2">
                <a href="{{ route('cash-registers.report', ['cash_register' => 'current']) }}" class="btn btn-label-secondary">
                    <i class="ti tabler-file-report me-1"></i> Corte Actual
                </a>
                <a href="{{ route('cash-registers.create') }}" class="btn btn-primary">
                    <i class="ti tabler-plus me-1"></i> Abrir Turno
                </a>
            </div>
        </div>

        <div class="table-responsive text-nowrap">
            <table class="table table-hover" id="cash-registers-table">
                <thead>
                    <tr>
                        <th class="ps-4">Folio</th>
                        <th>Estado</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th>Responsable</th>
                        <th class="text-end">Inicial</th>
                        <th class="text-end">Final</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
               
            </table>
        </div>

        @if(false && isset($registers) && $registers->hasPages())
        <div class="card-footer py-3">
             {{ $registers->links() }}
        </div>
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
    GF.createAjaxDataTable('#cash-registers-table', {
        ajax: "{{ route('cash-registers.datatable') }}",
        responsive: true,
        columns: [
            { data: 'folio' },
            { data: 'status', orderable: false, searchable: false },
            { data: 'opened_at' },
            { data: 'closed_at' },
            { data: 'user' },
            { data: 'opening_amount' },
            { data: 'closing_amount' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [0, 1, 2, 3, 4, 7], render: function (data) { return data; } },
            { targets: [5], className: 'text-end text-muted font-monospace' },
            { targets: [6], className: 'text-end font-monospace' },
            { targets: [7], className: 'text-center' }
        ]
    });
</script>
@endpush
