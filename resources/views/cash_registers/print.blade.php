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
            margin: 0;
            padding: 0;
            width: {{ $settings['ticket_printer_width'] ?? '80mm' }};
            background: #fff;
            color: #000 !important;
            font-weight: 600;
            line-height: 1.35;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
            -webkit-font-smoothing: none;
        }
        .ticket {
            box-sizing: border-box;
            margin-top: {{ $settings['ticket_margin_top'] ?? '0' }}mm;
            margin-left: {{ $settings['ticket_margin_left'] ?? '0' }}mm;
            margin-right: {{ $settings['ticket_margin_right'] ?? '0' }}mm;
            padding: 10px;
            width: calc(
                {{ $settings['ticket_printer_width'] ?? '80mm' }}
                - {{ $settings['ticket_margin_left'] ?? '0' }}mm
                - {{ $settings['ticket_margin_right'] ?? '0' }}mm
            );
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 1.25em;
            text-transform: uppercase;
            font-weight: 700;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 5px;
        }
        .info-row span:last-child {
            text-align: right;
            overflow-wrap: anywhere;
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
            font-weight: 700;
            font-size: 1.1em;
        }
        strong {
            font-weight: 700;
        }
        .section-title {
            margin: 12px 0 8px;
            padding: 4px 0;
            text-align: center;
            font-weight: 700;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        .detail-item {
            margin-bottom: 7px;
        }
        .detail-meta {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 0.9em;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="ticket">

    <div class="header">
        <h2>{{ $cashRegister->status == 'closed' ? 'CORTE DE CAJA' : 'PRE CORTE DE CAJA' }}</h2>
        <p><strong>{{ $cashRegister->branch->name ?? 'Sucursal Principal' }}</strong></p>
        <p>Folio #{{ $cashRegister->id }}</p>
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
    <div class="info-row">
        <span>Ventas cobradas:</span>
        <span>{{ $salesCount }}</span>
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
        <span>Total en Caja:</span>
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

    @foreach([
        'in' => 'ENTRADAS DE EFECTIVO',
        'out' => 'RETIROS DE EFECTIVO',
        'expense' => 'GASTOS DE EFECTIVO'
    ] as $movementType => $movementTitle)
        @php($movementItems = $cashRegister->movements->where('type', $movementType))
        @if($movementItems->isNotEmpty())
            <div class="section-title">{{ $movementTitle }}</div>
            @foreach($movementItems as $movement)
                <div class="detail-item">
                    <div>{{ $movement->description }}</div>
                    <div class="detail-meta">
                        <span>{{ $movement->created_at->format('H:i') }} - {{ $movement->user->name ?? 'Sin usuario' }}</span>
                        <strong>${{ number_format($movement->amount, 2) }}</strong>
                    </div>
                </div>
            @endforeach
        @endif
    @endforeach

    @if($waiterSales->isNotEmpty())
        <div class="section-title">VENTAS POR MESERO</div>
        @foreach($waiterSales as $waiter)
            <div class="detail-item">
                <div class="info-row">
                    <span>{{ $waiter['name'] }}</span>
                    <strong>${{ number_format($waiter['amount'], 2) }}</strong>
                </div>
                <div class="detail-meta">
                    <span>Comandas: {{ $waiter['orders'] }}</span>
                </div>
            </div>
        @endforeach
    @endif

    <div class="divider"></div>
        
    <div style="text-align: left;">
        <strong>Notas:</strong><br>
        {{ $cashRegister->notes ?? 'Sin observaciones' }}
    </div>

    <div class="footer">
        <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
        <p>*** Fin del Reporte ***</p>
    </div>
    </div>

    <script>
        window.addEventListener('afterprint', function () {
            window.close();
            setTimeout(function () {
                window.location.href = 'about:blank';
            }, 150);
        });
    </script>

</body>
</html>
