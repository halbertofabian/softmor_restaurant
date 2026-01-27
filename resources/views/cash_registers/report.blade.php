<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Detallado - Corte #{{ $cashRegister->id }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .header p {
            margin: 3px 0;
        }
        .info-section {
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-top: 15px;
            margin-bottom: 8px;
            padding-bottom: 3px;
            border-bottom: 1px solid #ddd;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        table th {
            background-color: #f5f5f5;
            padding: 6px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
        }
        table td {
            padding: 5px 6px;
            border-bottom: 1px solid #eee;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-box {
            background-color: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
            margin-top: 15px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .total-row.grand {
            font-weight: bold;
            font-size: 14px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 2px solid #000;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Reporte Detallado de Corte de Caja</h1>
        <p><strong>{{ $settings['business_name'] ?? 'Restaurant Softmor' }}</strong></p>
        <p>{{ $cashRegister->branch->name ?? 'Sucursal Principal' }}</p>
        <p>Corte #{{ $cashRegister->id }}</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span><strong>Usuario:</strong></span>
            <span>{{ $cashRegister->user->name }}</span>
        </div>
        <div class="info-row">
            <span><strong>Apertura:</strong></span>
            <span>{{ $cashRegister->opened_at->format('d/m/Y H:i:s') }}</span>
        </div>
        <div class="info-row">
            <span><strong>Cierre:</strong></span>
            <span>{{ $cashRegister->closed_at ? $cashRegister->closed_at->format('d/m/Y H:i:s') : 'En Curso' }}</span>
        </div>
    </div>

    <!-- Payment Methods Summary -->
    <div class="section-title">Resumen de Ventas por Método de Pago</div>
    <table>
        <thead>
            <tr>
                <th>Método de Pago</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>💵 Efectivo</td>
                <td class="text-right">${{ number_format($paymentsByMethod['cash'], 2) }}</td>
            </tr>
            <tr>
                <td>💳 Tarjeta</td>
                <td class="text-right">${{ number_format($paymentsByMethod['card'], 2) }}</td>
            </tr>
            <tr>
                <td>📱 Transferencia</td>
                <td class="text-right">${{ number_format($paymentsByMethod['transfer'], 2) }}</td>
            </tr>
            <tr>
                <td>🏦 Depósito (Banco)</td>
                <td class="text-right">${{ number_format($paymentsByMethod['deposit'], 2) }}</td>
            </tr>
            <tr style="font-weight: bold; background-color: #f5f5f5;">
                <td>TOTAL VENTAS</td>
                <td class="text-right">${{ number_format($totalSales, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Detailed Movements -->
    <div class="section-title">Detalle de Movimientos</div>
    <table>
        <thead>
            <tr>
                <th>Hora</th>
                <th>Tipo</th>
                <th>Descripción</th>
                <th>Usuario</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cashRegister->movements->sortBy('created_at') as $movement)
            <tr>
                <td>{{ $movement->created_at->format('H:i') }}</td>
                <td>
                    @if($movement->type == 'in')
                        <span class="badge badge-success">Ingreso</span>
                    @elseif($movement->type == 'out')
                        <span class="badge badge-warning">Retiro</span>
                    @else
                        <span class="badge badge-danger">Gasto</span>
                    @endif
                </td>
                <td>
                    {{ $movement->description }}
                    @if($movement->expenseCategory)
                        <br><small style="color: #666;">({{ $movement->expenseCategory->name }})</small>
                    @endif
                </td>
                <td>{{ $movement->user->name ?? 'N/A' }}</td>
                <td class="text-right">{{ $movement->type == 'in' ? '+' : '-' }}${{ number_format($movement->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center" style="color: #999;">No hay movimientos registrados</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Cash Flow Summary -->
    <div class="totals-box">
        <div class="section-title" style="margin-top: 0;">Flujo de Efectivo</div>
        
        <div class="total-row">
            <span>Monto Inicial:</span>
            <span>${{ number_format($cashRegister->opening_amount, 2) }}</span>
        </div>
        <div class="total-row">
            <span>+ Ventas (Efectivo):</span>
            <span>${{ number_format($sales, 2) }}</span>
        </div>
        <div class="total-row">
            <span>+ Ingresos Extras:</span>
            <span>${{ number_format($in, 2) }}</span>
        </div>
        <div class="total-row">
            <span>- Retiros:</span>
            <span>${{ number_format($out, 2) }}</span>
        </div>
        <div class="total-row">
            <span>- Gastos:</span>
            <span>${{ number_format($expenses, 2) }}</span>
        </div>
        
        <div class="total-row grand">
            <span>TOTAL ESPERADO EN CAJA:</span>
            <span>${{ number_format($expected, 2) }}</span>
        </div>

        @if($cashRegister->status == 'closed')
        <div class="total-row" style="margin-top: 10px;">
            <span>Efectivo Declarado:</span>
            <span>${{ number_format($cashRegister->closing_amount, 2) }}</span>
        </div>
        <div class="total-row" style="color: {{ ($cashRegister->closing_amount - $expected) < 0 ? '#721c24' : '#155724' }};">
            <span>Diferencia:</span>
            <span>${{ number_format($cashRegister->closing_amount - $expected, 2) }}</span>
        </div>
        @endif
    </div>

    @if($cashRegister->notes)
    <div style="margin-top: 20px;">
        <div class="section-title">Notas / Observaciones</div>
        <p style="padding: 10px; background-color: #f9f9f9; border-left: 3px solid #666;">
            {{ $cashRegister->notes }}
        </p>
    </div>
    @endif

    <div class="footer">
        <p>Reporte generado el {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>{{ $settings['business_name'] ?? 'Restaurant Softmor' }} - Sistema de Punto de Venta</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>

</body>
</html>
