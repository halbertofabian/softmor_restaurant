@extends('layouts.master')
@section('title', 'Usuarios')
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Usuarios del Sistema</h5>
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="ti tabler-plus me-1"></i> Nueva Usuario
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="users-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>WhatsApp/País</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="card-footer py-2 d-none">
            @if (isset($users))
                {{ $users->links() }}
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.min.js') }}"></script>
    <script>
        GF.createAjaxDataTable('#users-table', {
            ajax: "{{ route('users.datatable') }}",
            responsive: true,
            columns: [{
                    data: 'name'
                },
                {
                    data: 'roles',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'whatsapp'
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            columnDefs: [{
                targets: [0, 1, 2, 4],
                render: function(data) {
                    return data;
                }
            }]
        });
    </script>
@endpush
