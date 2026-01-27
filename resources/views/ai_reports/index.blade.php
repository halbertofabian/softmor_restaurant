@extends('layouts.master')

@section('title', 'Reportes IA')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="row">
        <!-- Main Chat Area -->
        <div class="col-lg-8 order-1 order-lg-0">
            
            <div class="card mb-4" style="height: calc(100vh - 200px);">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary">
                        <i class="ti tabler-sparkles me-2"></i> Asistente Inteligente
                    </h5>
                    <span class="badge bg-label-primary rounded-pill">BETA</span>
                </div>
                
                <div class="card-body d-flex flex-column">
                    <!-- Chat History (Placeholder for future iteration, currently hidden or just results) -->
                    <div class="flex-grow-1 d-flex flex-column justify-content-center align-items-center mb-4" id="emptyState">
                        <div class="avatar avatar-xl bg-label-primary rounded-circle mb-3">
                            <i class="ti tabler-brain fs-1"></i>
                        </div>
                        <h4 class="fw-bold mb-2">¿Qué deseas saber hoy?</h4>
                        <p class="text-muted text-center" style="max-width: 400px;">
                            Pregunta sobre ventas, inventario, tendencias o análisis. La IA generará reportes y gráficas al instante.
                        </p>
                    </div>

                    <!-- Loading State -->
                    <div id="loadingState" class="text-center py-5 d-none flex-grow-1 justify-content-center flex-column">
                        <div class="spinner-border text-primary mx-auto mb-3" role="status"></div>
                        <p class="text-muted move-text">Analizando datos...</p>
                    </div>
                    
                    <!-- Result Preview (Hidden initially) -->
                    <div id="resultContainer" class="d-none flex-grow-1 overflow-auto mb-3 p-3 bg-lighter rounded">
                         <div class="alert alert-primary d-flex align-items-center" role="alert">
                            <i class="ti tabler-info-circle me-2"></i>
                            <div id="interpretation"></div>
                        </div>
                        <div id="chartContainer" class="mb-3" style="max-height: 400px;">
                            <canvas id="reportChart"></canvas>
                        </div>
                         <div id="tableContainer" class="table-responsive">
                            <table class="table table-hover table-sm" id="resultTable">
                                <thead></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div class="mt-auto">
                        <form id="questionForm" class="position-relative">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text border-0 ps-3">
                                    <i class="ti tabler-search"></i>
                                </span>
                                <textarea name="question" 
                                          id="questionInput" 
                                          class="form-control border-0 shadow-none ps-2 py-3" 
                                          rows="1" 
                                          style="resize: none; min-height: 50px;"
                                          placeholder="Ej: Ventas de la semana pasada..." 
                                          required></textarea>
                                <button type="submit" class="btn btn-primary pe-3" id="askBtn">
                                    <i class="ti tabler-send"></i>
                                </button>
                            </div>
                        </form>
                        <div class="d-flex justify-content-center gap-2 mt-2">
                             <span class="badge bg-label-secondary cursor-pointer" onclick="fillQuestion('¿Cuánto vendí hoy?')">Ventas hoy</span>
                             <span class="badge bg-label-secondary cursor-pointer" onclick="fillQuestion('Top 5 productos del mes')">Top productos</span>
                             <span class="badge bg-label-secondary cursor-pointer" onclick="fillQuestion('Comparativa ventas vs semana pasada')">Comparativa</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: History & Favorites -->
        <div class="col-lg-4 order-0 order-lg-1">
            <!-- Favorites -->
            <div class="card mb-4" style="max-height: 400px; overflow-y: auto;">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold"><i class="ti tabler-star me-2 text-warning"></i>Favoritos</h6>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($favorites as $fav)
                    <a href="{{ route('ai-reports.show', $fav) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div class="d-flex flex-column text-truncate" style="max-width: 80%;">
                            <span class="fw-medium text-truncate">{{ $fav->question }}</span>
                            <small class="text-muted">{{ $fav->created_at->diffForHumans() }}</small>
                        </div>
                        <i class="ti tabler-chevron-right text-muted"></i>
                    </a>
                    @empty
                    <div class="text-center py-4 text-muted">
                        <small>Marca reportes como favoritos para verlos aquí</small>
                    </div>
                    @endforelse
                </div>
            </div>
            
            <!-- History -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold"><i class="ti tabler-history me-2"></i>Recientes</h6>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover table-sm mb-0">
                        <tbody class="table-border-bottom-0">
                             @forelse($reports->take(5) as $report)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('ai-reports.show', $report) }}" class="text-body d-block text-truncate" style="max-width: 200px;">
                                        {{ $report->question }}
                                    </a>
                                </td>
                                <td class="text-end pe-3">
                                    <small class="text-muted">{{ $report->created_at->format('d/m') }}</small>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">Sin historial</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                 <div class="card-footer text-center border-top-0 pt-1">
                    <small class="text-muted">Mostrando últimos 5</small>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let currentChart = null;
const questionInput = document.getElementById('questionInput');
const emptyState = document.getElementById('emptyState');

// Auto-resize textarea
questionInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});

function fillQuestion(text) {
    questionInput.value = text;
    questionInput.focus();
}

document.getElementById('questionForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const question = questionInput.value;
    const loadingState = document.getElementById('loadingState');
    const resultContainer = document.getElementById('resultContainer');
    const askBtn = document.getElementById('askBtn');
    
    // UI Update
    emptyState.classList.add('d-none');
    loadingState.classList.remove('d-none');
    loadingState.classList.add('d-flex');
    resultContainer.classList.add('d-none');
    askBtn.disabled = true;
    
    try {
        const response = await fetch('{{ route("ai-reports.ask") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ question })
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayResult(data.result);
            // Redirect to detail page slightly delayed for UX
             setTimeout(() => {
                window.location.href = `/ai-reports/${data.report.id}`;
            }, 1000);
        } else {
            alert('Error: ' + data.error);
            loadingState.classList.add('d-none');
            loadingState.classList.remove('d-flex');
            emptyState.classList.remove('d-none');
        }
    } catch (error) {
        alert('Error: ' + error.message);
        loadingState.classList.add('d-none');
        loadingState.classList.remove('d-flex');
        emptyState.classList.remove('d-none');
    } finally {
        askBtn.disabled = false;
    }
});

function displayResult(result) {
    const resultContainer = document.getElementById('resultContainer');
    const interpretation = document.getElementById('interpretation');
    const chartContainer = document.getElementById('chartContainer');
    const tableContainer = document.getElementById('tableContainer');
    
    interpretation.textContent = result.interpretation;
    loadingState.classList.add('d-none');
    loadingState.classList.remove('d-flex');
    resultContainer.classList.remove('d-none');
    resultContainer.classList.add('d-flex', 'flex-column');
    
    // Display chart or table
    if (result.chart_type === 'table' || !result.chart_config) {
        chartContainer.classList.add('d-none');
        tableContainer.classList.remove('d-none');
        displayTable(result.results);
    } else {
        chartContainer.classList.remove('d-none');
        tableContainer.classList.add('d-none');
        displayChart(result.chart_config);
    }
}

function displayChart(config) {
    const ctx = document.getElementById('reportChart');
    if (currentChart) currentChart.destroy();
    
    // Adapt chart config for dark mode if needed
    // Config colors usually come from backend or Chart.js defaults
    
    currentChart = new Chart(ctx, config);
}

function displayTable(data) {
    const table = document.getElementById('resultTable');
    const thead = table.querySelector('thead');
    const tbody = table.querySelector('tbody');
    
    thead.innerHTML = '';
    tbody.innerHTML = '';
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td class="text-center">No hay datos</td></tr>';
        return;
    }
    
    const headers = Object.keys(data[0]);
    const headerRow = document.createElement('tr');
    headers.forEach(header => {
        const th = document.createElement('th');
        th.textContent = header.toUpperCase();
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        headers.forEach(header => {
            const td = document.createElement('td');
            td.textContent = row[header];
            tr.appendChild(td);
        });
        tbody.appendChild(tr);
    });
}
</script>
@endpush
