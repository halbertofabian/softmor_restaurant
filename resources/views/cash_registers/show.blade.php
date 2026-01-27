@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Control de Caja #{{ $cashRegister->id }}</h2>
            <p class="text-muted small mb-0">
                Abierta por <strong>{{ $cashRegister->user->name }}</strong> el {{ $cashRegister->opened_at->format('d/m/Y h:i A') }}
            </p>
        </div>
        <div>
             @if($cashRegister->status == 'open')
             <a href="{{ route('cash-registers.edit', $cashRegister) }}" class="btn btn-danger fw-bold shadow-sm">
                 <i class="ti tabler-lock me-2"></i> Realizar Corte
             </a>
             @else
             <span class="badge bg-danger fs-6 px-3 py-2 me-2">CERRADA</span>
             <a href="{{ route('cash-registers.print', $cashRegister) }}" target="_blank" class="btn btn-dark fw-bold shadow-sm">
                 <i class="ti tabler-printer me-2"></i> Ticket
             </a>
             <a href="{{ route('cash-registers.report', $cashRegister) }}" target="_blank" class="btn btn-primary fw-bold shadow-sm ms-2">
                 <i class="ti tabler-file-text me-2"></i> Reporte Detallado
             </a>
             @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left Column: Stats & Sales -->
        <div class="col-12 col-xl-8">
            <!-- Sales by Payment Method -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="ti tabler-cash me-2"></i>Ventas por Método de Pago</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 h-100 bg-primary">
                                <div class="card-body text-white">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="ti tabler-cash fs-3 me-2"></i>
                                        <h6 class="mb-0 text-uppercase small">Efectivo</h6>
                                    </div>
                                    <h3 class="fw-bold mb-0">${{ number_format($paymentsByMethod['cash'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 h-100 bg-danger">
                                <div class="card-body text-white">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="ti tabler-credit-card fs-3 me-2"></i>
                                        <h6 class="mb-0 text-uppercase small">Tarjeta</h6>
                                    </div>
                                    <h3 class="fw-bold mb-0">${{ number_format($paymentsByMethod['card'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 h-100 bg-info">
                                <div class="card-body text-white">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="ti tabler-device-mobile fs-3 me-2"></i>
                                        <h6 class="mb-0 text-uppercase small">Transferencia</h6>
                                    </div>
                                    <h3 class="fw-bold mb-0">${{ number_format($paymentsByMethod['transfer'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 h-100 bg-success">
                                <div class="card-body text-white">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="ti tabler-building-bank fs-3 me-2"></i>
                                        <h6 class="mb-0 text-uppercase small">Depósito</h6>
                                    </div>
                                    <h3 class="fw-bold mb-0">${{ number_format($paymentsByMethod['deposit'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card border-0 mt-3 bg-dark">
                        <div class="card-body text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold text-uppercase">Total de Ventas</h5>
                                <h2 class="mb-0 fw-bold">${{ number_format($totalSales, 2) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cash Flow Stats -->
            @php
                $sales = $cashRegister->payments->where('method', 'cash')->sum('amount');
                $in = $cashRegister->movements->where('type', 'in')->sum('amount');
                $out = $cashRegister->movements->where('type', 'out')->sum('amount');
                $expenses = $cashRegister->movements->where('type', 'expense')->sum('amount');
                $balance = $cashRegister->opening_amount + $sales + $in - $out - $expenses; 
            @endphp
            
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase small fw-bold">Monto Inicial</h6>
                            <h3 class="fw-bold text-gray-800">${{ number_format($cashRegister->opening_amount, 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                        <div class="card-body">
                            <h6 class="text-success text-uppercase small fw-bold">Ingresos Extras</h6>
                            <h3 class="fw-bold text-success">+${{ number_format($in, 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
                        <div class="card-body">
                            <h6 class="text-warning text-uppercase small fw-bold">Retiros</h6>
                            <h3 class="fw-bold text-warning">-${{ number_format($out, 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                        <div class="card-body">
                            <h6 class="text-danger text-uppercase small fw-bold">Gastos</h6>
                            <h3 class="fw-bold text-danger">-${{ number_format($expenses, 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

             <!-- Movements List -->
             <div class="card border-0 shadow-sm">
                 <div class="card-header bg-transparent py-3">
                     <h5 class="card-title mb-0 fw-bold"><i class="ti tabler-list me-2"></i>Historial de Movimientos</h5>
                 </div>
                 <div class="table-responsive">
                     <table class="table align-middle table-hover mb-0">
                         <thead class="table-light">
                             <tr>
                                 <th class="ps-4">Hora</th>
                                 <th>Tipo</th>
                                 <th>Descripción</th>
                                 <th>Usuario</th>
                                 <th class="text-end pe-4">Monto</th>
                             </tr>
                         </thead>
                         <tbody>
                             @foreach($cashRegister->movements->sortByDesc('created_at') as $movement)
                             <tr>
                                 <td class="ps-4 text-muted">{{ $movement->created_at->format('h:i A') }}</td>
                                 <td>
                                     @if($movement->type == 'in')
                                        <span class="badge bg-success-subtle text-success">Ingreso</span>
                                     @elseif($movement->type == 'out')
                                        <span class="badge bg-warning-subtle text-warning">Retiro</span>
                                     @else
                                        <span class="badge bg-danger-subtle text-danger">Gasto</span>
                                     @endif
                                 </td>
                                 <td>
                                    {{ $movement->description }}
                                    @if($movement->expenseCategory)
                                        <br><small class="text-muted">({{ $movement->expenseCategory->name }})</small>
                                    @endif
                                 </td>
                                 <td class="small">{{ $movement->user->name ?? 'Usuario' }}</td>
                                 <td class="text-end pe-4 fw-bold {{ $movement->type == 'in' ? 'text-success' : 'text-danger' }}">
                                     {{ $movement->type == 'in' ? '+' : '-' }}${{ number_format($movement->amount, 2) }}
                                 </td>
                             </tr>
                             @endforeach
                             @if($cashRegister->movements->isEmpty())
                             <tr>
                                 <td colspan="5" class="text-center py-4 text-muted">No hay movimientos registrados</td>
                             </tr>
                             @endif
                         </tbody>
                     </table>
                 </div>
             </div>
        </div>

        <!-- Right Side: Actions -->
        <div class="col-12 col-xl-4">
            @if($cashRegister->status == 'open')
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="ti tabler-plus me-2"></i>Registrar Movimiento</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('cash-registers.movements.store', $cashRegister) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Tipo de Movimiento</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="type" id="type-expense" value="expense" checked onchange="toggleCategory(true)">
                                <label class="btn btn-outline-danger" for="type-expense">Gasto</label>
                                
                                <input type="radio" class="btn-check" name="type" id="type-out" value="out" onchange="toggleCategory(false)">
                                <label class="btn btn-outline-warning" for="type-out">Retiro</label>
                                
                                <input type="radio" class="btn-check" name="type" id="type-in" value="in" onchange="toggleCategory(false)">
                                <label class="btn btn-outline-success" for="type-in">Ingreso</label>
                            </div>
                        </div>

                        <div class="mb-3" id="category-selector">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-muted mb-0">Categoría</label>
                                <a href="{{ route('expense-categories.index') }}" class="small text-decoration-none">Gestionar <i class="ti tabler-settings"></i></a>
                            </div>
                            <select name="expense_category_id" class="form-select">
                                <option value="">Selecciona una categoría</option>
                                @foreach($expenseCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Monto</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="amount" class="form-control fw-bold" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Descripción / Motivo</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Ej. Pago a proveedor, Compra de hielo..." required></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark fw-bold">Guardar Movimiento</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
            
            <!-- Expected Balance Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="ti tabler-calculator me-2"></i>Balance Esperado</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Efectivo en Caja:</span>
                        <span class="fw-bold">${{ number_format($balance, 2) }}</span>
                    </div>
                    @if($cashRegister->status == 'closed')
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Declarado:</span>
                        <span class="fw-bold">${{ number_format($cashRegister->closing_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Diferencia:</span>
                        <span class="fw-bold {{ ($cashRegister->closing_amount - $balance) < 0 ? 'text-danger' : 'text-success' }}">
                            ${{ number_format($cashRegister->closing_amount - $balance, 2) }}
                        </span>
                    </div>
                    @if($cashRegister->notes)
                    <hr>
                    <div>
                        <strong class="small text-muted d-block mb-1">Notas:</strong>
                        <p class="small mb-0">{{ $cashRegister->notes }}</p>
                    </div>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleCategory(show) {
        const selector = document.getElementById('category-selector');
        if(show) {
            selector.classList.remove('d-none');
        } else {
            selector.classList.add('d-none');
        }
    }
</script>
@endpush
