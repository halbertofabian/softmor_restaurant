@extends('layouts.master')

@section('title', 'Reporte de Ventas')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold py-3">
            <span class="text-muted fw-light">Reportes /</span> Ventas
        </h4>
        <div class="card bg-label-success shadow-none border-0">
             <div class="card-body py-2 px-3">
                 <small class="d-block text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Total Filtrado</small>
                 <span class="fs-4 fw-bold text-success">${{ number_format($totalAmount, 2) }}</span>
             </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('reports.sales.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="start_date">Fecha Inicio</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="end_date">Fecha Fin</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="method">Método de Pago</label>
                    <select id="method" name="method" class="form-select">
                        <option value="all" {{ request('method') == 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="cash" {{ request('method') == 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="card" {{ request('method') == 'card' ? 'selected' : '' }}>Tarjeta</option>
                        <option value="transfer" {{ request('method') == 'transfer' ? 'selected' : '' }}>Transferencia</option>
                        <option value="other" {{ request('method') == 'other' ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>
                <div class="col-md-3">
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
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="ps-4">Folio Venta</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Método</th>
                        <th>Referencia</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($sales as $payment)
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold text-primary">#{{ $payment->order_id }}</span>
                        </td>
                         <td>
                            <div class="d-flex flex-column">
                                <span class="fw-medium">{{ $payment->created_at->format('d/m/Y') }}</span>
                                <small class="text-muted">{{ $payment->created_at->format('g:i A') }}</small>
                            </div>
                        </td>
                        <td>
                            @if($payment->order && $payment->order->name)
                                {{ Str::limit($payment->order->name, 20) }}
                            @else
                                <span class="text-muted fst-italic">Público General</span>
                            @endif
                        </td>
                        <td>
                            @switch($payment->method)
                                @case('cash')
                                    <span class="badge bg-label-success"><i class="ti tabler-cash me-1"></i> Efectivo</span>
                                    @break
                                @case('card')
                                    <span class="badge bg-label-info"><i class="ti tabler-credit-card me-1"></i> Tarjeta</span>
                                    @break
                                @case('transfer')
                                    <span class="badge bg-label-primary"><i class="ti tabler-building-bank me-1"></i> Transf.</span>
                                    @break
                                @default
                                    <span class="badge bg-label-secondary">{{ ucfirst($payment->method) }}</span>
                            @endswitch
                        </td>
                        <td>
                            {{ $payment->reference ?? '-' }}
                        </td>
                        <td class="text-end fw-bold">
                            ${{ number_format($payment->amount, 2) }}
                        </td>
                        <td class="text-center">
                            @if($payment->order)
                            <a href="{{ route('pos.ticket', $payment->order) }}" 
                               target="_blank"
                               class="btn btn-sm btn-icon btn-text-secondary rounded-pill" 
                               title="Reimprimir Ticket">
                                <i class="ti tabler-printer"></i>
                            </a>
                            <a href="{{ route('orders.show', $payment->order) }}" class="btn btn-sm btn-icon btn-text-primary rounded-pill" title="Ver Comanda">
                                <i class="ti tabler-eye"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-2">
                                <i class="ti tabler-receipt-off fs-1"></i>
                            </div>
                            <p class="mb-0">No se encontraron ventas con estos filtros.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer py-3">
            {{ $sales->links() }}
        </div>
    </div>
</div>
@endsection
