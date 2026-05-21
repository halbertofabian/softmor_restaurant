@extends('layouts.master')

@section('content')
<div class="container-fluid py-4 cash-close-page">
    <div class="card border-0 shadow-lg cash-close-main">
        <div class="card-header bg-danger text-white py-3 cash-close-header">
            <h4 class="mb-0 fw-bold"><i class="ti tabler-lock me-2"></i> Realizar Corte de Caja</h4>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-warning mb-4">
                <i class="ti tabler-alert-triangle me-2"></i>
                <strong>Atención:</strong> Al realizar el corte, la caja se cerrará y no podrás registrar más movimientos hasta abrir una nueva.
            </div>

            <form id="cash-close-form" action="{{ route('cash-registers.update', $cashRegister) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="close_register" value="1">

                <div class="row g-4 cash-close-layout">
                    <!-- Left Column: Summary (7) -->
                    <div class="col-lg-7 cash-close-col-left">
                        <h6 class="fw-bold text-muted text-uppercase mb-3"><span class="cash-step">1</span> Resumen de Caja</h6>
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

                        <div class="card border-0 mb-4 cash-close-inner-card">
                            <div class="card-header border-bottom cash-close-inner-header">
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
                            <button id="cash-close-submit" type="submit" class="btn btn-danger fw-bold px-4">
                                <i class="ti tabler-lock me-2"></i> Cerrar Caja Definitivamente
                            </button>
                        </div>
                    </div>

                    <!-- Right Column: Denomination Calculator (5) -->
                    <div class="col-lg-5 cash-close-col-right">
                        <div class="card border-0 sticky-top cash-close-inner-card cash-denom-panel" style="top: 20px;">
                            <div class="card-header border-bottom cash-close-inner-header">
                                <h6 class="mb-0 fw-bold text-uppercase">
                                    <span class="cash-step">2</span> <i class="ti tabler-calculator me-2"></i>Contador de Denominaciones
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

@push('styles')
<style>
    .cash-close-page.container-fluid {
        max-width: 1440px;
    }
    .cash-close-page .cash-close-main {
        background: linear-gradient(180deg, rgba(56, 63, 103, 0.82) 0%, rgba(49, 56, 92, 0.86) 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 20px 44px rgba(8, 11, 24, 0.35);
    }
    .cash-close-page .cash-close-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        background: linear-gradient(120deg, #ff4d5a 0%, #f44f67 100%) !important;
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
    }
    .cash-close-page .cash-close-header h4 {
        font-size: 2rem;
    }
    .cash-close-page .cash-close-inner-card {
        background: rgba(35, 41, 71, 0.88);
        border: 1px solid rgba(255, 255, 255, 0.09);
        border-radius: 0.9rem;
        box-shadow: 0 12px 28px rgba(9, 11, 24, 0.25);
    }
    .cash-close-page .cash-close-inner-header {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.08) !important;
        padding-top: .9rem;
        padding-bottom: .9rem;
    }
    .cash-close-page .cash-close-layout .cash-close-col-left {
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        padding-right: 1.25rem;
    }
    .cash-close-page .cash-close-layout .cash-close-col-right {
        padding-left: 1.25rem;
    }
    .cash-close-page .list-group-item {
        background: transparent;
        border-color: rgba(255, 255, 255, 0.08);
        padding-top: .95rem;
        padding-bottom: .95rem;
    }
    .cash-close-page .input-group-text,
    .cash-close-page .form-control {
        border-color: rgba(255, 255, 255, 0.12);
        min-height: 44px;
    }
    .cash-close-page #closingAmount {
        letter-spacing: 0.5px;
        font-size: 2.15rem !important;
    }
    .cash-close-page .card-body {
        padding: 1.25rem;
    }
    .cash-close-page .cash-step {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.4rem;
        height: 1.4rem;
        margin-right: .5rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700;
        color: #11152b;
        background: #ffb020;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .25);
    }
    .cash-close-page .alert-warning {
        background: rgba(255, 176, 32, .16);
        border: 1px solid rgba(255, 176, 32, .3);
        color: #ffd58a;
    }
    .cash-close-page .list-group {
        overflow: hidden;
        border-radius: .75rem;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
    }
    .cash-close-page .list-group-item.bg-dark {
        background: rgba(123, 128, 184, .6) !important;
    }
    .cash-close-page .form-text {
        color: rgba(218, 223, 246, .76);
    }
    .cash-close-page .btn-danger {
        box-shadow: 0 10px 24px rgba(244, 79, 103, .28);
    }
    .cash-close-page .btn-danger,
    .cash-close-page .btn-light,
    .cash-close-page .btn-primary {
        min-height: 44px;
        border-radius: .7rem;
    }
    .cash-close-page .cash-denom-panel .card.border.shadow-sm {
        background: rgba(255, 255, 255, 0.02);
        border-color: rgba(255, 255, 255, 0.08) !important;
    }
    .cash-close-page .cash-denom-panel .alert-success {
        background: rgba(34, 197, 94, .16);
        border: 1px solid rgba(34, 197, 94, .32);
        color: #7ef6af;
    }
    @media (max-width: 991.98px) {
        .cash-close-page .cash-close-layout .cash-close-col-left,
        .cash-close-page .cash-close-layout .cash-close-col-right {
            border-right: 0;
            padding-right: 0;
            padding-left: 0;
        }
        .cash-close-page .cash-close-header h4 {
            font-size: 1.45rem;
        }
    }
    .cash-close-page #denominationTotal {
        letter-spacing: .3px;
    }
</style>
@endpush

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
        const closeForm = document.getElementById('cash-close-form');
        const cardLabel = document.querySelector('input[name="declared_card"]')?.closest('.col-md-4')?.querySelector('label');
        const transferLabel = document.querySelector('input[name="declared_transfer"]')?.closest('.col-md-4')?.querySelector('label');
        const depositLabel = document.querySelector('input[name="declared_deposit"]')?.closest('.col-md-4')?.querySelector('label');

        if (cardLabel) {
            cardLabel.innerHTML = '<i class="ti tabler-credit-card me-1"></i> Tarjeta';
        }
        if (transferLabel) {
            transferLabel.innerHTML = '<i class="ti tabler-arrows-transfer-up-down me-1"></i> Transferencia';
        }
        if (depositLabel) {
            depositLabel.innerHTML = '<i class="ti tabler-building-bank me-1"></i> Deposito';
        }

        if (closeForm) {
            closeForm.addEventListener('keydown', function(event) {
                if (event.key !== 'Enter') {
                    return;
                }

                if (event.target && event.target.tagName === 'TEXTAREA') {
                    return;
                }

                event.preventDefault();
            });

            closeForm.addEventListener('submit', function(event) {
                event.preventDefault();

                if (window.GF && typeof window.GF.showModalConfirm === 'function') {
                    window.GF.showModalConfirm(
                        'Confirmar accion',
                        'Seguro que quieres cerrar caja definitivamente?',
                        function() { closeForm.submit(); }
                    );
                    return;
                }

                closeForm.submit();
            });
        }

        document.querySelectorAll('.denomination-input, .denomination-change').forEach(input => {
            input.addEventListener('input', calculateDenominations);
        });
    });
</script>
@endpush
