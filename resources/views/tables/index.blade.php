@extends('layouts.master')
@section('title', 'Mesas')

@section('content')
<!-- Custom Styles for Premium Dark Theme -->
<style>
    :root {
        --primary: #FFAB1D;
        --primary-dark: #E59A1A;
        --dark-bg: #09090b;
        --card-bg: #18181b; 
        --border-subtle: rgba(255, 255, 255, 0.08);
        --text-primary: #fafafa;
        --text-secondary: #a1a1a1;
        --status-free: #10b981;
        --status-occupied: #ef4444;
    }

    .container-p-y {
        padding-top: 1rem !important;
        padding-bottom: 5rem !important; /* Space for bottom nav if any or just breathing room */
    }

    /* Stats Cards */
    .stat-card {
        background: var(--card-bg);
        border: 1px solid var(--border-subtle);
        border-radius: 1rem;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.2s;
    }
    
    .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    /* Zone Tabs */
    .zone-scroll-container {
        overflow-x: auto;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .zone-scroll-container::-webkit-scrollbar {
        display: none;
    }

    .nav-pills .nav-link {
        background: var(--card-bg);
        color: var(--text-secondary);
        border: 1px solid var(--border-subtle);
        border-radius: 0.75rem;
        white-space: nowrap;
        padding: 0.6rem 1.2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: #000;
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(255, 171, 29, 0.2);
    }

    /* Search Input */
    .search-input-group {
        background: var(--card-bg);
        border: 1px solid var(--border-subtle);
        border-radius: 0.75rem;
        padding: 0.25rem 0.75rem;
        transition: border-color 0.2s;
    }
    .search-input-group:focus-within {
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(255, 171, 29, 0.1);
    }
    .search-input {
        background: transparent;
        border: none;
        color: var(--text-primary);
        font-weight: 500;
    }
    .search-input:focus {
        box-shadow: none;
        background: transparent;
        color: var(--text-primary);
    }
    .search-input::placeholder {
        color: var(--text-secondary);
        opacity: 0.7;
    }

    /* Adjust Grid for Mobile */
    @media (max-width: 576px) {
        .stat-card {
            padding: 0.75rem;
        }
        .stat-icon {
            width: 36px;
            height: 36px;
            font-size: 1rem;
        }
        .stat-info h6 {
            font-size: 0.8rem;
        }
        .stat-info small {
            font-size: 0.9rem;
        }
    }
</style>

<div class="container-fluid flex-grow-1 container-p-y">
    
    <!-- Stats Row (Scrollable on extremely small screens or grid) -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 171, 29, 0.1); color: var(--primary);">
                    <i class="ti tabler-layout-grid"></i>
                </div>
                <div class="stat-info">
                    <h6 class="text-secondary mb-0">Total</h6>
                    <small class="fw-bold text-white fs-6">{{ $tables->count() }}</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--status-free);">
                    <i class="ti tabler-armchair"></i>
                </div>
                <div class="stat-info">
                    <h6 class="text-secondary mb-0">Libres</h6>
                    <small class="fw-bold fs-6" style="color: var(--status-free);">{{ $tables->where('status', 'free')->where('is_active', true)->count() }}</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--status-occupied);">
                    <i class="ti tabler-users"></i>
                </div>
                <div class="stat-info">
                    <h6 class="text-secondary mb-0">Ocupadas</h6>
                    <small class="fw-bold fs-6" style="color: var(--status-occupied);">{{ $tables->where('status', 'occupied')->where('is_active', true)->count() }}</small>
                </div>
            </div>
        </div>
        @unless(auth()->user()->hasRole('mesero'))
        <div class="col-6 col-md-3">
             <div class="stat-card cursor-pointer" onclick="window.location.href='{{ route('tables.create') }}'" style="border: 1px dashed var(--primary); background: transparent;">
                <div class="stat-icon" style="background: rgba(255, 171, 29, 0.1); color: var(--primary);">
                    <i class="ti tabler-plus"></i>
                </div>
                <div class="stat-info">
                    <h6 class="fw-bold text-white mb-0">Nueva Mesa</h6>
                </div>
            </div>
        </div>
        @endunless
    </div>

    <!-- Filters & Search -->
    <div class="d-flex flex-column gap-3 mb-4">
        <!-- Search -->
        <div class="search-input-group d-flex align-items-center">
            <i class="ti tabler-search text-secondary me-2"></i>
            <input type="text" class="form-control search-input" placeholder="Buscar mesa por nombre..." id="searchTable">
        </div>

        <!-- Zone Tabs (Scrollable) -->
        <div class="zone-scroll-container">
            <ul class="nav nav-pills flex-nowrap" id="zoneTabs" role="tablist">
                <li class="nav-item me-2">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#zone-all" type="button">Todas</button>
                </li>
                @php
                    $zones = $tables->pluck('zone')->filter()->unique()->values();
                @endphp
                @foreach($zones as $index => $zone)
                <li class="nav-item me-2">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#zone-{{ Str::slug($zone) }}" type="button">{{ $zone }}</button>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Tables Grid -->
    <div class="tab-content pt-0">
        <div class="tab-pane fade show active" id="zone-all">
            <div class="row g-2" id="all-tables-container">
                @foreach($tables as $table)
                    <div class="col-12 col-md-4 col-xl-3 table-item" data-name="{{ strtolower($table->name) }}">
                        @include('tables.partials.card', ['table' => $table])
                    </div>
                @endforeach
            </div>
        </div>

        @foreach($zones as $zone)
        <div class="tab-pane fade" id="zone-{{ Str::slug($zone) }}">
            <div class="row g-2">
                 @foreach($tables->where('zone', $zone) as $table)
                     <div class="col-12 col-md-4 col-xl-3">
                        @include('tables.partials.card', ['table' => $table])
                    </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('searchTable').addEventListener('keyup', function() {
    let query = this.value.toLowerCase();
    document.querySelectorAll('.table-item').forEach(item => {
        let name = item.getAttribute('data-name');
        if(name.includes(query)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});
</script>
@endsection
