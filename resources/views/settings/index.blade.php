@extends('layouts.master')

@section('title', 'Configuración de Sistema')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Configuración</h4>
            <span class="text-muted">Administra las preferencias generales de tu restaurante</span>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 mb-4">
            <div class="card shadow-none bg-transparent border-0">
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column gap-2" id="settingsTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active d-flex align-items-center py-2 px-3 text-start" data-bs-toggle="tab" data-bs-target="#tab-general" type="button">
                                <i class="ti tabler-printer me-2 fs-5"></i> 
                                <span>Impresoras</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center py-2 px-3 text-start" data-bs-toggle="tab" data-bs-target="#tab-texts" type="button">
                                <i class="ti tabler-text-caption me-2 fs-5"></i> 
                                <span>Textos Ticket</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center py-2 px-3 text-start" data-bs-toggle="tab" data-bs-target="#tab-tips" type="button">
                                <i class="ti tabler-coin me-2 fs-5"></i> 
                                <span>Propinas</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center py-2 px-3 text-start" data-bs-toggle="tab" data-bs-target="#tab-ai" type="button">
                                <i class="ti tabler-sparkles me-2 fs-5"></i> 
                                <span>Inteligencia Artificial</span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="card shadow-sm border-0">
                <form action="{{ route('settings.update') }}" method="POST">
                    @csrf
                    <div class="card-body p-4">
                        <div class="tab-content m-0 p-0">
                            
                            <!-- Tab: Impresoras -->
                            <div class="tab-pane fade show active" id="tab-general">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="avatar avatar-sm me-3 bg-label-primary rounded p-2">
                                        <i class="ti tabler-printer fs-4"></i>
                                    </div>
                                    <h5 class="mb-0 fw-bold">Configuración de Impresión</h5>
                                </div>

                                <!-- Paper Size Cards -->
                                <div class="mb-5">
                                    <label class="form-label text-uppercase text-muted small fw-bold mb-3">Formato de Papel</label>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check custom-option custom-option-icon h-100 border rounded p-3 {{ ($settings['ticket_printer_width'] ?? '80mm') == '80mm' ? 'checked border-primary' : '' }}">
                                                <label class="form-check-label custom-option-content d-flex w-100 align-items-center m-0" for="width80">
                                                    <span class="custom-option-body me-3">
                                                        <i class="ti tabler-printer fs-1 text-primary"></i>
                                                    </span>
                                                    <div>
                                                        <span class="custom-option-header h6 mb-1 d-block fw-bold">80mm (Estándar)</span>
                                                        <span class="text-muted small">Ideal para impresoras térmicas de mostrador.</span>
                                                    </div>
                                                    <input class="form-check-input ms-auto" type="radio" name="ticket_printer_width" id="width80" value="80mm" {{ ($settings['ticket_printer_width'] ?? '80mm') == '80mm' ? 'checked' : '' }}>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check custom-option custom-option-icon h-100 border rounded p-3 {{ ($settings['ticket_printer_width'] ?? '80mm') == '58mm' ? 'checked border-primary' : '' }}">
                                                <label class="form-check-label custom-option-content d-flex w-100 align-items-center m-0" for="width58">
                                                    <span class="custom-option-body me-3">
                                                        <i class="ti tabler-printer-off fs-1 text-secondary"></i>
                                                    </span>
                                                    <div>
                                                        <span class="custom-option-header h6 mb-1 d-block fw-bold">58mm (Compacto)</span>
                                                        <span class="text-muted small">Para impresoras móviles o pequeñas.</span>
                                                    </div>
                                                    <input class="form-check-input ms-auto" type="radio" name="ticket_printer_width" id="width58" value="58mm" {{ ($settings['ticket_printer_width'] ?? '80mm') == '58mm' ? 'checked' : '' }}>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Local Bridge Section -->
                                <div class="bg-label-secondary rounded p-4 mb-4">
                                    <h6 class="fw-bold mb-3 d-flex align-items-center">
                                        <i class="ti tabler-server me-2"></i> Conexión Local (Puente de Impresión)
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-7">
                                            <label class="form-label fw-semibold">URL del Servidor Local</label>
                                            <div class="input-group">
                                                <span class="input-group-text border-end-0"><i class="ti tabler-link text-muted"></i></span>
                                                <input type="text" id="local_bridge_url" class="form-control border-start-0 ps-0" name="local_bridge_url" value="{{ $settings['local_bridge_url'] ?? 'http://localhost:8000/api/printer/raw' }}" placeholder="http://localhost:8000/api/printer/raw">
                                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('local_bridge_url').value = 'http://localhost:8000/api/printer/raw';"><i class="ti tabler-refresh"></i></button>
                                            </div>
                                            <div class="form-text small mt-1">Script PHP corriendo en la caja (Default: localhost:8000)</div>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label fw-semibold">Nombre de la Impresora (Windows)</label>
                                            <div class="input-group">
                                                <input type="text" list="printer_list" id="ticket_printer_name" class="form-control" name="ticket_printer_name" value="{{ $settings['ticket_printer_name'] ?? 'POS-80' }}" placeholder="Ej: EPSON-TM-T20">
                                                <datalist id="printer_list"></datalist>
                                                <button class="btn btn-primary" type="button" onclick="fetchPrinters()">
                                                    <i class="ti tabler-search"></i>
                                                </button>
                                            </div>
                                            <div class="form-text small mt-1">Nombre compartido en el panel de control.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Settings Adjustment -->
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold" for="ticket_font_size">Tamaño Fuente (px)</label>
                                        <div class="input-group">
                                            <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('ticket_font_size').stepDown()"><i class="ti tabler-minus"></i></button>
                                            <input type="number" id="ticket_font_size" class="form-control text-center" name="ticket_font_size" value="{{ $settings['ticket_font_size'] ?? '12' }}" placeholder="12">
                                            <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('ticket_font_size').stepUp()"><i class="ti tabler-plus"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-semibold">Márgenes de Impresión (mm)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Superior</span>
                                            <input type="number" class="form-control" name="ticket_margin_top" value="{{ $settings['ticket_margin_top'] ?? '0' }}">
                                            <span class="input-group-text">Izquierda</span>
                                            <input type="number" class="form-control" name="ticket_margin_left" value="{{ $settings['ticket_margin_left'] ?? '0' }}">
                                            <span class="input-group-text">Derecha</span>
                                            <input type="number" class="form-control" name="ticket_margin_right" value="{{ $settings['ticket_margin_right'] ?? '0' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Textos -->
                            <div class="tab-pane fade" id="tab-texts">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="avatar avatar-sm me-3 bg-label-warning rounded p-2">
                                        <i class="ti tabler-text-caption fs-4"></i>
                                    </div>
                                    <h5 class="mb-0 fw-bold">Personalización de Textos</h5>
                                </div>

                                <div class="row g-4">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="ticket_pre_check_header" name="ticket_pre_check_header" value="{{ $settings['ticket_pre_check_header'] ?? '*** CUENTA DE CONSUMO ***' }}" placeholder="Header">
                                            <label for="ticket_pre_check_header">Encabezado de Pre-Cuenta</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="ticket_pre_check_disclaimer" name="ticket_pre_check_disclaimer" value="{{ $settings['ticket_pre_check_disclaimer'] ?? 'No válido como comprobante fiscal' }}" placeholder="Disclaimer">
                                            <label for="ticket_pre_check_disclaimer">Aviso Legal (Pie de Pre-cuenta)</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea class="form-control" id="ticket_footer_message" name="ticket_footer_message" style="height: 100px" placeholder="Mensaje">{{ $settings['ticket_footer_message'] ?? '¡Gracias por su visita!' }}</textarea>
                                            <label for="ticket_footer_message">Mensaje de Despedida (Ticket Final)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Propinas -->
                            <div class="tab-pane fade" id="tab-tips">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="avatar avatar-sm me-3 bg-label-success rounded p-2">
                                        <i class="ti tabler-coin fs-4"></i>
                                    </div>
                                    <h5 class="mb-0 fw-bold">Sugerencias de Propina</h5>
                                </div>

                                <div class="card bg-label-success border-0 mb-4">
                                    <div class="card-body d-flex align-items-center">
                                        <i class="ti tabler-info-circle fs-2 me-3 text-success"></i>
                                        <div>
                                            <h6 class="mb-1 fw-bold text-success">¿Cómo funciona?</h6>
                                            <p class="mb-0 small text-success-dark">Las sugerencias se calculan e imprimen automáticamente al final de la precuenta para facilitar la decisión al cliente.</p>
                                        </div>
                                        <div class="form-check form-switch ms-auto">
                                            <input class="form-check-input" type="checkbox" style="transform: scale(1.5);" name="ticket_tips_enabled" value="1" id="tipsCheck" {{ ($settings['ticket_tips_enabled'] ?? '') ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                </div>

                                <label class="form-label fw-bold mb-3">Porcentajes Sugeridos</label>
                                <div class="row g-3">
                                    @for($i=1; $i<=4; $i++)
                                    <div class="col-6 col-md-3">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text border-end-0 text-muted">OP{{$i}}</span>
                                            <input type="number" class="form-control border-start-0 fw-bold" name="ticket_tip_{{ $i }}_percent" value="{{ $settings['ticket_tip_'.$i.'_percent'] ?? ($i==1?10:($i==2?12:($i==3?15:18))) }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    @endfor
                                </div>
                            </div>

                            <!-- Tab: IA -->
                            <div class="tab-pane fade" id="tab-ai">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="avatar avatar-sm me-3 bg-label-info rounded p-2">
                                        <i class="ti tabler-sparkles fs-4"></i>
                                    </div>
                                    <h5 class="mb-0 fw-bold">Inteligencia Artificial</h5>
                                </div>

                                <div class="alert alert-info border-0 d-flex align-items-start mb-4" role="alert">
                                    <i class="ti tabler-robot me-3 fs-3 mt-1"></i>
                                    <div>
                                        <h6 class="alert-heading fw-bold mb-1">ChatGPT Integration</h6>
                                        <p class="mb-0 small">Habilita reportes inteligentes y análisis de ventas usando tu propia API Key de OpenAI.</p>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold" for="openai_api_key">OpenAI API Key</label>
                                    <div class="input-group input-group-merge input-group-lg">
                                        <span class="input-group-text"><i class="ti tabler-key"></i></span>
                                        <input type="password" id="openai_api_key" class="form-control" name="openai_api_key" value="{{ $settings['openai_api_key'] ?? '' }}" placeholder="sk-..." autocomplete="off">
                                        <span class="input-group-text cursor-pointer" onclick="const i = document.getElementById('openai_api_key'); i.type = i.type === 'password' ? 'text' : 'password';"><i class="ti tabler-eye"></i></span>
                                    </div>
                                    <div class="form-text">Tu clave está segura y encriptada.</div>
                                </div>
                            </div>

                        </div>
                    </div>
                    
                    <!-- Footer Actions -->
                    <div class="card-footer d-flex justify-content-end border-top p-3">
                         <button type="submit" class="btn btn-primary px-4">
                            <i class="ti tabler-device-floppy me-2"></i> Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
async function fetchPrinters() {
    const bridgeUrlRaw = document.getElementById('local_bridge_url').value;
    const listUrl = bridgeUrlRaw.replace('raw', 'list');
    
    const btn = event.currentTarget;
    const originalIcon = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    btn.disabled = true;

    try {
        const response = await fetch(listUrl);
        const data = await response.json();
        
        if(data.status === 'success' && data.printers) {
            const input = document.getElementById('ticket_printer_name');
            const parent = input.parentElement;
            const currentValue = input.value;

            const select = document.createElement('select');
            select.id = 'ticket_printer_name';
            select.name = 'ticket_printer_name';
            select.className = 'form-select';
            
            const defaultOption = document.createElement('option');
            defaultOption.text = '-- Selecciona una impresora --';
            defaultOption.disabled = true;
            select.appendChild(defaultOption);

            data.printers.forEach(printer => {
                const option = document.createElement('option');
                option.value = printer;
                option.text = printer;
                if (printer === currentValue) option.selected = true;
                select.appendChild(option);
            });

            if (!select.value) select.selectedIndex = 0;

            const datalist = document.getElementById('printer_list');
            if (datalist) datalist.remove();
            input.remove();
            
            parent.insertBefore(select, btn);
            select.focus();
            
        } else {
            alert('Error: ' + (data.message || 'No se pudieron obtener impresoras.'));
        }
    } catch (error) {
        console.error("Error fetching printers:", error);
        alert('Error al conectar con el servidor local. Verifica que el bridge esté corriendo.');
    } finally {
        btn.innerHTML = originalIcon;
        btn.disabled = false;
    }
}
</script>
@endsection
