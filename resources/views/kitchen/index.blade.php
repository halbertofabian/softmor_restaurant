@extends('layouts.master')
@section('title', 'Monitor de Cocina - Selección de Área')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="mb-4">Monitor de Cocina y Bar</h4>
    <p class="mb-4">Selecciona el área que deseas monitorear.</p>

    <div class="row">
        @foreach($areas as $area)
        <div class="col-md-4 col-sm-6 mb-4">
            <a href="{{ route('kitchen.monitor', $area) }}" class="card h-100 text-decoration-none text-body hover-shadow">
                <div class="card-body text-center">
                    <div class="avatar avatar-xl mx-auto mb-3">
                        <span class="avatar-initial rounded bg-label-primary display-4">
                            <i class="ti tabler-cooker"></i>
                        </span>
                    </div>
                    <h5 class="card-title">{{ $area->name }}</h5>
                    <p class="card-text text-muted">{{ $area->description ?? 'Sin descripción' }}</p>
                </div>
                <div class="card-footer text-center bg-light border-top">
                    <span class="text-primary fw-bold">Abrir Monitor <i class="ti tabler-arrow-right ms-1"></i></span>
                </div>
            </a>
        </div>
        @endforeach

        @if($areas->isEmpty())
        <div class="col-12">
            <div class="alert alert-warning" role="alert">
                No hay áreas de preparación activas. <a href="{{ route('preparation-areas.index') }}">Crear áreas</a>.
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
