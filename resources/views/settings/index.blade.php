@extends('layouts.app')
@section('title', 'Configurações')

@section('content')
<div class="page-header">
    <h1>Configurações</h1>
    <p>Gerencie os dados da sua empresa e perfil</p>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

    {{-- Dados da empresa --}}
    <div class="card">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
            <div style="width:36px;height:36px;border-radius:9px;background:rgba(168,85,247,0.15);display:flex;align-items:center;justify-content:center;">
                <i data-lucide="building-2" style="width:16px;height:16px;color:#a855f7;"></i>
            </div>
            <div>
                <div style="font-size:14px;font-weight:600;">Dados da Empresa</div>
                <div style="font-size:12px;color:var(--muted);">Plano: <span style="color:#a855f7;font-weight:600;">{{ ucfirst($company->plan ?? 'free') }}</span></div>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.company') }}">
            @csrf @method('PATCH')
            <div style="margin-bottom:14px;">
                <label>Nome da empresa</label>
                <input type="text" name="name" class="input" value="{{ old('name', $company->name) }}" required>
            </div>
            <div style="margin-bottom:20px;">
                <label>Slug</label>
                <input type="text" class="input" value="{{ $company->slug }}" disabled
                       style="opacity:0.5;cursor:not-allowed;">
                <span style="font-size:11px;color:var(--muted);margin-top:4px;display:block;">O slug não pode ser alterado.</span>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i data-lucide="save" style="width:14px;height:14px;"></i> Salvar empresa
            </button>
        </form>
    </div>

    {{-- Dados do perfil --}}
    <div class="card">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
            <div style="width:36px;height:36px;border-radius:9px;background:rgba(236,72,153,0.15);display:flex;align-items:center;justify-content:center;">
                <i data-lucide="user" style="width:16px;height:16px;color:#ec4899;"></i>
            </div>
            <div>
                <div style="font-size:14px;font-weight:600;">Meu Perfil</div>
                <div style="font-size:12px;color:var(--muted);">Role: <span style="color:#ec4899;font-weight:600;">{{ ucfirst($user->role) }}</span></div>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.profile') }}">
            @csrf @method('PATCH')
            <div style="margin-bottom:14px;">
                <label>Nome</label>
                <input type="text" name="name" class="input" value="{{ old('name', $user->name) }}" required>
            </div>
            <div style="margin-bottom:20px;">
                <label>E-mail</label>
                <input type="email" name="email" class="input" value="{{ old('email', $user->email) }}" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i data-lucide="save" style="width:14px;height:14px;"></i> Salvar perfil
            </button>
        </form>
    </div>

    {{-- Alterar senha --}}
    <div class="card" style="grid-column:span 2;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
            <div style="width:36px;height:36px;border-radius:9px;background:rgba(255,101,132,0.1);display:flex;align-items:center;justify-content:center;">
                <i data-lucide="lock" style="width:16px;height:16px;color:#ff6584;"></i>
            </div>
            <div style="font-size:14px;font-weight:600;">Alterar Senha</div>
        </div>

        <form method="POST" action="{{ route('settings.password') }}">
            @csrf @method('PATCH')
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:20px;">
                <div>
                    <label>Senha atual</label>
                    <input type="password" name="current_password" class="input" placeholder="••••••••" required>
                </div>
                <div>
                    <label>Nova senha</label>
                    <input type="password" name="password" class="input" placeholder="••••••••" required>
                </div>
                <div>
                    <label>Confirmar nova senha</label>
                    <input type="password" name="password_confirmation" class="input" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn btn-danger" style="justify-content:center;">
                <i data-lucide="lock" style="width:14px;height:14px;"></i> Alterar senha
            </button>
        </form>
    </div>

</div>
@endsection