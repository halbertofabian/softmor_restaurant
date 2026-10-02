<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de materias primas</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10px; }
        h1 { margin: 0; font-size: 20px; text-align: center; }
        .subtitle { text-align: center; margin: 4px 0; color: #4b5563; }
        .rule { border-top: 2px solid #111827; margin: 14px 0; }
        .summary { width: 100%; margin-bottom: 16px; border-spacing: 8px 0; }
        .summary td { background: #f3f4f6; border-left: 4px solid #f59e0b; padding: 9px 12px; }
        .summary strong { display: block; font-size: 16px; margin-top: 3px; }
        table.inventory { width: 100%; border-collapse: collapse; }
        .inventory th { background: #e5e7eb; text-transform: uppercase; font-size: 9px; text-align: left; }
        .inventory th, .inventory td { border: 1px solid #9ca3af; padding: 7px; }
        .inventory tbody tr:nth-child(even) { background: #f9fafb; }
        .number { text-align: right; }
        .center { text-align: center; }
        .active { color: #047857; font-weight: bold; }
        .inactive { color: #6b7280; font-weight: bold; }
        .low { color: #dc2626; font-weight: bold; }
        .footer { margin-top: 12px; color: #6b7280; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <h1>REPORTE DE MATERIAS PRIMAS</h1>
    <div class="subtitle"><strong>{{ $branch?->name ?? 'Sucursal' }}</strong></div>
    <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>
    <div class="rule"></div>
    <table class="summary"><tr><td>Total registradas<strong>{{ $summary['total'] }}</strong></td><td>Activas<strong>{{ $summary['active'] }}</strong></td><td>Inactivas<strong>{{ $summary['inactive'] }}</strong></td><td>Bajo mínimo<strong class="{{ $summary['low_stock'] > 0 ? 'low' : '' }}">{{ $summary['low_stock'] }}</strong></td></tr></table>
    <table class="inventory">
        <thead><tr><th>Materia prima</th><th class="number">Existencia</th><th class="number">Equivalencia</th><th class="number">Mínimo</th><th class="center">Recetas</th><th class="center">Estado</th><th>Último movimiento</th></tr></thead>
        <tbody>
            @forelse($items as $item)
                @php
                    $isLow = $item->min_stock !== null && (float) $item->stock <= (float) $item->min_stock;
                    $equivalent = match($item->base_unit) {
                        'g' => number_format((float) $item->stock / 1000, 3) . ' kg',
                        'ml' => number_format((float) $item->stock / 1000, 3) . ' l',
                        default => '-',
                    };
                @endphp
                <tr><td>{{ $item->name }}</td><td class="number {{ $isLow ? 'low' : '' }}">{{ number_format((float) $item->stock, 3) }} {{ $item->base_unit }}</td><td class="number">{{ $equivalent }}</td><td class="number">{{ $item->min_stock === null ? '-' : number_format((float) $item->min_stock, 3) . ' ' . $item->base_unit }}</td><td class="center">{{ $item->recipe_items_count }}</td><td class="center {{ $item->status ? 'active' : 'inactive' }}">{{ $item->status ? 'Activo' : 'Inactivo' }}</td><td>{{ $item->movements_max_created_at ? \Carbon\Carbon::parse($item->movements_max_created_at)->format('d/m/Y H:i') : '-' }}</td></tr>
            @empty
                <tr><td colspan="7" class="center">No hay materias primas registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Las equivalencias son informativas. Las existencias se almacenan en la unidad base indicada.</div>
</body>
</html>
