@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <div class="card border-0 shadow-lg">
        <div class="card-header bg-danger text-white py-3">
            <h4 class="mb-0 fw-bold"><i class="ti tabler-lock me-2"></i> Realizar Corte de Caja</h4>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-warning mb-4">
                <i class="ti tabler-alert-triangle me-2"></i>
                <strong>Atención:</strong> Al realizar el corte, la caja se cerrará y no podrás registrar más movimientos hasta abrir una nueva.
            </div>

            <form action="{{ route('cash-registers.update', $cashRegister) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="close_register" value="1">

                <div class="row">
                    <!-- Left Column: Summary (7) -->
                    <div class="col-lg-7">
                        <h6 class="fw-bold text-muted text-uppercase mb-3">Resumen de Caja</h6>
                        <ul class="list-group list-group-flush border rounded mb-4">
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                <span class="text-muted">Monto Inicial</span>
                                <span class="fw-bold">${{ number_format($cashRegister->opening_amount, 2) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-info"><i class="ti tabler-shopping-cart me-1"></i> Ventas (Efectivo)</span>
                                <span class="fw-bold text-info">+${{ number_format($sales, 2) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-success"><i class="ti tabler-arrow-up me-1"></i> Ingresos Extras</span>
                                <span class="fw-bold text-success">+${{ number_format($in, 2) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-warning"><i class="ti tabler-arrow-down me-1"></i> Retiros</span>
                                <span class="fw-bold text-warning">-${{ number_format($out, 2) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-danger"><i class="ti tabler-receipt me-1"></i> Gastos</span>
                                <span class="fw-bold text-danger">-${{ number_format($expenses, 2) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white">
                                <span class="fw-bold text-uppercase">Total Esperado en Caja</span>
                                <span class="fw-bold fs-5">${{ number_format($expected, 2) }}</span>
                            </li>
                        </ul>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Monto Efectivo en Caja (Contado)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text border-end-0 text-success">$</span>
                                <input type="number" step="0.01" name="closing_amount" id="closingAmount" class="form-control border-start-0 ps-0 fw-bold fs-3 text-success" placeholder="0.00" required>
                            </div>
                            <div class="form-text">Cuenta todo el dinero en efectivo que tienes físicamente en la caja.</div>
                        </div>

                        <div class="card border-0 mb-4">
                            <div class="card-header border-bottom">
                                <h6 class="mb-0 fw-bold text-uppercase">
                                    <i class="ti tabler-building-bank me-2"></i>Declaración de Pagos Electrónicos
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">💳 Tarjeta</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" name="declared_card" class="form-control" placeholder="0.00" value="0">
                                        </div>
                                        <small class="text-muted">Esperado: ${{ number_format($paymentsByMethod['card'] ?? 0, 2) }}</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">📱 Transferencia</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" name="declared_transfer" class="form-control" placeholder="0.00" value="0">
                                        </div>
                                        <small class="text-muted">Esperado: ${{ number_format($paymentsByMethod['transfer'] ?? 0, 2) }}</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">🏦 Depósito</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" name="declared_deposit" class="form-control" placeholder="0.00" value="0">
                                        </div>
                                        <small class="text-muted">Esperado: ${{ number_format($paymentsByMethod['deposit'] ?? 0, 2) }}</small>
                                    </div>
                                </div>
                                <div class="form-text mt-2">Declara los montos recibidos en cada método de pago electrónico.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Notas / Observaciones</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Comentarios sobre el turno, diferencias, etc."></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('cash-registers.show', $cashRegister) }}" class="btn btn-light fw-bold">Cancelar</a>
                            <button type="submit" class="btn btn-danger fw-bold px-4">
                                <i class="ti tabler-lock me-2"></i> Cerrar Caja Definitivamente
                            </button>
                        </div>
                    </div>

                    <!-- Right Column: Denomination Calculator (5) -->
                    <div class="col-lg-5">
                        <div class="card border-0 sticky-top" style="top: 20px;">
                            <div class="card-header border-bottom">
                                <h6 class="mb-0 fw-bold text-uppercase">
                                    <i class="ti tabler-calculator me-2"></i>Contador de Denominaciones
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    @php
                                        $denominations = [1000, 500, 200, 100, 50, 20];
                                    @endphp
                                    @foreach($denominations as $denom)
                                    <div class="col-6">
                                        <div class="card border shadow-sm">
                                            <div class="card-body p-2 text-center">
                                                <div class="small text-muted mb-1">${{ $denom }}</div>
                                                <input type="number" 
                                                       class="form-control form-control-sm text-center denomination-input" 
                                                       data-value="{{ $denom }}" 
                                                       min="0" 
                                                       value="0"
                                                       placeholder="0">
                                                <div class="small text-success fw-bold mt-1 denomination-total">$0</div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                    
                                    <!-- Cambio (Monedas) -->
                                    <div class="col-12">
                                        <div class="card border shadow-sm">
                                            <div class="card-body p-2">
                                                <label class="form-label small text-muted mb-1">Cambio (Monedas)</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" 
                                                           step="0.01" 
                                                           class="form-control denomination-change" 
                                                           placeholder="0.00"
                                                           value="0">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-success mt-3 mb-0 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Total Calculado:</span>
                                    <span class="fs-4 fw-bold" id="denominationTotal">$0.00</span>
                                </div>
                                
                                <button type="button" class="btn btn-sm btn-primary w-100 mt-2" onclick="applyDenominationTotal()">
                                    <i class="ti tabler-check me-1"></i> Aplicar al Monto en Caja
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Calculate denomination totals
    function calculateDenominations() {
        let total = 0;
        
        // Calculate bills
        document.querySelectorAll('.denomination-input').forEach(input => {
            const value = parseFloat(input.dataset.value);
            const quantity = parseInt(input.value) || 0;
            const subtotal = value * quantity;
            
            // Update individual denomination total
            const totalElement = input.closest('.card-body').querySelector('.denomination-total');
            totalElement.textContent = '$' + subtotal.toFixed(2);
            
            total += subtotal;
        });
        
        // Add change (coins)
        const change = parseFloat(document.querySelector('.denomination-change').value) || 0;
        total += change;
        
        // Update grand total
        document.getElementById('denominationTotal').textContent = '$' + total.toFixed(2);
        
        return total;
    }
    
    // Apply total to closing amount field
    function applyDenominationTotal() {
        const total = calculateDenominations();
        document.getElementById('closingAmount').value = total.toFixed(2);
    }
    
    // Listen for changes in denomination inputs
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.denomination-input, .denomination-change').forEach(input => {
            input.addEventListener('input', calculateDenominations);
        });
    });
</script>
@endpush
