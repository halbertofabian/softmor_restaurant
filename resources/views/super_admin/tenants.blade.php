@extends('layouts.master')

@section('title', 'Gestionar Tenants')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="py-3 mb-0">
            <span class="text-muted fw-light">Super Admin /</span> Tenants
        </h4>
        <a href="{{ route('super-admin.subscriptions.create') }}" class="btn btn-primary">
            <i class="ti tabler-plus me-1"></i> Nueva Suscripción
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header py-3">
            <h5 class="mb-0 fw-bold"><i class="ti tabler-list me-2"></i>Listado de Restaurantes</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Restaurante / Sucursal</th>
                        <th>Admin Principal</th>
                        <th>Métricas</th>
                        <th>Fecha Registro</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenantsData as $tenant)
                    <tr>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-bold">{{ $tenant['branch_name'] }}</span>
                                <code class="small text-muted">{{ Str::limit($tenant['tenant_id'], 15) }}</code>
                            </div>
                        </td>
                        <td>
                             <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2">
                                    <span class="avatar-initial rounded-circle bg-label-primary">{{ substr($tenant['admin_user']->name ?? 'U', 0, 1) }}</span>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold small">{{ $tenant['admin_user']->name ?? 'N/A' }}</span>
                                    <small class="text-muted" style="font-size: 0.75rem;">{{ $tenant['admin_user']->email ?? '' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-3">
                                <div class="text-center">
                                  <small class="d-block text-muted">Ventas</small>
                                  <span class="fw-bold text-success">${{ number_format($tenant['sales_total']) }}</span>
                                </div>
                                <div class="text-center">
                                  <small class="d-block text-muted">Órdenes</small>
                                  <span class="fw-bold">{{ $tenant['orders_count'] }}</span>
                                </div>
                                <div class="text-center">
                                  <small class="d-block text-muted">Usuarios</small>
                                  <span class="fw-bold">{{ $tenant['users_count'] }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="small text-muted">
                            {{ $tenant['created_at']->format('d/m/Y') }}
                        </td>
                        <td class="text-center">
                            @if($tenant['admin_user'])
                            <a href="{{ route('super-admin.impersonate', $tenant['admin_user']) }}" 
                               class="btn btn-sm btn-primary" 
                               title="Entrar como Admin"
                               onclick="return confirm('¿Iniciar sesión como {{ $tenant['admin_user']->name }}?')">
                                <i class="ti tabler-login me-1"></i> Entrar
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="ti tabler-building-store-off fs-1 mb-2 d-block"></i>
                            No hay tenants registrados aún
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
