@extends('layouts.master')
@section('title', 'Monitor: ' . $area->name)
@section('content')
<div class="row align-items-center mb-4">
    <div class="col-6">
        <h4 class="mb-0">
            <span class="text-muted fw-light">Monitor /</span> {{ $area->name }}
        </h4>
        <small class="text-muted">Actualización automática cada 30s</small>
    </div>
    <div class="col-6 text-end">
        <a href="{{ route('kitchen.index') }}" class="btn btn-outline-secondary">
            <i class="ti tabler-arrow-left me-1"></i> Salir
        </a>
    </div>
</div>

<div class="row" id="orders-grid">
    @forelse($orders as $order)
    <div class="col-md-4 col-lg-3 mb-4">
        <div class="card h-100 border-top border-primary border-3 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-light py-2">
                <h6 class="mb-0 fw-bold">Mesa {{ $order->table->name }}</h6>
                <span class="badge bg-label-primary">#{{ $order->id }}</span>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($order->details as $detail)
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 text-truncate" style="max-width: 80%;">
                                <span class="badge bg-primary rounded-pill me-1">{{ $detail->quantity }}</span>
                                {{ $detail->product_name }}
                            </h6>
                            
                            @if($detail->updated_at->diffInMinutes(now()) < 5)
                                <span class="badge bg-danger animate__animated animate__flash animate__infinite infinite">NUEVO</span>
                            @else
                                <small class="text-muted">{{ $detail->updated_at->format('H:i') }}</small>
                            @endif
                        </div>
                        @if($detail->notes)
                        <div class="alert alert-warning p-1 mb-0 mt-1 small">
                            <i class="ti tabler-note me-1"></i> {{ $detail->notes }}
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-light py-2 text-center text-muted small">
                <i class="ti tabler-clock me-1"></i> {{ $order->created_at->diffForHumans() }}
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <div class="text-muted display-6 mb-3"><i class="ti tabler-cup"></i></div>
        <h3>No hay comandas pendientes</h3>
        <p>Todo está tranquilo en {{ $area->name }}.</p>
    </div>
    @endforelse
</div>

@push('scripts')
<style>
@keyframes flash {
  0%, 50%, 100% { opacity: 1; }
  25%, 75% { opacity: 0; }
}
.animate__flash {
  animation-name: flash;
  animation-duration: 2s;
}
</style>
<script>
    // 1. Auto-refresh page: REMOVED to avoid cancelling poll requests. 
    // We rely on the poll -> print -> reload flow.
    // Fallback refresh every 60s just in case.
    setTimeout(function(){
       console.log("Fallback refresh triggered (60s)");
       window.location.reload(1);
    }, 60000);

    // 2. Polling for New Prints (Every 5s)
    const AREA_ID = "{{ $area->id }}";
    const CHECK_URL = "{{ route('kitchen.check-new', $area) }}";
    const MARK_URL = "{{ route('kitchen.mark-printed') }}";
    
    // Local Bridge URL from Settings (or default)
    const LOCAL_BRIDGE_URL = "{{ $localBridgeUrl }}"; 

    console.log("Kitchen Monitor Started");
    console.log("Area ID:", AREA_ID);
    console.log("Check URL:", CHECK_URL);
    console.log("Bridge URL:", LOCAL_BRIDGE_URL);

    // Run first check immediately
    checkNewOrders();
    
    setInterval(checkNewOrders, 5000);

    async function checkNewOrders() {
        try {
            console.log("🔍 [" + new Date().toLocaleTimeString() + "] Checking for new kitchen tickets...");
            const res = await fetch(CHECK_URL);
            const data = await res.json();
            
            console.log("📩 Response:", data);

            if(data.status === 'success' && data.orders.length > 0) {
                console.log("✅ Found " + data.orders.length + " new tickets to print!");
                showToast(`Encontradas ${data.orders.length} órdenes nuevas`, 'success');
                
                for (let order of data.orders) {
                    await printTicket(order);
                }
            } else {
                 console.log("⏸️ No new tickets found.", data.debug || '');
            }
        } catch (error) {
            console.error("❌ Polling Error:", error);
            // Show toast to help user debug connectivity
            showToast("Error consultando tickets: " + error.message, 'error');
        }
    }

    async function printTicket(order) {
        // Construct Ticket Data for Kitchen
        const ticketData = {
            type: 'kitchen', // Mark as kitchen ticket for special formatting
            printer_name: "{{ $area->printer_name ?: ($defaultPrinter ?? 'POS-80') }}",
            table_name: order.table_name,
            waiter_name: order.waiter_name,
            date: order.created_at,
            items: order.items.map(item => ({
                quantity: item.quantity,
                name: item.name,
                notes: item.notes || ''
            }))
        };

        try {
            // 1. Send to Local Printer
            const printRes = await fetch(LOCAL_BRIDGE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(ticketData)
            });
            
            // Check if connection failed (e.g. server not running)
            if (!printRes.ok) {
                 throw new Error("Local Server unreachable or returned error: " + printRes.status);
            }

            const printResult = await printRes.json();

            if(printResult.status === 'success') {
                console.log("Ticket " + order.order_id + " printed successfully.");
                
                // 2. Mark as printed in Backend
                const itemIds = order.items.map(i => i.id);
                await fetch(MARK_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ item_ids: itemIds })
                });

                // Toast success (simplified)
                showToast(`Ticket #${order.order_id} impreso. Actualizando pantalla...`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                console.error("Printer Bridge Error:", printResult.message);
                showToast(`Error imprimiendo Ticket #${order.order_id}: ${printResult.message}`, 'error');
            }

        } catch (error) {
            console.error("Printing Failed (Check Bridge):", error);
            showToast(`Error de conexión con impresora: Asegúrate que server.php esté corriendo.`, 'error');
        }
    }

    function showToast(message, type = 'success') {
        const div = document.createElement('div');
        div.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed top-0 end-0 m-3 z-index-5`;
        div.style.zIndex = 9999;
        div.innerText = message;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 4000);
    }
</script>
@endpush

@endsection
