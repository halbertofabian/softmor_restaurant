<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #{{ $order->id }}</title>
    <style>
        @page {
            margin: 0;
            size: {{ $settings['ticket_printer_width'] ?? '80mm' }} auto;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: {{ $settings['ticket_font_size'] ?? '12' }}px;
            line-height: 1.3;
            margin-top: {{ $settings['ticket_margin_top'] ?? '0' }}mm;
            margin-left: {{ $settings['ticket_margin_left'] ?? '0' }}mm;
            margin-right: {{ $settings['ticket_margin_right'] ?? '0' }}mm;
            padding: 10px;
            width: {{ $settings['ticket_printer_width'] ?? '80mm' }};
            box-sizing: border-box;
            background: #fff;
            color: #000 !important;
            font-weight: 600;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
            -webkit-font-smoothing: none;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .uppercase { text-transform: uppercase; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .border-b { border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px; }
        .flex { display: flex; justify-content: space-between; }
        .text-muted { color: #000; opacity: .8; }
        .no-print { display: none !important; }
        .ticket-header { text-align: center; margin-bottom: 8px; }
        .ticket-title { font-size: 1.2em; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .info-row { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 2px; }
        .info-label { white-space: nowrap; }
        .info-value { text-align: right; flex: 1; }
        .item-row { margin-bottom: 6px; }
        .item-name { word-break: break-word; }
        .item-total { text-align: right; font-weight: 600; }
        .section-title { text-align: center; font-weight: 700; margin: 4px 0; }
        .ticket-total { display: flex; justify-content: space-between; font-size: 1.15em; font-weight: 700; }
        
        @media print {
            body { padding: 0; width: 100%; }
        }

        .btn {
            background: #000;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="ticket-header">
        <div class="ticket-title">Gestional Food</div>
        <div>Surcursal: {{ $order->branch->name ?? 'Principal' }}</div>
        <div>{{ now()->format('d/m/Y H:i A') }}</div>
        <div>Ticket #: {{ $order->id }}</div>
    </div>

    <div class="divider"></div>
    <div class="info-row">
        <span class="info-label">Atendido por:</span>
        <span class="info-value">{{ $order->user->name ?? 'Sistema' }}</span>
    </div>
    <div class="divider"></div>

    @foreach($order->details as $detail)
    <div class="item-row">
        <div class="item-name">{{ $detail->quantity }} x {{ $detail->product->name ?? 'Producto' }}</div>
        <div class="item-total">${{ number_format($detail->price * $detail->quantity, 2) }}</div>
    </div>
    @endforeach

    <div class="divider"></div>

    <div class="ticket-total">
        <span>TOTAL:</span>
        <span>${{ number_format($order->total, 2) }}</span>
    </div>

    @if(isset($isPreCheck) && $isPreCheck)
    <div class="text-center font-bold" style="font-size: 1.1em; margin-top: 10px;">{{ $settings['ticket_pre_check_header'] ?? '*** CUENTA DE CONSUMO ***' }}</div>
    <div class="text-center mb-2" style="font-size: 0.8em;">{{ $settings['ticket_pre_check_disclaimer'] ?? 'No válido como comprobante fiscal' }}</div>
    @endif

    @if($order->status === 'closed' && !isset($isPreCheck))
    @php
        $paymentTotals = $order->payments->groupBy('method')->map(function($group){
            return $group->sum('amount');
        });
    @endphp
    <div class="divider"></div>
    <div class="mb-2" style="margin-top: 5px;">
        <div class="section-title">METODOS DE PAGO</div>
        @foreach($paymentTotals as $method => $amount)
        <div class="info-row">
            <span>{{ ucfirst($method) }}:</span>
            <span>${{ number_format($amount, 2) }}</span>
        </div>
        @endforeach
        @foreach($order->payments as $payment)
            @if($payment->reference)
            <div class="text-muted" style="font-size: 10px;">Ref: {{ $payment->reference }}</div>
            @endif
        @endforeach
    </div>
    @endif

    @if(isset($isPreCheck) && $isPreCheck && ($settings['ticket_tips_enabled'] ?? false))
    <div class="border-b"></div>
    <div class="mb-2" style="margin-top: 5px;">
        <div class="text-center font-bold mb-1">Propina Sugerida</div>
        @php
            $tip1 = $settings['ticket_tip_1_percent'] ?? 10;
            $tip2 = $settings['ticket_tip_2_percent'] ?? 12;
            $tip3 = $settings['ticket_tip_3_percent'] ?? 15;
            $tip4 = $settings['ticket_tip_4_percent'] ?? 18;
        @endphp
        <div class="flex">
            <span>{{ $tip1 }}%:</span>
            <span>${{ number_format($order->total * ($tip1 / 100), 2) }}</span>
        </div>
        <div class="flex">
            <span>{{ $tip2 }}%:</span>
            <span>${{ number_format($order->total * ($tip2 / 100), 2) }}</span>
        </div>
        <div class="flex">
            <span>{{ $tip3 }}%:</span>
            <span>${{ number_format($order->total * ($tip3 / 100), 2) }}</span>
        </div>
        <div class="flex">
            <span>{{ $tip4 }}%:</span>
            <span>${{ number_format($order->total * ($tip4 / 100), 2) }}</span>
        </div>
    </div>
    @endif
    
    <div class="text-center" style="margin-top: 15px;">
        @if(isset($isPreCheck) && $isPreCheck)
        {!! nl2br(e($settings['ticket_footer_message'] ?? (!empty($settings['ticket_footer_message']) ? $settings['ticket_footer_message'] : "Gracias por su preferencia\n¡Le esperamos pronto!"))) !!}
        @else
        {!! nl2br(e($settings['ticket_footer_message'] ?? '¡Gracias por su visita!')) !!}
        @endif
    </div>

    @if(!isset($isPdf))
    <div class="no-print" style="margin-top: 20px;">
        <button onclick="window.print()" class="btn">Imprimir Ticket</button>
        <a href="{{ route('pos.ticket.pdf', $order) }}" class="btn" style="background:#6c757d; margin-top: 5px; text-decoration: none; display: inline-block; text-align: center;">Descargar PDF</a>
        <a href="{{ route('pos.print', $order) }}" class="btn" style="background:#28a745; margin-top: 5px; text-decoration: none; display: inline-block; text-align: center;">Impresión Directa (Térmica)</a>
        <a href="{{ route('tables.index') }}" style="display: block; text-align: center; margin-top: 10px; color: #000;">Volver a Mesas</a>
    </div>
    @endif

    <script>
        window.addEventListener('load', function () {
            window.print();
        });

        window.addEventListener('afterprint', function () {
            window.close();
            setTimeout(function () {
                window.location.href = 'about:blank';
            }, 150);
        });
    </script>
</body>
</html>
