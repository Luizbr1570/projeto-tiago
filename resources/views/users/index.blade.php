@extends('layouts.app')
@section('title', 'Usuários')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1>Usuários — {{ auth()->user()->company->name }}</h1>
        <p>Gerencie os membros da sua equipe</p>
    </div>
    <button onclick="document.getElementById('modal-user').style.display='flex'" class="btn btn-primary">
        <i data-lucide="user-plus" style="width:14px;height:14px;"></i> Novo usuário
    </button>
</div>

{{-- Cards resumo --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px;" class="users-summary">
    <div class="card">
        <div class="card-label">Total de usuários</div>
        <div class="card-value">{{ $users->count() }}</div>
    </div>
    <div class="card">
        <div class="card-label">Administradores</div>
        <div class="card-value" style="color:#a855f7;">{{ $users->where('role','admin')->count() }}</div>
    </div>
    <div class="card">
        <div class="card-label">Agentes</div>
        <div class="card-value" style="color:#43e97b;">{{ $users->where('role','agent')->count() }}</div>
    </div>
</div>

{{-- Tabela desktop --}}
<div class="users-table-desktop card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table style="min-width:520px;">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>E-mail</th>
                    <th>Papel</th>
                    <th>Criado em</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr data-removable>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;border-radius:50%;background:{{ $user->role === 'admin' ? 'rgba(168,85,247,0.15)' : 'rgba(67,233,123,0.15)' }};display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:{{ $user->role === 'admin' ? '#a855f7' : '#43e97b' }};flex-shrink:0;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:13px;">
                                    {{ $user->name }}
                                    @if($user->id === auth()->id())
                                        <span style="font-size:10px;color:var(--muted);font-weight:400;">(você)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="color:var(--muted);">{{ $user->email }}</td>
                    <td>
                        <span class="badge {{ $user->role === 'admin' ? 'badge-novo' : 'badge-em_conversa' }}">
                            {{ $user->role === 'admin' ? 'Administrador' : 'Agente' }}
                        </span>
                    </td>
                    <td style="color:var(--muted);">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                            <button onclick="openEditModal('{{ $user->id }}','{{ addslashes($user->name) }}','{{ $user->email }}','{{ $user->role }}')"
                                class="btn btn-ghost" style="padding:5px 10px;font-size:12px;" title="Editar">
                                <i data-lucide="pencil" style="width:12px;height:12px;"></i>
                            </button>
                            <button onclick="openPasswordModal('{{ $user->id }}','{{ addslashes($user->name) }}')"
                                class="btn btn-ghost" style="padding:5px 10px;font-size:12px;" title="Redefinir senha">
                                <i data-lucide="key" style="width:12px;height:12px;"></i>
                            </button>
                            @if($user->id !== auth()->id())
                            <button type="button"
                                class="btn btn-danger"
                                style="padding:5px 10px;font-size:12px;"
                                data-delete-url="{{ route('users.destroy', $user->id) }}"
                                onclick="confirmDelete(this,'Usuário','{{ route("users.destroy", $user->id) }}','{{ route("users.restore", $user->id) }}')">
                                <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:36px;color:var(--muted);">Nenhum usuário encontrado</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Cards mobile --}}
<div class="users-cards-mobile" style="display:none;flex-direction:column;gap:10px;">
    @forelse($users as $user)
    <div class="card" data-removable style="padding:14px 16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:38px;height:38px;border-radius:50%;background:{{ $user->role === 'admin' ? 'rgba(168,85,247,0.15)' : 'rgba(67,233,123,0.15)' }};display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:{{ $user->role === 'admin' ? '#a855f7' : '#43e97b' }};flex-shrink:0;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <div style="font-weight:600;font-size:13px;">
                        {{ $user->name }}
                        @if($user->id === auth()->id())
                            <span style="font-size:10px;color:var(--muted);font-weight:400;">(você)</span>
                        @endif
                    </div>
                    <div style="font-size:11px;color:var(--muted);">{{ $user->email }}</div>
                </div>
            </div>
            <span class="badge {{ $user->role === 'admin' ? 'badge-novo' : 'badge-em_conversa' }}">
                {{ $user->role === 'admin' ? 'Admin' : 'Agente' }}
            </span>
        </div>
        <div style="display:flex;gap:8px;">
            <button onclick="openEditModal('{{ $user->id }}','{{ addslashes($user->name) }}','{{ $user->email }}','{{ $user->role }}')"
                class="btn btn-ghost" style="flex:1;justify-content:center;font-size:12px;padding:7px;">
                <i data-lucide="pencil" style="width:13px;height:13px;"></i> Editar
            </button>
            <button onclick="openPasswordModal('{{ $user->id }}','{{ addslashes($user->name) }}')"
                class="btn btn-ghost" style="flex:1;justify-content:center;font-size:12px;padding:7px;">
                <i data-lucide="key" style="width:13px;height:13px;"></i> Senha
            </button>
            @if($user->id !== auth()->id())
            <button type="button"
                class="btn btn-danger"
                style="padding:7px 12px;font-size:12px;"
                data-delete-url="{{ route('users.destroy', $user->id) }}"
                onclick="confirmDelete(this,'Usuário','{{ route("users.destroy", $user->id) }}','{{ route("users.restore", $user->id) }}')">
                <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
            </button>
            @endif
        </div>
    </div>
    @empty
    <div class="card" style="text-align:center;padding:36px;color:var(--muted);">Nenhum usuário encontrado</div>
    @endforelse
</div>

{{-- Modal novo usuário --}}
<div id="modal-user" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:100;align-items:center;justify-content:center;padding:16px;" onclick="if(event.target===this)this.style.display='none'">
    <div class="card" style="width:100%;max-width:420px;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:700;">Novo Usuário</h3>
            <button onclick="document.getElementById('modal-user').style.display='none'" style="background:none;border:none;color:var(--muted);cursor:pointer;">
                <i data-lucide="x" style="width:16px;height:16px;"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div style="margin-bottom:14px;">
                <label>Nome *</label>
                <input type="text" name="name" class="input" placeholder="João Silva" required value="{{ old('name') }}">
            </div>
            <div style="margin-bottom:14px;">
                <label>E-mail *</label>
                <input type="email" name="email" class="input" placeholder="joao@empresa.com" required value="{{ old('email') }}">
            </div>
            <div style="margin-bottom:14px;">
                <label>Papel *</label>
                <select name="role" class="input" required>
                    <option value="agent" {{ old('role') === 'agent' ? 'selected' : '' }}>Agente</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrador</option>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label>Senha *</label>
                <input type="password" name="password" class="input" placeholder="Mínimo 8 caracteres" required>
            </div>
            <div style="margin-bottom:20px;">
                <label>Confirmar senha *</label>
                <input type="password" name="password_confirmation" class="input" placeholder="Repita a senha" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i data-lucide="user-plus" style="width:14px;height:14px;"></i> Criar usuário
            </button>
        </form>
    </div>
</div>

{{-- Modal editar usuário --}}
<div id="modal-edit" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:100;align-items:center;justify-content:center;padding:16px;" onclick="if(event.target===this)this.style.display='none'">
    <div class="card" style="width:100%;max-width:420px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:700;">Editar Usuário</h3>
            <button onclick="document.getElementById('modal-edit').style.display='none'" style="background:none;border:none;color:var(--muted);cursor:pointer;">
                <i data-lucide="x" style="width:16px;height:16px;"></i>
            </button>
        </div>
        <form method="POST" id="form-edit" action="">
            @csrf @method('PATCH')
            <div style="margin-bottom:14px;">
                <label>Nome *</label>
                <input type="text" name="name" id="edit-name" class="input" required>
            </div>
            <div style="margin-bottom:14px;">
                <label>E-mail</label>
                <input type="text" id="edit-email" class="input" disabled style="opacity:0.5;cursor:not-allowed;">
                <span style="font-size:11px;color:var(--muted);margin-top:4px;display:block;">O e-mail não pode ser alterado.</span>
            </div>
            <div style="margin-bottom:20px;">
                <label>Papel *</label>
                <select name="role" id="edit-role" class="input" required>
                    <option value="agent">Agente</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i data-lucide="save" style="width:14px;height:14px;"></i> Salvar alterações
            </button>
        </form>
    </div>
</div>

{{-- Modal redefinir senha --}}
<div id="modal-password" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:100;align-items:center;justify-content:center;padding:16px;" onclick="if(event.target===this)this.style.display='none'">
    <div class="card" style="width:100%;max-width:420px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:700;">Redefinir Senha</h3>
            <button onclick="document.getElementById('modal-password').style.display='none'" style="background:none;border:none;color:var(--muted);cursor:pointer;">
                <i data-lucide="x" style="width:16px;height:16px;"></i>
            </button>
        </div>
        <p id="password-user-name" style="font-size:13px;color:var(--muted);margin-bottom:16px;"></p>
        <form method="POST" id="form-password" action="">
            @csrf @method('PATCH')
            <div style="margin-bottom:14px;">
                <label>Nova senha *</label>
                <input type="password" name="password" class="input" placeholder="Mínimo 8 caracteres" required>
            </div>
            <div style="margin-bottom:20px;">
                <label>Confirmar nova senha *</label>
                <input type="password" name="password_confirmation" class="input" placeholder="Repita a senha" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i data-lucide="key" style="width:14px;height:14px;"></i> Redefinir senha
            </button>
        </form>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .users-summary        { grid-template-columns: 1fr 1fr !important; }
    .users-summary > div:last-child { grid-column: span 2; }
    .users-table-desktop  { display: none !important; }
    .users-cards-mobile   { display: flex !important; }
}
@media (max-width: 480px) {
    .users-summary { grid-template-columns: 1fr !important; }
    .users-summary > div:last-child { grid-column: span 1; }
}
</style>

@push('scripts')
<script>
function openEditModal(id, name, email, role) {
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-email').value = email;
    document.getElementById('edit-role').value = role;
    document.getElementById('form-edit').action = '/users/' + id;
    document.getElementById('modal-edit').style.display = 'flex';
    lucide.createIcons();
}

function openPasswordModal(id, name) {
    document.getElementById('password-user-name').textContent = 'Redefinindo senha de: ' + name;
    document.getElementById('form-password').action = '/users/' + id + '/password';
    document.getElementById('modal-password').style.display = 'flex';
    lucide.createIcons();
}
</script>
@endpush

@endsection