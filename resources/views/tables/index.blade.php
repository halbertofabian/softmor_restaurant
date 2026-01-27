@extends('layouts.master')
@section('title', 'Mesas')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <!-- Stats Summary -->
    <div class="row mb-4 g-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card bg-label-primary h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-primary text-white"><i class="ti tabler-table"></i></span>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-primary">Total</h6>
                        <small class="fw-bold text-heading">{{ $tables->count() }} Mesas</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card bg-label-success h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-success text-white"><i class="ti tabler-check"></i></span>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-success">Libres</h6>
                        <small class="fw-bold text-heading">{{ $tables->where('status', 'free')->where('is_active', true)->count() }} Dispo.</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card bg-label-danger h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-danger text-white"><i class="ti tabler-armchair"></i></span>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-danger">Ocupadas</h6>
                        <small class="fw-bold text-heading">{{ $tables->where('status', 'occupied')->where('is_active', true)->count() }} Activas</small>
                    </div>
                </div>
            </div>
        </div>
        @unless(auth()->user()->hasRole('mesero'))
        <div class="col-sm-6 col-lg-3">
             <div class="card h-100 border-dashed border-primary shadow-none bg-transparent">
                <div class="card-body py-3 d-flex align-items-center justify-content-center cursor-pointer" onclick="window.location.href='{{ route('tables.create') }}'">
                    <i class="ti tabler-plus text-primary me-2 fs-4"></i>
                    <span class="fw-bold text-primary">Nueva Mesa</span>
                </div>
            </div>
        </div>
        @endunless
    </div>

    <!-- Zone Filter & Search -->
    <div class="card mb-4">
        <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <ul class="nav nav-pills" id="zoneTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill fw-bold small" data-bs-toggle="tab" data-bs-target="#zone-all" type="button">Todas</button>
                </li>
                @php
                    $zones = $tables->pluck('zone')->filter()->unique()->values();
                @endphp
                @foreach($zones as $index => $zone)
                <li class="nav-item ms-1">
                    <button class="nav-link rounded-pill fw-bold small" data-bs-toggle="tab" data-bs-target="#zone-{{ Str::slug($zone) }}" type="button">{{ $zone }}</button>
                </li>
                @endforeach
            </ul>
            
            <div class="input-group input-group-merge" style="width: 250px;">
                <span class="input-group-text"><i class="ti tabler-search"></i></span>
                <input type="text" class="form-control" placeholder="Buscar mesa..." id="searchTable">
            </div>
        </div>
    </div>

    <!-- Tables Grid -->
    <div class="tab-content pt-0">
        <div class="tab-pane fade show active" id="zone-all">
            <div class="row g-3" id="all-tables-container">
                @foreach($tables as $table)
                    <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 table-item" data-name="{{ strtolower($table->name) }}">
                        @include('tables.partials.card', ['table' => $table])
                    </div>
                @endforeach
            </div>
        </div>

        @foreach($zones as $zone)
        <div class="tab-pane fade" id="zone-{{ Str::slug($zone) }}">
            <div class="row g-3">
                 @foreach($tables->where('zone', $zone) as $table)
                     <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6">
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
