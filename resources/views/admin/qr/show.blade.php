@extends('layouts.master')

@section('title', 'Código QR - ' . $branch->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">Sucursales /</span> Código QR
    </h4>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ $branch->name }}</h5>
                    <small class="text-muted">Escanea para ver el menú digital</small>
                </div>
                <div class="card-body">
                    <div class="bg-light p-4 rounded mb-3 d-inline-block">
                        {!! $qrCode !!}
                    </div>
                    <p class="text-muted small mb-3 text-break">
                        <a href="{{ $url }}" target="_blank" class="text-primary text-decoration-underline">{{ $url }}</a>
                    </p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('branches.qr.download', $branch->id) }}" class="btn btn-primary">
                            <i class="ti tabler-download me-2"></i> Descargar PNG
                        </a>
                        <a href="{{ $url }}" target="_blank" class="btn btn-outline-secondary">
                            <i class="ti tabler-external-link me-2"></i> Ver Menú Público
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
