@extends('layouts.master')

@section('title', 'Apertura de Caja')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="col-md-6 col-lg-4">
            
            <div class="card">
                <div class="card-body p-4 text-center">
                    
                    <div class="app-brand justify-content-center mb-4">
                        <div class="avatar avatar-xl bg-label-primary rounded-3 p-2">
                             <i class="ti tabler-cash fs-1"></i>
                        </div>
                    </div>

                    <h4 class="mb-2 fw-bold">Apertura de Caja</h4>
                    <p class="text-muted mb-4">Ingresa el monto inicial en caja para comenzar el turno.</p>

                    <form action="{{ route('cash-registers.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4 text-start">
                            <label class="form-label" for="opening_amount">Monto Inicial</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       step="0.01" 
                                       id="opening_amount"
                                       name="opening_amount" 
                                       class="form-control form-control-lg @error('opening_amount') is-invalid @enderror" 
                                       placeholder="0.00" 
                                       autofocus 
                                       required />
                            </div>
                            @error('opening_amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg waves-effect waves-light">
                                <i class="ti tabler-lock-open me-2"></i> Abrir Caja
                            </button>
                            <a href="{{ route('dashboard') }}" class="btn btn-label-secondary">
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
