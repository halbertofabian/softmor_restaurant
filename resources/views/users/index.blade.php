@extends('layouts.master')
@section('title', 'Usuarios')
@section('content')
<div class="row align-items-center mb-4">
    <div class="col-6">
        <h4 class="mb-0">Usuarios del Sistema</h4>
    </div>
    <div class="col-6 text-end">
        <a href="{{ route('users.create') }}" class="btn btn-primary">Nuevo Usuario</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>WhatsApp/País</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-bold">{{ $user->name }}</span>
                            <small class="text-muted">{{ $user->email }}</small>
                        </div>
                    </td>
                    <td>
                        @foreach($user->roles as $role)
                            <span class="badge bg-label-primary">{{ ucfirst($role->name) }}</span>
                        @endforeach
                    </td>
                    <td>
                        @if($user->estado == 'activo')
                            <span class="badge bg-label-success">Activo</span>
                        @else
                            <span class="badge bg-label-danger">Inactivo</span>
                        @endif
                    </td>
                    <td>{{ $user->pais_whatsapp }}</td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                <i class="ti tabler-dots-vertical"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('users.edit', $user) }}">Editar</a>
                                @if($user->id !== auth()->id())
                                <form action="{{ route('users.destroy', $user) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('¿Eliminar usuario? Esta acción no se puede deshacer.')">Eliminar</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer py-2">
        {{ $users->links() }}
    </div>
</div>
@endsection
