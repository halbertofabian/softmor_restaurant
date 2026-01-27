@extends('layouts.master')

@section('title', 'Invitación Creada')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card text-center mb-4 border-0 shadow-sm">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <div class="avatar avatar-xl bg-light rounded-circle mx-auto p-3">
                            <span class="avatar-initial rounded-circle bg-label-success">
                                <i class="ti tabler-check fs-1"></i>
                            </span>
                        </div>
                    </div>
                    
                    <h3 class="card-title mb-2">¡Suscripción Creada con Éxito!</h3>
                    <p class="text-muted mb-4">
                        Se ha creado el usuario <strong>{{ $name }}</strong> ({{ $email }})<br>
                        Comparte el siguiente enlace para que pueda configurar su cuenta.
                    </p>

                    <div class="input-group mb-3">
                        <input type="text" class="form-control text-center" value="{{ $link }}" id="invitationLink" readonly>
                        <button class="btn btn-primary" type="button" onclick="copyToClipboard()">
                            <i class="ti tabler-copy me-1"></i> Copiar
                        </button>
                    </div>

                    <div class="alert alert-warning d-inline-block text-start mb-4" role="alert">
                        <div class="d-flex">
                            <i class="ti tabler-alert-triangle me-2 mt-1"></i>
                            <div>
                                <strong>Importante:</strong> Este enlace expirará en 48 horas.<br>
                                Envíaselo al cliente por WhatsApp o correo electrónico.
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 col-lg-6 mx-auto">
                        <a href="https://wa.me/?text={{ urlencode('Hola ' . $name . ', aquí tienes tu enlace para activar tu cuenta en Restaurant Softmor: ' . $link) }}" target="_blank" class="btn btn-success">
                            <i class="ti tabler-brand-whatsapp me-1"></i> Enviar por WhatsApp
                        </a>
                        <a href="{{ route('super-admin.dashboard') }}" class="btn btn-label-secondary">Volver al Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard() {
  var copyText = document.getElementById("invitationLink");
  copyText.select();
  copyText.setSelectionRange(0, 99999); /* For mobile devices */
  navigator.clipboard.writeText(copyText.value).then(function() {
      alert("Enlace copiado al portapapeles");
  });
}
</script>
@endsection
