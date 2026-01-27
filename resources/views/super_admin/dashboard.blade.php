@extends('layouts.master')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="py-3 mb-0">
            <span class="text-muted fw-light">Super Admin /</span> Dashboard
        </h4>
        <span class="badge bg-label-primary">{{ now()->format('d M Y') }}</span>
    </div>

    <!-- KPIs -->
    <div class="row g-4 mb-4">
        <!-- Tenants -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="avatar bg-label-primary rounded p-2">
                             <i class="ti tabler-building-store fs-2"></i>
                        </div>
                        <div class="dropdown">
                             <button class="btn p-0" type="button" data-bs-toggle="dropdown"><i class="ti tabler-dots-vertical text-muted"></i></button>
                             <div class="dropdown-menu dropdown-menu-end">
                                 <a class="dropdown-item" href="{{ route('super-admin.tenants') }}">Ver Todos</a>
                             </div>
                        </div>
                    </div>
                    <span class="d-block text-muted text-uppercase small mb-1">Total Tenants</span>
                    <h2 class="card-title mb-0">{{ $totalTenants }}</h2>
                    <small class="text-success fw-medium">
                        <i class="ti tabler-trending-up me-1"></i> +{{ $newTenantsThisMonth }} este mes
                    </small>
                </div>
            </div>
        </div>

        <!-- Orders -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="avatar bg-label-success rounded p-2">
                            <i class="ti tabler-shopping-cart fs-2"></i>
                        </div>
                    </div>
                    <span class="d-block text-muted text-uppercase small mb-1">Total Órdenes</span>
                    <h2 class="card-title mb-0">{{ number_format($totalOrders) }}</h2>
                    <small class="text-muted">Global Histórico</small>
                </div>
            </div>
        </div>

        <!-- Sales -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="avatar bg-label-warning rounded p-2">
                            <i class="ti tabler-cash fs-2"></i>
                        </div>
                    </div>
                    <span class="d-block text-muted text-uppercase small mb-1">Ventas Totales</span>
                    <h2 class="card-title mb-0 text-success">${{ number_format($totalSales, 2) }}</h2>
                    <small class="text-muted">Ingresos Globales</small>
                </div>
            </div>
        </div>

        <!-- Active Users -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="avatar bg-label-info rounded p-2">
                            <i class="ti tabler-users fs-2"></i>
                        </div>
                    </div>
                    <span class="d-block text-muted text-uppercase small mb-1">Usuarios Activos</span>
                    <h2 class="card-title mb-0">{{ $activeUsers }}</h2>
                    <small class="text-info fw-medium">En las últimas 24h</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts & Tables -->
    <div class="row g-4">
        <!-- Tenants List -->
        <div class="col-xl-8 col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 card-title">
                        <i class="ti tabler-list me-2"></i> Tenants Activos
                    </h5>
                    <a href="{{ route('super-admin.tenants') }}" class="btn btn-sm btn-label-primary">Ver Todos</a>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th class="text-center">Usuarios</th>
                                <th class="text-center">Órdenes</th>
                                <th class="text-end">Ventas</th>
                                <th class="text-end">Actividad</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @forelse($tenantsData as $tenant)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2">
                                            <span class="avatar-initial rounded-circle bg-label-secondary">
                                                {{ substr($tenant['tenant_id'], 0, 1) }}
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="text-heading fw-medium">{{ Str::limit($tenant['tenant_id'], 15) }}</span>
                                            <small class="text-muted" style="font-size: 0.7rem;">ID: {{ substr($tenant['tenant_id'], 0, 8) }}...</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">{{ $tenant['users'] }}</td>
                                <td class="text-center">{{ number_format($tenant['orders']) }}</td>
                                <td class="text-end fw-bold text-success">${{ number_format($tenant['sales'], 2) }}</td>
                                <td class="text-end">
                                    <span class="badge bg-label-secondary">
                                        {{ $tenant['last_activity'] ? \Carbon\Carbon::parse($tenant['last_activity'])->diffForHumans() : 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="ti tabler-building-store-off fs-1 mb-2"></i>
                                    <p>No hay tenants registrados</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-xl-4 col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 card-title">
                        <i class="ti tabler-activity me-2"></i> Actividad Reciente
                    </h5>
                    <small class="text-muted">Últimas 20</small>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                        @forelse($recentOrders as $order)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3 border-bottom-dashed">
                            <div class="d-flex align-items-center">
                                <div class="avatar bg-label-secondary rounded me-3 p-1">
                                    <i class="ti tabler-receipt text-body"></i>
                                </div>
                                <div class="d-flex flex-column">
                                    <h6 class="mb-0 text-truncate" style="max-width: 140px;">Orden #{{ $order->id }}</h6>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        {{ $order->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-0 text-success fw-bold">${{ number_format($order->total, 2) }}</h6>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                    {{ Str::limit($order->branch->name ?? 'Sucursal', 12) }}
                                </small>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-center text-muted py-5">
                            <i class="ti tabler-Zzz fs-1 mb-2"></i>
                            <p>No hay actividad reciente</p>
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
