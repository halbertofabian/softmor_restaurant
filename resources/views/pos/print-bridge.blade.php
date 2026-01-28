@extends('layouts.master')

@section('title', 'Imprimiendo...')

@section('content')
<div class="container text-center py-5">
    <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <h3>Procesando Impresión...</h3>
    <p class="text-muted">Enviando datos al puente de impresión local...</p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', async function() {
        const printData = @json($printData);
        
        try {
            // Fetch local bridge URL from controller-passed variable or assume default. 
            // Better yet, we can't access settings directly here unless passed.
            // But we can output it via Blade.
            const bridgeUrl = "{{ $settings['local_bridge_url'] ?? 'http://localhost:8000/api/printer/raw' }}";
            
            await fetch(bridgeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(printData)
            });
            console.log('Ticket sent to local printer');
        } catch (error) {
            console.error('Error printing locally:', error);
            // alert('Hubo un error al intentar imprimir en el servidor local. ' + error.message);
        }

        // Always redirect
        window.location.href = "{{ route('orders.index') }}";
    });
</script>
@endsection
