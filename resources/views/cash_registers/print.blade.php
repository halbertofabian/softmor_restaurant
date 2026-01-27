<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corte de Caja #{{ $cashRegister->id }}</title>
    <style>
        @page {
            margin: 0;
            size: {{ $settings['ticket_printer_width'] ?? '80mm' }} auto;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: {{ $settings['ticket_font_size'] ?? '12' }}px;
            margin-top: {{ $settings['ticket_margin_top'] ?? '0' }}mm;
            margin-left: {{ $settings['ticket_margin_left'] ?? '0' }}mm;
            margin-right: {{ $settings['ticket_margin_right'] ?? '0' }}mm;
            padding: 10px;
            width: {{ $settings['ticket_printer_width'] ?? '80mm' }};
            background: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 1.25em;
            text-transform: uppercase;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .totals {
            margin-top: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 1.1em;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h2>Restaurant Softmor</h2>
        <p>Corte de Caja #{{ $cashRegister->id }}</p>
        <p>{{ $cashRegister->branch->name ?? 'Sucursal Principal' }}</p>
    </div>

    <div class="info-row">
        <span>Usuario:</span>
        <span>{{ $cashRegister->user->name }}</span>
    </div>
    <div class="info-row">
        <span>Apertura:</span>
        <span>{{ $cashRegister->opened_at->format('d/m/Y H:i') }}</span>
    </div>
    <div class="info-row">
        <span>Cierre:</span>
        <span>{{ $cashRegister->closed_at ? $cashRegister->closed_at->format('d/m/Y H:i') : 'En Curso' }}</span>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span>Monto Inicial:</span>
        <span>${{ number_format($cashRegister->opening_amount, 2) }}</span>
    </div>
    <div class="info-row">
        <span>+ Ventas (Efectivo):</span>
        <span>${{ number_format($sales, 2) }}</span>
    </div>
    <div class="info-row">
        <span>+ Ingresos Extras:</span>
        <span>${{ number_format($in, 2) }}</span>
    </div>
    <div class="info-row">
        <span>- Retiros:</span>
        <span>${{ number_format($out, 2) }}</span>
    </div>
    <div class="info-row">
        <span>- Gastos:</span>
        <span>${{ number_format($expenses, 2) }}</span>
    </div>

    <div class="divider"></div>

    <div class="total-row">
        <span>Total Esperado:</span>
        <span>${{ number_format($expected, 2) }}</span>
    </div>

    @if($cashRegister->status == 'closed')
    <div class="info-row" style="margin-top: 5px;">
        <span>Declarado:</span>
        <span>${{ number_format($cashRegister->closing_amount, 2) }}</span>
    </div>
    <div class="info-row">
        <span>Diferencia:</span>
        <span style="{{ ($cashRegister->closing_amount - $expected) < 0 ? 'color: red;' : '' }}">
            ${{ number_format($cashRegister->closing_amount - $expected, 2) }}
        </span>
    </div>
    @endif

    <div class="divider"></div>
    
    <div style="text-align: center; margin-bottom: 10px;">
        <strong>PAGOS ELECTRONICOS</strong>
    </div>
    
    <div class="info-row">
        <span>Tarjeta:</span>
        <span>${{ number_format($paymentsByMethod['card'], 2) }}</span>
    </div>
    @if($cashRegister->status == 'closed' && $cashRegister->declared_card > 0)
    <div class="info-row" style="font-size: 0.9em;">
        <span>  Declarado:</span>
        <span>${{ number_format($cashRegister->declared_card, 2) }}</span>
    </div>
    @endif
    
    <div class="info-row">
        <span>Transferencia:</span>
        <span>${{ number_format($paymentsByMethod['transfer'], 2) }}</span>
    </div>
    @if($cashRegister->status == 'closed' && $cashRegister->declared_transfer > 0)
    <div class="info-row" style="font-size: 0.9em;">
        <span>  Declarado:</span>
        <span>${{ number_format($cashRegister->declared_transfer, 2) }}</span>
    </div>
    @endif
    
    <div class="info-row">
        <span>Deposito:</span>
        <span>${{ number_format($paymentsByMethod['deposit'], 2) }}</span>
    </div>
    @if($cashRegister->status == 'closed' && $cashRegister->declared_deposit > 0)
    <div class="info-row" style="font-size: 0.9em;">
        <span>  Declarado:</span>
        <span>${{ number_format($cashRegister->declared_deposit, 2) }}</span>
    </div>
    @endif
    
    <div class="divider"></div>
    
    <div class="total-row">
        <span>Total Ventas:</span>
        <span>${{ number_format($totalSales, 2) }}</span>
    </div>

    <div class="divider"></div>
        
    <div style="text-align: left;">
        <strong>Notas:</strong><br>
        {{ $cashRegister->notes ?? 'Sin observaciones' }}
    </div>

    <div class="footer">
        <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
        <p>*** Fin del Reporte ***</p>
    </div>

</body>
</html>
