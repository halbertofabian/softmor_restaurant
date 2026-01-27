@extends('layouts.master')
@section('title', 'Comandas Activas')
@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Comandas Activas</h5>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Comanda #</th>
                    <th>Mesa</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Creada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->table->name ?? 'N/A' }}</td>
                    <td>
                        @switch($order->status)
                            @case('open') <span class="badge bg-label-primary">Abierta</span> @break
                            @case('sent') <span class="badge bg-label-warning">Enviada</span> @break
                            @case('in_preparation') <span class="badge bg-label-info">En Prep.</span> @break
                            @case('closed') <span class="badge bg-label-success">Cerrada</span> @break
                            @case('canceled') <span class="badge bg-label-danger">Cancelada</span> @break
                        @endswitch
                    </td>
                    <td>${{ number_format($order->total, 2) }}</td>
                    <td>{{ $order->created_at->format('d/m H:i') }}</td>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-icon btn-text-primary"><i class="ti tabler-eye"></i></a>
                        @if(!auth()->user()->hasRole('mesero'))
                        <a href="{{ route('pos.checkout', $order) }}" class="btn btn-sm btn-icon btn-text-success"><i class="ti tabler-cash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
