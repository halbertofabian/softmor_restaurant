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
            <table class="table table-hover">
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
                <tbody class="table-border-bottom-0">
                    @forelse($registers as $register)
                    <tr>
                        <td class="ps-4">
                            <span class="fw-medium">#{{ $register->id }}</span>
                        </td>
                        <td>
                            @if($register->status == 'open')
                                <span class="badge bg-label-success">Abierta</span>
                            @else
                                <span class="badge bg-label-secondary">Cerrada</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-medium">{{ $register->opened_at->format('d/m/Y') }}</span>
                                <small class="text-muted">{{ $register->opened_at->format('g:i A') }}</small>
                            </div>
                        </td>
                         <td>
                            @if($register->closed_at)
                                <div class="d-flex flex-column">
                                    <span>{{ $register->closed_at->format('d/m/Y') }}</span>
                                    <small class="text-muted">{{ $register->closed_at->format('g:i A') }}</small>
                                </div>
                            @else
                                <span class="text-muted fst-italic">-</span>
                            @endif
                        </td>
                        <td>
                             <div class="d-flex align-items-center">
                                 <div class="avatar avatar-xs me-2">
                                     <span class="avatar-initial rounded-circle bg-label-primary">
                                         {{ substr($register->user->name, 0, 1) }}
                                     </span>
                                 </div>
                                 <span class="text-truncate" style="max-width: 150px;">{{ $register->user->name }}</span>
                             </div>
                        </td>
                        <td class="text-end text-muted font-monospace">${{ number_format($register->opening_amount, 2) }}</td>
                        <td class="text-end font-monospace">
                            @if($register->closing_amount !== null)
                                <span class="fw-medium text-success">${{ number_format($register->closing_amount, 2) }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                    <i class="ti tabler-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('cash-registers.show', $register) }}">
                                        <i class="ti tabler-eye me-1"></i> Ver Detalle
                                    </a>
                                    @if($register->status == 'closed')
                                    <a class="dropdown-item" href="{{ route('cash-registers.print', $register) }}" target="_blank">
                                        <i class="ti tabler-printer me-1"></i> Imprimir Ticket
                                    </a>
                                     <a class="dropdown-item" href="{{ route('cash-registers.report', $register) }}" target="_blank">
                                        <i class="ti tabler-file-analytics me-1"></i> Reporte PDF
                                    </a>
                                    @endif
                                    @if($register->status == 'open')
                                     <a class="dropdown-item text-primary" href="{{ route('cash-registers.show', $register) }}">
                                        <i class="ti tabler-lock-open me-1"></i> Hacer Corte
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted mb-2">
                                <i class="ti tabler-cash-off fs-1"></i>
                            </div>
                            <p class="mb-0">No hay registros de caja encontrados.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($registers->hasPages())
        <div class="card-footer py-3">
             {{ $registers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
