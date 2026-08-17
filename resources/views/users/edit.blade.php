@extends('layouts.master')
@section('title', 'Editar Usuario')
@section('content')
<h4 class="mb-4">Editar Usuario: {{ $user->name }}</h4>

<div class="card">
    <div class="card-body">
        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" id="label-email">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="field-email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    <div class="form-text" id="hint-email" style="display:none;">Opcional si solo será mesero de mesa (no usará la app).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">País + WhatsApp <small class="text-muted">(opcional)</small></label>
                    <input type="text" name="pais_whatsapp" class="form-control" value="{{ old('pais_whatsapp', $user->pais_whatsapp) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                    <select name="estado" class="form-select" required>
                        <option value="activo" {{ $user->estado == 'activo' ? 'selected' : '' }}>Activo</option>
                        <option value="inactivo" {{ $user->estado == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Rol <span class="text-danger">*</span></label>
                    <select name="role_id" id="field-role" class="form-select" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <div class="alert alert-secondary mb-0">
                        <small>Deja los campos de contraseña vacíos si no deseas cambiarla.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" id="label-password">Nueva Contraseña</label>
                    <input type="password" name="password" id="field-password" class="form-control">
                    <div class="form-text" id="hint-password" style="display:none;">Opcional si solo será mesero de mesa (no usará la app).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" id="label-password_confirmation">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" id="field-password_confirmation" class="form-control">
                </div>
                <div class="col-12 mt-4">
                    <label class="form-label d-block text-primary fw-bold">Asignar Sucursales</label>
                    <div class="row g-3">
                        @foreach($branches as $branch)
                        <div class="col-md-4 col-sm-6">
                            <label class="form-check-label d-block border rounded p-3 cursor-pointer position-relative h-100 hover-shadow transition-all" for="branch_{{ $branch->id }}">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-label-primary p-2 me-3 rounded">
                                        <i class="ti tabler-building-store fs-4"></i>
                                    </span>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark">{{ $branch->name }}</h6>
                                        <small class="text-muted text-uppercase" style="font-size: 0.7rem;">Sucursal</small>
                                    </div>
                                </div>
                                <div class="border-top my-2"></div>
                                <p class="small text-muted mb-0 text-truncate">
                                    <i class="ti tabler-map-pin me-1"></i> {{ $branch->address ?? 'Sin dirección' }}
                                </p>
                                
                                <input class="form-check-input position-absolute top-0 end-0 m-3" type="checkbox" name="branches[]" value="{{ $branch->id }}" id="branch_{{ $branch->id }}"
                                    {{ $user->branches->contains($branch->id) ? 'checked' : '' }}>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <div class="mt-4 text-end">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    var MESERO_ROLE_ID = @json($meseroRoleId ?? null);

    document.addEventListener('DOMContentLoaded', function () {
        var roleSelect = document.getElementById('field-role');
        if (!roleSelect) return;

        var fields = [
            { name: 'email', label: 'label-email', hint: 'hint-email' },
            { name: 'password', label: 'label-password', hint: 'hint-password' },
            { name: 'password_confirmation', label: 'label-password_confirmation', hint: null }
        ];

        function sync() {
            var isMesero = String(roleSelect.value) === String(MESERO_ROLE_ID);
            fields.forEach(function (field) {
                var input = document.getElementById('field-' + field.name);
                var label = document.getElementById(field.label);
                var hint = field.hint ? document.getElementById(field.hint) : null;

                if (isMesero) {
                    input.removeAttribute('required');
                    var ast = label.querySelector('.text-danger');
                    if (ast) ast.remove();
                    if (hint) hint.style.display = 'block';
                } else {
                    input.setAttribute('required', 'required');
                    if (!label.querySelector('.text-danger')) {
                        label.insertAdjacentHTML('beforeend', ' <span class="text-danger">*</span>');
                    }
                    if (hint) hint.style.display = 'none';
                }
            });
        }

        roleSelect.addEventListener('change', sync);
        sync();
    });
</script>
@endpush
