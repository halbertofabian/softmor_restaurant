@extends('layouts.master')

@section('title', 'Configuración')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="mb-0 fw-bold py-3">
             <span class="text-muted fw-light">Administración /</span> Configuración
         </h4>
    </div>

    <div class="row">
        <!-- Navigation -->
        <div class="col-md-3 mb-4">
             <div class="card">
                 <div class="card-body">
                     <ul class="nav nav-pills flex-column" id="settingsTabs" role="tablist">
                         <li class="nav-item mb-1">
                             <button class="nav-link active d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#tab-general" type="button">
                                 <i class="ti tabler-printer me-2"></i> Impresión y Diseño
                             </button>
                         </li>
                         <li class="nav-item mb-1">
                             <button class="nav-link d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#tab-texts" type="button">
                                 <i class="ti tabler-text-caption me-2"></i> Textos del Ticket
                             </button>
                         </li>
                         <li class="nav-item">
                             <button class="nav-link d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#tab-tips" type="button">
                                 <i class="ti tabler-coin me-2"></i> Propinas
                             </button>
                         </li>
                         <li class="nav-item mt-1">
                             <button class="nav-link d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#tab-ai" type="button">
                                 <i class="ti tabler-robot me-2"></i> Inteligencia Artificial
                             </button>
                         </li>
                     </ul>
                 </div>
             </div>
        </div>

        <!-- Content -->
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 card-title">Configuración de Tickets</h5>
                    <small class="text-muted">Ajusta cómo se ven tus comprobantes</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        
                        <div class="tab-content p-0">
                            <!-- General Settings -->
                            <div class="tab-pane fade show active" id="tab-general">
                                <h6 class="fw-bold mb-3"><i class="ti tabler-ruler-2 me-2"></i>Dimensiones</h6>
                                
                                <div class="mb-4">
                                    <label class="form-label d-block mb-2">Ancho del Papel</label>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check custom-option custom-option-basic">
                                                <label class="form-check-label custom-option-content" for="width80">
                                                    <input class="form-check-input" type="radio" name="ticket_printer_width" id="width80" value="80mm" {{ ($settings['ticket_printer_width'] ?? '80mm') == '80mm' ? 'checked' : '' }}>
                                                    <span class="custom-option-header">
                                                        <span class="h6 mb-0">80mm (Estándar)</span>
                                                        <i class="ti tabler-printer fs-4"></i>
                                                    </span>
                                                    <span class="custom-option-body">
                                                        <small>Para impresoras térmicas grandes/comunes.</small>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check custom-option custom-option-basic">
                                                <label class="form-check-label custom-option-content" for="width58">
                                                    <input class="form-check-input" type="radio" name="ticket_printer_width" id="width58" value="58mm" {{ ($settings['ticket_printer_width'] ?? '80mm') == '58mm' ? 'checked' : '' }}>
                                                    <span class="custom-option-header">
                                                        <span class="h6 mb-0">58mm (Pequeña)</span>
                                                        <i class="ti tabler-printer-off fs-4"></i>
                                                    </span>
                                                    <span class="custom-option-body">
                                                        <small>Para impresoras portátiles o compactas.</small>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label" for="ticket_font_size">Tamaño de Fuente (px)</label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class="ti tabler-typography"></i></span>
                                            <input type="number" id="ticket_font_size" class="form-control" name="ticket_font_size" value="{{ $settings['ticket_font_size'] ?? '12' }}" placeholder="12">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Márgenes (mm)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Sup.</span>
                                            <input type="number" class="form-control" name="ticket_margin_top" value="{{ $settings['ticket_margin_top'] ?? '0' }}">
                                            <span class="input-group-text">Izq.</span>
                                            <input type="number" class="form-control" name="ticket_margin_left" value="{{ $settings['ticket_margin_left'] ?? '0' }}">
                                            <span class="input-group-text">Der.</span>
                                            <input type="number" class="form-control" name="ticket_margin_right" value="{{ $settings['ticket_margin_right'] ?? '0' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Text Settings -->
                            <div class="tab-pane fade" id="tab-texts">
                                <h6 class="fw-bold mb-3"><i class="ti tabler-edit me-2"></i>Textos Personalizados</h6>

                                <div class="mb-3">
                                    <label class="form-label" for="ticket_pre_check_header">Encabezado "Cuenta de Consumo"</label>
                                    <input type="text" id="ticket_pre_check_header" class="form-control" name="ticket_pre_check_header" value="{{ $settings['ticket_pre_check_header'] ?? '*** CUENTA DE CONSUMO ***' }}">
                                    <div class="form-text">Título superior del ticket de pre-cuenta.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="ticket_pre_check_disclaimer">Aviso Legal (Pre-Check)</label>
                                    <input type="text" id="ticket_pre_check_disclaimer" class="form-control" name="ticket_pre_check_disclaimer" value="{{ $settings['ticket_pre_check_disclaimer'] ?? 'No válido como comprobante fiscal' }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="ticket_footer_message">Mensaje Pie de Página</label>
                                    <textarea id="ticket_footer_message" class="form-control" name="ticket_footer_message" rows="3">{{ $settings['ticket_footer_message'] ?? '¡Gracias por su visita!' }}</textarea>
                                    <div class="form-text">Aparece al final de todos los tickets.</div>
                                </div>
                            </div>

                            <!-- Tips Settings -->
                            <div class="tab-pane fade" id="tab-tips">
                                <!-- ... (tips content stays same) ... -->
                                <h6 class="fw-bold mb-3"><i class="ti tabler-cash-banknote me-2"></i>Configuración de Propinas</h6>
                                
                                <div class="alert alert-primary d-flex align-items-center" role="alert">
                                    <i class="ti tabler-info-circle me-2 fs-4"></i>
                                    <div>
                                        Las propinas sugeridas se imprimirán al final de la "Cuenta de Consumo" para facilitar el cálculo al cliente.
                                    </div>
                                </div>

                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" name="ticket_tips_enabled" value="1" id="tipsCheck" {{ ($settings['ticket_tips_enabled'] ?? '') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="tipsCheck">Habilitar Sugerencias de Propina</label>
                                </div>

                                <div class="row g-3">
                                    @for($i=1; $i<=4; $i++)
                                    <div class="col-md-3 col-6">
                                        <label class="form-label">Opción {{ $i }}</label>
                                        <div class="input-group input-group-merge">
                                            <input type="number" class="form-control" name="ticket_tip_{{ $i }}_percent" value="{{ $settings['ticket_tip_'.$i.'_percent'] ?? ($i==1?10:($i==2?12:($i==3?15:18))) }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    @endfor
                                </div>
                            </div>

                            <!-- AI Settings -->
                            <div class="tab-pane fade" id="tab-ai">
                                <h6 class="fw-bold mb-3"><i class="ti tabler-robot me-2"></i>Configuración de IA</h6>
                                
                                <div class="alert alert-warning d-flex align-items-start" role="alert">
                                    <i class="ti tabler-alert-triangle me-2 fs-4 mt-1"></i>
                                    <div class="small">
                                        <strong>Importante:</strong> Para usar los reportes inteligentes personalizados, necesitas tu propia clave de API de OpenAI. 
                                        El costo de uso se facturará a tu cuenta de OpenAI directamente.
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold" for="openai_api_key">API Key de OpenAI (ChatGPT)</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="ti tabler-key"></i></span>
                                        <input type="password" id="openai_api_key" class="form-control" name="openai_api_key" value="{{ $settings['openai_api_key'] ?? '' }}" placeholder="sk-..." autocomplete="off">
                                        <span class="input-group-text cursor-pointer" onclick="const i = document.getElementById('openai_api_key'); i.type = i.type === 'password' ? 'text' : 'password';"><i class="ti tabler-eye"></i></span>
                                    </div>
                                    <div class="form-text">Tu clave se guarda de forma segura. Si la dejas vacía, no podrás generar reportes IA.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                             <a href="{{ route('dashboard') }}" class="btn btn-label-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti tabler-device-floppy me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
