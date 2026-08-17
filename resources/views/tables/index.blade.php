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
                    $zones = $tables->pluck('zone')->filter()->unique()->sort()->values();
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
    <div class="tab-content doc-example-content p-0 pt-0">
        <div class="tab-pane fade show active" id="zone-all">
            <div class="row g-2" id="all-tables-container">
                @foreach($tables as $table)
                    <div class="col-6 col-md-4 col-xl-3 table-item" data-name="{{ strtolower($table->name) }}">
                        @include('tables.partials.card', ['table' => $table])
                    </div>
                @endforeach
            </div>
        </div>

        @foreach($zones as $zone)
        <div class="tab-pane fade" id="zone-{{ Str::slug($zone) }}">
            <div class="row g-2">
                 @foreach($tables->where('zone', $zone) as $table)
                     <div class="col-6 col-md-4 col-xl-3 table-item" data-name="{{ strtolower($table->name) }}">
                        @include('tables.partials.card', ['table' => $table])
                    </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal: Assign waiter when a non-waiter occupies a table -->
@unless(auth()->user()->hasRole('mesero'))
<div class="modal fade" id="occupyWaiterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ocupar Mesa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Selecciona el mesero que atenderá la mesa <strong id="occupy-table-name"></strong>:</p>
                <label class="form-label" for="occupy-waiter-select">Mesero</label>
                <select id="occupy-waiter-select" class="form-select">
                    <option value="">Seleccionar mesero...</option>
                    @foreach ($waiters as $waiter)
                        <option value="{{ $waiter->id }}">{{ $waiter->name }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback" id="occupy-waiter-error">Selecciona un mesero.</div>
                @if($waiters->isEmpty())
                <div class="alert alert-warning mt-3 mb-0">
                    No hay meseros registrados para esta sucursal. <a href="{{ route('users.index') }}">Da de alta meseros</a> para poder asignar quién atenderá la mesa.
                </div>
                @endif
            </div>
            <div class="modal-footer">
                @if(auth()->user()->hasRole('administrador'))
                <button type="button" class="btn btn-outline-primary me-auto" onclick="openRegisterWaiterModal()">
                    <i class="ti tabler-user-plus me-1"></i> Registrar Mesero
                </button>
                @endif
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmOccupy()">Ocupar Mesa</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Quick create waiter -->
<div class="modal fade" id="registerWaiterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="registerWaiterForm" action="{{ route('users.store') }}" method="POST" onsubmit="submitRegisterWaiter(event)">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Mesero</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">País + WhatsApp <small class="text-muted">(opcional)</small></label>
                            <input type="text" name="pais_whatsapp" class="form-control" placeholder="+52 ...">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Perfil</label>
                            <select name="role_id" class="form-select" required>
                                <option value="{{ $roles->firstWhere('name', 'mesero')?->id }}" selected>Mesero</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="branches[]" value="{{ session('branch_id') }}" id="register-branch" checked>
                                <label class="form-check-label" for="register-branch">
                                    Asignar a la sucursal actual: <strong>{{ session('branch_name') }}</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar y Seleccionar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endunless
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('searchTable');
    if (!input) return;

    var filterTables = function () {
        var query = (input.value || '').toLowerCase().trim();
        document.querySelectorAll('.table-item').forEach(function (item) {
            var name = (item.getAttribute('data-name') || '').toLowerCase();
            item.style.display = name.includes(query) ? '' : 'none';
        });
    };

    input.addEventListener('input', filterTables);
    filterTables();
});
</script>

@unless(auth()->user()->hasRole('mesero'))
<style>
    /* Select2 red border when the waiter select is invalid */
    #occupy-waiter-select.is-invalid + .select2-container .select2-selection {
        border-color: var(--bs-danger, #ff4d49) !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 77, 73, 0.25);
    }
</style>
<script>
    var currentOccupyForm = null;

    function openOccupierModal(btn) {
        currentOccupyForm = document.querySelector('form[data-occupy-table="' + btn.getAttribute('data-table-id') + '"]');
        document.getElementById('occupy-table-name').textContent = btn.getAttribute('data-table-name');

        var select = document.getElementById('occupy-waiter-select');
        select.classList.remove('is-invalid');
        select.value = '';
        $(select).trigger('change');

        var modalEl = document.getElementById('occupyWaiterModal');
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }

    function confirmOccupy() {
        var select = document.getElementById('occupy-waiter-select');
        if (!select.value) {
            select.classList.add('is-invalid');
            select.focus();
            return;
        }
        select.classList.remove('is-invalid');
        if (currentOccupyForm) {
            currentOccupyForm.querySelector('input[name="waiter_id"]').value = select.value;
            currentOccupyForm.submit();
        }
    }

    var registerWaiterModal = null;

    function openRegisterWaiterModal() {
        var form = document.getElementById('registerWaiterForm');
        form.reset();
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(function (el) {
            el.textContent = '';
        });
        form.querySelector('[name="role_id"]').value = @json($roles->firstWhere('name', 'mesero')?->id ?? '');
        registerWaiterModal = new bootstrap.Modal(document.getElementById('registerWaiterModal'));
        registerWaiterModal.show();
    }

    function submitRegisterWaiter(e) {
        e.preventDefault();
        var form = document.getElementById('registerWaiterForm');
        var data = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value
            },
            body: data
        }).then(function (res) {
            return res.json().then(function (body) {
                if (!res.ok) {
                    throw body;
                }
                return body;
            });
        }).then(function (json) {
            var select = document.getElementById('occupy-waiter-select');
            var opt = new Option(json.user.name, json.user.id, true, true);
            $(select).append(opt).val(json.user.id).trigger('change').removeClass('is-invalid');
            if (registerWaiterModal) {
                registerWaiterModal.hide();
            }
        }).catch(function (err) {
            var errors = (err && err.errors) ? err.errors : { name: [(err && err.message) || 'Error al crear el usuario.'] };
            Object.keys(errors).forEach(function (key) {
                var input = form.querySelector('[name="' + key + '"]');
                if (input) {
                    input.classList.add('is-invalid');
                    var fb = input.closest('.col-12').querySelector('.invalid-feedback');
                    if (fb) {
                        fb.textContent = errors[key][0];
                    }
                }
            });
        });
        return false;
    }

    $(function () {
        if ($.fn.select2) {
            $('#occupy-waiter-select').select2({
                dropdownParent: $('#occupyWaiterModal'),
                placeholder: 'Seleccionar mesero...',
                allowClear: true
            }).on('change', function () {
                $(this).removeClass('is-invalid');
            });
        }
    });
</script>
@endunless
@endpush
