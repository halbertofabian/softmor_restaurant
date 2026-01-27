@extends('layouts.master')

@section('title', 'Dashboard - Restaurant Softmor')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Bienvenido a Restaurant Softmor! 🚀</h4>
            <p class="text-muted small">Este es tu panel principal. Aquí está el resumen de tu operación.</p>
        </div>
    </div>

    <!-- Top Stats Rows -->
    <div class="row g-4 mb-4">
        <!-- Main Chart Section (Ventas Diarias) -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ventas Mensuales ({{ date('Y') }})</h5>
                    <!-- Optional: Dropdown for period selection -->
                </div>
                <div class="card-body">
                    <div id="salesChart" style="height: 250px;"></div>
                </div>
            </div>
        </div>

        <!-- Right Side Stats -->
        <div class="col-lg-5">
            <div class="row g-4">
                <!-- Ventas Hoy -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2 text-primary">
                                <i class="ti tabler-currency-dollar fs-4"></i>
                                <span class="fw-bold">Ventas Hoy</span>
                            </div>
                            <h2 class="mb-1 fw-bold">${{ number_format($salesToday, 0) }}</h2>
                        </div>
                    </div>
                </div>

                <!-- Pedidos -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2 text-info">
                                <i class="ti tabler-file-invoice fs-4"></i>
                                <span class="fw-bold">Pedidos</span>
                            </div>
                            <h2 class="mb-1 fw-bold">{{ $ordersCount }}</h2>
                            <small class="text-muted">Total registrados</small>
                        </div>
                    </div>
                </div>

                <!-- Mesas Ocupadas -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2 text-danger">
                                <i class="ti tabler-users fs-4"></i>
                                <span class="fw-bold">Mesas Ocupadas</span>
                            </div>
                            <h2 class="mb-1 fw-bold">{{ $tablesStat }}</h2>
                            <!-- Progress Bar Simulation -->
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $activeTables > 0 ? ($occupiedTables / $activeTables * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ticket Promedio -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2 text-success">
                                <i class="ti tabler-ticket fs-4"></i>
                                <span class="fw-bold">Ticket Promedio</span>
                            </div>
                            <h2 class="mb-1 fw-bold">${{ number_format($avgTicket, 2) }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Lists -->
    <div class="row g-4">
        <!-- Ultimos Pedidos -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Últimos Pedidos</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Fecha</th>
                                <th>Mesa</th>
                                <th>Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach($latestOrders as $order)
                            <tr>
                                <td>#{{ $order->id }}</td>
                                <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                <td><span class="badge bg-label-secondary text-dark">{{ $order->table->name ?? 'N/A' }}</span></td>
                                <td class="fw-bold">${{ number_format($order->total, 2) }}</td>
                                <td>
                                    <span class="badge {{ $order->status == 'closed' ? 'bg-label-success' : 'bg-label-primary' }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Productos Mas Vendidos & Resumen -->
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Productos Más Vendidos</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        @foreach($topProducts as $product)
                        <div class="d-flex justify-content-between align-items-center pb-2 border-bottom">
                            <div>
                                <span class="d-block fw-bold">{{ $product->product_name }}</span>
                                <small class="text-muted">{{ $product->qty }} vendidos</small>
                            </div>
                            <span class="fw-bold text-primary">${{ number_format($product->total, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <div class="card bg-primary text-white">
                <div class="card-body">
                     <div class="d-flex align-items-center justify-content-between">
                         <div>
                            <h5 class="mb-1 text-white">Resumen de Comandas</h5>
                            <p class="mb-0 text-white-50">Total procesado</p>
                         </div>
                         <div class="text-end">
                             <i class="ti tabler-sparkles fs-3 mb-1"></i>
                             <h3 class="mb-0 fw-bold text-white">${{ number_format($totalOrdersValue, 2) }}</h3>
                         </div>
                     </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.46.0/dist/apexcharts.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts library could not be loaded.');
            document.querySelector("#salesChart").innerHTML = '<div class="alert alert-danger">Error: No se pudo cargar la librería de gráficas. Verifique su conexión a internet.</div>';
            return;
        }

        try {
            // Helper to get CSS variable with fallback
            function getVar(name) {
                 var val = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
                 return val ? val : null;
            }

            // Get Theme Colors with explicit fallbacks
            // Check if dark mode is active to assume colors if vars are missing
            var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            
            const primaryColor = getVar('--bs-primary') || '#696cff'; // Default purple
            const cardColor = getVar('--bs-card-bg') || (isDark ? '#2f3349' : '#ffffff');
            const bodyColor = getVar('--bs-body-color') || (isDark ? '#b6bee3' : '#697a8d');
            const borderColor = getVar('--bs-border-color') || (isDark ? '#44475b' : '#d9dee3');

            var options = {
                series: [{
                    name: 'Ventas',
                    data: [{{ implode(',', $salesData) }}]
                }],
                chart: {
                    type: 'area', // Changed to Area for a more premium look
                    height: 300,
                    parentHeightOffset: 0,
                    toolbar: { show: false },
                    background: 'transparent',
                    fontFamily: 'Public Sans, sans-serif'
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3, // Fade to transparent
                        stops: [0, 90, 100]
                    }
                },
                dataLabels: { enabled: false },
                stroke: {
                    curve: 'smooth',
                    width: 3,
                    colors: [primaryColor]
                },
                colors: [primaryColor],
                grid: {
                    borderColor: borderColor,
                    strokeDashArray: 4,
                    padding: { top: -20, bottom: -10, left: -10 }
                },
                xaxis: {
                    categories: [{!! "'" . implode("','", $months) . "'" !!}],
                    labels: {
                        style: { colors: bodyColor, fontSize: '13px' }
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        style: { colors: bodyColor, fontSize: '13px' },
                        formatter: function (val) {
                            return "$" + val;
                        }
                    }
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    y: {
                        formatter: function(val) {
                            return "$" + val;
                        }
                    }
                }
            };

            var chartEl = document.querySelector("#salesChart");
            if(chartEl) {
                var chart = new ApexCharts(chartEl, options);
                chart.render();
            } else {
                console.error("Chart container #salesChart not found");
            }
        } catch (e) {
            console.error("Error rendering chart:", e);
            document.querySelector("#salesChart").innerHTML = '<div class="text-danger">Error rendering chart: ' + e.message + '</div>';
        }
    });
</script>
@endpush
