@extends('layouts.master')

@section('title', 'Reporte de Ventas')

@section('content')
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0 fw-bold py-3">
                <span class="text-muted fw-light">Reportes /</span> Ventas
            </h4>
            <a href="{{ route('reports.sales.by-waiter') }}" data-report-url="{{ route('reports.sales.by-waiter') }}"
                id="sales-waiter-report" target="_blank" class="btn btn-label-primary">
                <i class="ti tabler-report-analytics me-1"></i> Ventas por mesero
            </a>
        </div>
        <div class="card-body">
            <form id="sales-filter-form" action="{{ route('reports.sales.index') }}" method="GET"
                class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label" for="start_date">Fecha Inicio</label>
                    <input type="date" id="start_date" name="start_date" class="form-control"
                        value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="end_date">Fecha Fin</label>
                    <input type="date" id="end_date" name="end_date" class="form-control"
                        value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="method">Método de Pago</label>
                    <select id="method" name="method" class="form-select">
                        <option value="all" {{ request('method') == 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="cash" {{ request('method') == 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="card" {{ request('method') == 'card' ? 'selected' : '' }}>Tarjeta</option>
                        <option value="transfer" {{ request('method') == 'transfer' ? 'selected' : '' }}>Transferencia
                        </option>
                        <option value="other" {{ request('method') == 'other' ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="waiter_id">Mesero</label>
                    <select id="waiter_id" name="waiter_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($waiters as $waiter)
                            <option value="{{ $waiter->id }}"
                                {{ (string) request('waiter_id') === (string) $waiter->id ? 'selected' : '' }}>
                                {{ $waiter->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti tabler-filter me-1"></i> Filtrar
                        </button>
                        <a href="{{ route('reports.sales.index') }}" class="btn btn-label-secondary">
                            <i class="ti tabler-refresh"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover" id="sales-table">
                <thead>
                    <tr>
                        <th class="ps-4">Folio Venta</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Atendió</th>
                        <th>Método</th>
                        <th>Referencia</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="card-footer py-3 d-none">
            @if (isset($sales))
                {{ $sales->links() }}
            @endif
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <span class="fw-bold">Total Filtrado</span>
            <span class="fs-4 fw-bold text-success"
                id="sales-total-amount">${{ number_format($totalAmount, 2) }}</span>
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
        var salesTable = GF.createAjaxDataTable('#sales-table', {
            ajax: "{{ route('reports.sales.datatable') }}",
            ajaxData: function() {
                return {
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    method: $('#method').val(),
                    waiter_id: $('#waiter_id').val()
                };
            },
            responsive: true,
            columns: [{
                    data: 'folio'
                },
                {
                    data: 'date'
                },
                {
                    data: 'client'
                },
                {
                    data: 'waiter'
                },
                {
                    data: 'method',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'reference'
                },
                {
                    data: 'amount'
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            columnDefs: [{
                    targets: [0, 1, 2, 3, 4, 7],
                    render: function(data) {
                        return data;
                    }
                },
                {
                    targets: [6],
                    className: 'text-end fw-bold'
                },
                {
                    targets: [7],
                    className: 'text-center'
                }
            ]
        });

        $('#sales-filter-form').on('submit', function(event) {
            event.preventDefault();
            salesTable.ajax.reload();
        });

        $('#start_date, #end_date, #method, #waiter_id').on('change', function() {
            salesTable.ajax.reload();
        });

        $('#sales-waiter-report').on('click', function() {
            var params = new URLSearchParams({
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                method: $('#method').val()
            });
            var waiterId = $('#waiter_id').val();

            if (waiterId) {
                params.set('waiter_id', waiterId);
            }

            this.href = this.dataset.reportUrl + '?' + params.toString();
        });

        $('#sales-table').on('xhr.dt', function(e, settings, json) {
            if (!json || typeof json.totalAmount === 'undefined') {
                return;
            }
            $('#sales-total-amount').text('$' + json.totalAmount);
        });
    </script>
@endpush
