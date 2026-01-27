@extends('layouts.master')

@section('title', 'Nueva Suscripción')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti tabler-user-plus me-2 text-primary"></i> Crear Invitación
                    </h5>
                    <small class="text-muted">Genera un enlace de activación</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('super-admin.subscriptions.store') }}" method="POST">
                        @csrf
                        
                        <!-- Nombre del Cliente -->
                        <div class="mb-4">
                            <label class="form-label" for="name">Nombre del Cliente / Negocio</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti tabler-building-store"></i></span>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Ej. Restaurante La Plaza" required />
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-4">
                            <label class="form-label" for="email">Correo Electrónico</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti tabler-mail"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="cliente@ejemplo.com" required />
                            </div>
                            <div class="form-text">Se enviará la invitación a este correo si lo deseas.</div>
                        </div>

                        <!-- WhatsApp con Selector de País -->
                        <div class="mb-4">
                            <label class="form-label" for="whatsapp">Teléfono de Contacto (WhatsApp)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti tabler-brand-whatsapp"></i></span>
                                <select class="form-select" name="country_code" style="max-width: 120px;">
                                    <option value="52" selected>🇲🇽 +52</option>
                                    <option value="1">🇺🇸 +1</option>
                                    <option value="57">🇨🇴 +57</option>
                                    <option value="54">🇦🇷 +54</option>
                                    <option value="51">🇵🇪 +51</option>
                                    <option value="34">🇪🇸 +34</option>
                                </select>
                                <input type="text" id="whatsapp" name="whatsapp_number" class="form-control" placeholder="1234567890" required />
                            </div>
                        </div>

                        <!-- Info Alert -->
                        <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
                            <span class="alert-icon text-primary me-2">
                                <i class="ti tabler-info-circle fs-4"></i>
                            </span>
                            <div class="fs-6">
                                Al guardar, obtendrás un <strong>enlace único</strong>. No necesitas crear una contraseña; el cliente la configurará al entrar al enlace.
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg waves-effect waves-light">
                                <i class="ti tabler-link me-1"></i> Generar Enlace de Invitación
                            </button>
                            <a href="{{ route('super-admin.dashboard') }}" class="btn btn-label-secondary waves-effect">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
