@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="ti tabler-chart-bar me-2"></i>Detalle del Reporte</h2>
            <p class="text-muted mb-0">{{ $aiReport->created_at->format('d/m/Y H:i') }} por {{ $aiReport->user->name }}</p>
        </div>
        <div>
            <a href="{{ route('ai-reports.index') }}" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-2"></i>Volver
            </a>
            <form action="{{ route('ai-reports.favorite', $aiReport) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn {{ $aiReport->is_favorite ? 'btn-warning' : 'btn-outline-warning' }}">
                    <i class="ti {{ $aiReport->is_favorite ? 'tabler-star-filled' : 'tabler-star' }} me-2"></i>
                    {{ $aiReport->is_favorite ? 'Favorito' : 'Agregar a Favoritos' }}
                </button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Question -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="ti tabler-message-question me-2"></i>Pregunta</h5>
                </div>
                <div class="card-body">
                    <p class="fs-5 mb-0">{{ $aiReport->question }}</p>
                </div>
            </div>

            <!-- Interpretation -->
            @if($aiReport->interpretation)
            <div class="alert alert-info mb-4">
                <strong><i class="ti tabler-bulb me-2"></i>Interpretación de la IA:</strong><br>
                {{ $aiReport->interpretation }}
            </div>
            @endif

            <!-- Visualization -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent py-3">
                    <h5 class="mb-0"><i class="ti tabler-chart-dots me-2"></i>Resultados</h5>
                </div>
                <div class="card-body">
                    @if($aiReport->chart_type === 'table' || !$aiReport->chart_config)
                        <!-- Table View -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        @if($aiReport->result_data && count($aiReport->result_data) > 0)
                                            @foreach(array_keys($aiReport->result_data[0]) as $header)
                                                <th>{{ strtoupper($header) }}</th>
                                            @endforeach
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($aiReport->result_data ?? [] as $row)
                                        <tr>
                                            @foreach($row as $value)
                                                <td>{{ $value }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="100" class="text-center text-muted py-4">No hay datos disponibles</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                        <!-- Chart View -->
                        <canvas id="reportChart" style="max-height: 400px;"></canvas>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Metadata -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent py-3">
                    <h6 class="mb-0 fw-bold"><i class="ti tabler-info-circle me-2"></i>Información</h6>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <small class="text-muted d-block">Tipo de Visualización</small>
                        <span class="badge bg-info">{{ ucfirst($aiReport->chart_type) }}</span>
                    </div>
                    <div class="list-group-item">
                        <small class="text-muted d-block">Generado por</small>
                        <strong>{{ $aiReport->user->name }}</strong>
                    </div>
                    <div class="list-group-item">
                        <small class="text-muted d-block">Fecha</small>
                        <strong>{{ $aiReport->created_at->format('d/m/Y H:i:s') }}</strong>
                    </div>
                    <div class="list-group-item">
                        <small class="text-muted d-block">Registros</small>
                        <strong>{{ count($aiReport->result_data ?? []) }} resultados</strong>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <h6 class="mb-0 fw-bold"><i class="ti tabler-settings me-2"></i>Acciones</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('ai-reports.destroy', $aiReport) }}" method="POST" onsubmit="return confirm('¿Eliminar este reporte?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="ti tabler-trash me-2"></i>Eliminar Reporte
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if($aiReport->chart_config)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('reportChart');
    const config = @json($aiReport->chart_config);
    new Chart(ctx, config);
});
</script>
@endpush
@endif
