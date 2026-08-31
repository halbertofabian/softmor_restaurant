<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas por mesero</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            color: #202124;
            background: #f4f5f7;
        }

        .report {
            max-width: 1400px;
            margin: 0 auto;
            padding: 28px;
            background: #fff;
            border: 1px solid #d9dde3;
        }

        .header {
            margin-bottom: 22px;
            padding-bottom: 14px;
            text-align: center;
            border-bottom: 2px solid #202124;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 22px;
        }

        .header p {
            margin: 3px 0;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            max-width: 1400px;
            margin: 0 auto 12px;
        }

        .toolbar button {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            border: 1px solid #c5c9d0;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            background: #fff;
        }

        .toolbar svg {
            width: 17px;
            height: 17px;
        }

        .toolbar .primary {
            color: #111;
            border-color: #ffab1d;
            background: #ffab1d;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .area-section + .area-section {
            margin-top: 24px;
        }

        .area-title {
            margin: 0 0 8px;
            padding: 8px 10px;
            font-size: 15px;
            text-transform: uppercase;
            border-left: 4px solid #ffab1d;
            background: #f4f5f7;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th,
        td {
            padding: 7px 8px;
            border: 1px solid #9da3ad;
        }

        thead th,
        tfoot th,
        tfoot td {
            font-weight: bold;
            background: #eceff3;
        }

        th:first-child,
        td:first-child {
            min-width: 210px;
            text-align: left;
        }

        th:not(:first-child),
        td:not(:first-child) {
            min-width: 85px;
            text-align: center;
        }

        .empty {
            padding: 28px;
            text-align: center;
            color: #6c737f;
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .report {
                max-width: none;
                padding: 8mm;
                border: 0;
            }

            .toolbar {
                display: none;
            }

            table {
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.close()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
            Cerrar
        </button>
        <button type="button" class="primary" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                <path d="M6 14h12v8H6z" />
            </svg>
            Imprimir
        </button>
    </div>

    <main class="report">
        <header class="header">
            <h1>REPORTE DE VENTAS POR MESERO</h1>
            <p><strong>{{ $branch?->name ?? 'Sucursal' }}</strong></p>
            <p>{{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }} - {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }}</p>
        </header>

        @if($areas->isEmpty())
            <div class="empty">Sin ventas para estos filtros.</div>
        @else
            @foreach($areas as $area)
                <section class="area-section">
                    <h2 class="area-title">{{ $area['name'] }}</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    @foreach($waiters as $waiter)
                                        <th>{{ $waiter['name'] }}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($area['rows'] as $row)
                                    <tr>
                                        <td>{{ $row['name'] }}</td>
                                        @foreach($waiters as $waiter)
                                            <td>{{ $row['quantities'][$waiter['key']] ?: '-' }}</td>
                                        @endforeach
                                        <td><strong>{{ $row['total'] }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    @foreach($waiters as $waiter)
                                        <td>{{ $area['waiterTotals'][$waiter['key']] }}</td>
                                    @endforeach
                                    <td>{{ $area['waiterTotals']->sum() }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            @endforeach
        @endif
    </main>
</body>
</html>
