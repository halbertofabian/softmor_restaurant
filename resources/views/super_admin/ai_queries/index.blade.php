@extends('layouts.master')

@section('content')
<div class="container-xxl">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="py-3 mb-0">
                <span class="text-muted fw-light">Super Admin /</span> Consultas IA
            </h4>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-3">Monitor de Consultas Generadas</h5>
            <div class="d-flex justify-content-between align-items-center row pb-2 gap-3 gap-md-0">
                <div class="col-md-4 user_role">
                    <form action="{{ route('super_admin.ai_queries.index') }}" method="GET">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Buscar pregunta o error..." value="{{ request('search') }}">
                            <button class="btn btn-outline-primary" type="submit">Buscar</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-4 user_plan">
                    <div class="btn-group" role="group">
                        <a href="{{ route('super_admin.ai_queries.index') }}" class="btn btn-outline-secondary {{ !request('status') ? 'active' : '' }}">Todas</a>
                        <a href="{{ route('super_admin.ai_queries.index', ['status' => 'success']) }}" class="btn btn-outline-success {{ request('status') == 'success' ? 'active' : '' }}">Exitosas</a>
                        <a href="{{ route('super_admin.ai_queries.index', ['status' => 'error']) }}" class="btn btn-outline-danger {{ request('status') == 'error' ? 'active' : '' }}">Errores</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Tenant / Sucursal</th>
                        <th>Usuario</th>
                        <th>Pregunta / Error</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                    <tr>
                        <td>{{ $report->id }}</td>
                        <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <small class="d-block text-muted">{{ $report->tenant_id }}</small>
                            <span class="fw-bold">{{ $report->branch->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            {{ $report->user->name ?? 'N/A' }}
                        </td>
                        <td style="max-width: 400px;">
                            <div class="fw-bold text-truncate" title="{{ $report->question }}">{{ $report->question }}</div>
                            @if($report->status == 'error')
                                <small class="text-danger d-block mt-1 text-wrap">{{ Str::limit($report->error_message, 150) }}</small>
                            @else
                                <small class="text-muted d-block mt-1 text-truncate" title="{{ $report->sql_query }}">SQL: {{ $report->sql_query }}</small>
                            @endif
                        </td>
                        <td>
                            @if($report->status == 'success')
                                <span class="badge bg-label-success">Exitosa</span>
                            @else
                                <span class="badge bg-label-danger">Error</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">No se encontraron consultas.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $reports->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
