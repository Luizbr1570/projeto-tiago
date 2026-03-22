<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Soluv.IA') — {{ auth()->user()->company->name ?? '' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="/leaflet.css"/>
    <script src="/leaflet.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0d0d14;
            --surface:   #13131f;
            --surface2:  #1a1a2e;
            --surface3:  #1f1f35;
            --border:    #2a2a45;
            --accent:    #a855f7;
            --accent2:   #ec4899;
            --accent-glow: rgba(168,85,247,0.25);
            --text:      #e2e2f0;
            --muted:     #6b6b90;
            --muted2:    #9090b0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 200px;
            min-height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 50;
            overflow: hidden;
            transition: width 0.22s ease, transform 0.25s ease;
        }
        .sidebar-logo {
            padding: 22px 20px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            overflow: hidden;
        }
        .logo-icon {
            width: 28px; height: 28px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 800; color: #fff;
            flex-shrink: 0;
        }
        .logo-text {
            font-size: 17px; font-weight: 700; color: var(--text);
            letter-spacing: -0.3px;
        }
        .logo-text span { color: var(--accent); }

        nav { padding: 12px 10px; flex: 1; overflow-y: auto; overflow-x: hidden; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            color: var(--muted);
            text-decoration: none;
            font-size: 13px; font-weight: 500;
            margin-bottom: 2px;
            transition: all 0.15s;
            white-space: nowrap;
            overflow: hidden;
        }
        .nav-item svg { width: 15px; height: 15px; flex-shrink: 0; }
        .nav-item i   { flex-shrink: 0; }
        .nav-item:hover { background: var(--surface2); color: var(--muted2); }
        .nav-item.active {
            background: linear-gradient(135deg, rgba(168,85,247,0.2), rgba(236,72,153,0.1));
            color: var(--accent);
            border: 1px solid rgba(168,85,247,0.2);
        }
        .nav-item.active svg { color: var(--accent); }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 49;
        }
        .sidebar-overlay.active { display: block; }

        .main-wrap {
            margin-left: 200px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            height: 56px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 24px;
            position: sticky; top: 0; z-index: 40;
        }

        .filter-bar-mobile {
            display: none;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 8px 16px;
            gap: 6px;
        }

        .menu-toggle {
            display: flex;
            background: none; border: none;
            color: var(--muted); cursor: pointer;
            padding: 4px; flex-shrink: 0;
            transition: color 0.15s;
        }
        .menu-toggle:hover { color: var(--text); }
        .menu-toggle svg { width: 20px; height: 20px; }

        .sidebar.collapsed { width: 56px; overflow: hidden; }
        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .nav-item span,
        .sidebar.collapsed .nav-text { display: none; }
        .sidebar.collapsed .sidebar-logo { justify-content: center; padding: 22px 0 18px; }
        .sidebar.collapsed .nav-item { justify-content: center; padding: 9px 0; gap: 0; }
        .sidebar.collapsed nav { padding: 12px 6px; }
        .sidebar.collapsed .btn-logout { justify-content: center; padding: 8px 0; }
        .sidebar.collapsed .btn-logout .nav-text { display: none; }
        .main-wrap { transition: margin-left 0.22s ease; }

        .search-bar {
            flex: 1; max-width: 380px;
            display: flex; align-items: center; gap: 8px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0 12px;
            height: 34px;
        }
        .search-bar input {
            background: none; border: none; outline: none;
            color: var(--text); font-size: 13px; font-family: 'Inter', sans-serif;
            width: 100%;
        }
        .search-bar input::placeholder { color: var(--muted); }
        .search-bar svg { width: 14px; height: 14px; color: var(--muted); flex-shrink: 0; }

        .filter-btns { display: flex; gap: 6px; margin-left: auto; }
        .filter-btn {
            padding: 5px 12px; border-radius: 6px;
            font-size: 12px; font-weight: 500;
            border: 1px solid var(--border);
            background: var(--surface2); color: var(--muted);
            cursor: pointer; transition: all 0.15s;
            font-family: 'Inter', sans-serif;
        }
        .filter-btn:hover, .filter-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
        .btn-export {
            display: flex; align-items: center; gap: 6px;
            padding: 6px 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border: none; border-radius: 7px;
            color: #fff; font-size: 12px; font-weight: 600;
            cursor: pointer; font-family: 'Inter', sans-serif; transition: opacity 0.15s;
        }
        .btn-export:hover { opacity: 0.9; }
        .topbar-icons { display: flex; align-items: center; gap: 12px; margin-left: 16px; }
        .topbar-icons svg { width: 18px; height: 18px; color: var(--muted); cursor: pointer; }
        .avatar {
            width: 30px; height: 30px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; color: #fff;
        }

        .page { padding: 28px 28px; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
        .page-header p  { font-size: 13px; color: var(--muted); margin-top: 3px; }

        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; }
        .card-label { font-size: 12px; color: var(--muted); font-weight: 500; margin-bottom: 6px; }
        .card-value { font-size: 32px; font-weight: 700; letter-spacing: -1px; line-height: 1; }

        .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; }
        .alert-success { background: rgba(67,233,123,0.1); color: #43e97b; border: 1px solid rgba(67,233,123,0.2); }
        .alert-error   { background: rgba(255,101,132,0.1); color: #ff6584; border: 1px solid rgba(255,101,132,0.2); }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 11px; font-weight: 600; letter-spacing: 0.5px; color: var(--muted); padding: 10px 14px; border-bottom: 1px solid var(--border); }
        td { padding: 12px 14px; font-size: 13px; border-bottom: 1px solid rgba(42,42,69,0.5); }
        tr:hover td { background: rgba(168,85,247,0.04); }
        tr:last-child td { border-bottom: none; }

        .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-novo        { background: rgba(168,85,247,0.15); color: #a855f7; }
        .badge-em_conversa { background: rgba(67,233,123,0.15); color: #43e97b; }
        .badge-pediu_preco { background: rgba(255,193,7,0.15);  color: #ffc107; }
        .badge-encaminhado { background: rgba(13,202,240,0.15); color: #0dcaf0; }
        .badge-perdido     { background: rgba(255,101,132,0.15);color: #ff6584; }
        .badge-recuperacao { background: rgba(255,152,0,0.15);  color: #ff9800; }

        .input {
            background: var(--surface2); border: 1px solid var(--border);
            border-radius: 8px; padding: 9px 13px;
            color: var(--text); font-size: 13px;
            font-family: 'Inter', sans-serif; width: 100%; outline: none;
            transition: border-color 0.15s;
        }
        .input:focus { border-color: var(--accent); }
        .input::placeholder { color: var(--muted); }
        label { font-size: 12px; color: var(--muted); display: block; margin-bottom: 5px; font-weight: 500; }

        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 16px; border-radius: 8px;
            font-size: 13px; font-weight: 600;
            cursor: pointer; border: none; text-decoration: none;
            font-family: 'Inter', sans-serif; transition: all 0.15s;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { opacity: 0.88; }
        .btn-ghost { background: var(--surface2); color: var(--muted); border: 1px solid var(--border); }
        .btn-ghost:hover { color: var(--text); }
        .btn-danger { background: rgba(255,101,132,0.12); color: #ff6584; border: 1px solid rgba(255,101,132,0.2); }
        .btn-danger:hover { background: rgba(255,101,132,0.22); }

        .trend-up   { color: #43e97b; font-size: 12px; font-weight: 600; }
        .trend-down { color: #ff6584; font-size: 12px; font-weight: 600; }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease forwards; }

        .pagination { display: flex; gap: 4px; align-items: center; flex-wrap: wrap; }
        .pagination > * { display: inline-flex; }
        nav[aria-label="Pagination"] { display: flex; justify-content: center; margin-top: 16px; }
        [aria-label="Pagination"] span,
        [aria-label="Pagination"] a {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 32px; height: 32px; padding: 0 10px;
            border-radius: 6px; font-size: 12px; font-weight: 500;
            border: 1px solid var(--border);
            background: var(--surface2); color: var(--muted);
            text-decoration: none; transition: all 0.15s; margin: 0 2px;
        }
        [aria-label="Pagination"] a:hover { background: var(--surface3); color: var(--text); border-color: var(--accent); }
        [aria-label="Pagination"] [aria-current="page"] > span,
        [aria-label="Pagination"] span[aria-current="page"] { background: var(--accent); color: #fff; border-color: var(--accent); }
        [aria-label="Pagination"] span.cursor-default { opacity: 0.4; cursor: default; }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); width: 220px !important; }
            .sidebar.open { transform: translateX(0); }
            .sidebar.collapsed { width: 220px !important; }
            .sidebar.collapsed .logo-text,
            .sidebar.collapsed .nav-item span,
            .sidebar.collapsed .nav-text { display: revert !important; }
            .sidebar.collapsed .sidebar-logo { justify-content: flex-start !important; padding: 22px 20px 18px !important; }
            .sidebar.collapsed .nav-item { justify-content: flex-start !important; padding: 9px 12px !important; }
            .sidebar.collapsed nav { padding: 12px 10px !important; }
            .main-wrap { margin-left: 0 !important; }
            .search-bar { max-width: 260px; }
            .filter-btns { gap: 4px; }
            .filter-btn { padding: 5px 9px; font-size: 11px; }
        }

        @media (max-width: 768px) {
            .topbar { padding: 0 16px; gap: 8px; height: 56px; flex-wrap: nowrap; }
            .search-bar { max-width: 100%; flex: 1; }
            .filter-btns { display: none; }
            .btn-export  { display: none; }
            .topbar-icons { margin-left: auto; gap: 8px; }
            .bell-icon, .help-icon { display: none; }
            .filter-bar-mobile { display: flex; flex-wrap: nowrap; }
            .filter-bar-mobile .filter-btn { flex: 1; text-align: center; justify-content: center; }
            .page { padding: 16px; }
            .page-header h1 { font-size: 18px; }
            .card-value { font-size: 26px; }
        }

        @media (max-width: 480px) {
            .topbar { height: 50px; }
            .page { padding: 12px; }
        }

        .confirm-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.75); z-index: 200;
            align-items: center; justify-content: center; padding: 20px;
        }
        .confirm-overlay.active { display: flex; }
        .confirm-box {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; padding: 28px;
            width: 100%; max-width: 380px;
            animation: fadeIn 0.2s ease forwards;
        }
        .confirm-icon {
            width: 44px; height: 44px; border-radius: 10px;
            background: rgba(255,101,132,0.12);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 16px;
        }
        .confirm-title { font-size: 16px; font-weight: 700; margin-bottom: 8px; }
        .confirm-msg { font-size: 13px; color: var(--muted); line-height: 1.6; margin-bottom: 22px; }
        .confirm-actions { display: flex; gap: 10px; }
        .confirm-actions .btn { flex: 1; justify-content: center; }

        .undo-toast {
            position: fixed; bottom: 28px; left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 10px; padding: 12px 18px;
            display: flex; align-items: center; gap: 14px;
            font-size: 13px; z-index: 300;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s ease;
            opacity: 0; white-space: nowrap;
        }
        .undo-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        .undo-toast-msg { color: var(--muted2); }
        .undo-toast-msg strong { color: var(--text); }
        .undo-btn {
            background: none; border: none; color: var(--accent);
            font-size: 13px; font-weight: 600; cursor: pointer;
            font-family: 'Inter', sans-serif; padding: 0; transition: opacity 0.15s;
        }
        .undo-btn:hover { opacity: 0.75; }
        .undo-progress {
            position: absolute; bottom: 0; left: 0;
            height: 3px; border-radius: 0 0 10px 10px;
            background: var(--accent); width: 100%;
            transform-origin: left; transition: transform linear;
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="confirm-icon">
            <i data-lucide="trash-2" style="width:20px;height:20px;color:#ff6584;"></i>
        </div>
        <div class="confirm-title" id="confirmTitle">Confirmar exclusão</div>
        <div class="confirm-msg" id="confirmMsg">Tem certeza que deseja remover este item? Você terá 6 segundos para desfazer.</div>
        <div class="confirm-actions">
            <button class="btn btn-ghost" onclick="closeConfirm()">Cancelar</button>
            <button class="btn btn-danger" id="confirmBtn">
                <i data-lucide="trash-2" style="width:13px;height:13px;"></i> Remover
            </button>
        </div>
    </div>
</div>

<div class="undo-toast" id="undoToast">
    <span class="undo-toast-msg" id="undoMsg">Item removido</span>
    <button class="undo-btn" id="undoBtn">↩ Desfazer</button>
    <div class="undo-progress" id="undoProgress"></div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">S</div>
        <div class="logo-text">Soluv<span>.IA</span></div>
    </div>
    <nav>
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i data-lucide="layout-dashboard"></i> <span class="nav-text">Visão Geral</span>
        </a>
        <a href="{{ route('funnel.index') }}" class="nav-item {{ request()->routeIs('funnel*') ? 'active' : '' }}">
            <i data-lucide="filter"></i> <span class="nav-text">Funil</span>
        </a>
        @if(in_array(auth()->user()->role, ['admin','agent']))
        <a href="{{ route('leads.index') }}" class="nav-item {{ request()->routeIs('leads.*') ? 'active' : '' }}">
            <i data-lucide="users"></i> <span class="nav-text">Leads</span>
        </a>
        <a href="{{ route('chat-sessions.index') }}" class="nav-item {{ request()->routeIs('chat-sessions.*') ? 'active' : '' }}">
            <i data-lucide="message-circle"></i> <span class="nav-text">Sessões de Chat</span>
        </a>
        @endif
        <a href="{{ route('conversations.index') }}" class="nav-item {{ request()->routeIs('conversations.*') ? 'active' : '' }}">
            <i data-lucide="headphones"></i> <span class="nav-text">Atendimento</span>
        </a>
        @if(auth()->user()->role === 'admin')
        <a href="{{ route('products.index') }}" class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
            <i data-lucide="shopping-bag"></i> <span class="nav-text">Produtos</span>
        </a>
        <a href="{{ route('followups.index') }}" class="nav-item {{ request()->routeIs('followups.*') ? 'active' : '' }}">
            <i data-lucide="refresh-cw"></i> <span class="nav-text">Recuperação</span>
        </a>
        <a href="{{ route('sales.index') }}" class="nav-item {{ request()->routeIs('sales.*') ? 'active' : '' }}">
            <i data-lucide="dollar-sign"></i> <span class="nav-text">Vendas</span>
        </a>
        <a href="{{ route('metrics.index') }}" class="nav-item {{ request()->routeIs('metrics.*') ? 'active' : '' }}">
            <i data-lucide="bar-chart-2"></i> <span class="nav-text">Relatórios</span>
        </a>
        <a href="{{ route('insights.index') }}" class="nav-item {{ request()->routeIs('insights.*') ? 'active' : '' }}">
            <i data-lucide="sparkles"></i> <span class="nav-text">Insights IA</span>
        </a>
        <a href="{{ route('admin.meta.embedded-signup.index') }}" class="nav-item {{ request()->routeIs('admin.meta.embedded-signup.*') ? 'active' : '' }}">
            <i data-lucide="message-circle-more"></i> <span class="nav-text">Meta / WhatsApp</span>
        </a>
        <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <i data-lucide="users-2"></i> <span class="nav-text">Equipe</span>
        </a>
        @endif
        <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings*') ? 'active' : '' }}">
            <i data-lucide="settings"></i> <span class="nav-text">Configurações</span>
        </a>
    </nav>
    <div style="padding:14px 10px;border-top:1px solid var(--border);">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-ghost btn-logout" style="width:100%;justify-content:center;font-size:12px;">
                <i data-lucide="log-out" style="width:13px;height:13px;flex-shrink:0;"></i>
                <span class="nav-text"> Sair</span>
            </button>
        </form>
    </div>
</aside>

<div class="main-wrap">
    <header class="topbar">
        <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">
            <i data-lucide="menu"></i>
        </button>
        <form method="GET" action="{{ route('leads.index') }}" style="flex:1;max-width:380px;">
            <div class="search-bar">
                <i data-lucide="search"></i>
                <input type="text" name="search" placeholder="Buscar lead, número ou produto..." value="{{ request('search') }}">
            </div>
        </form>
        @yield('topbar-extra')
        <div class="filter-btns">
            @if(request()->routeIs('dashboard') || request()->routeIs('sales.*'))
            @php $periodBase = request()->routeIs('sales.*') ? route('sales.index') : route('dashboard'); @endphp
            <a href="{{ $periodBase . '?period=today' }}"  class="filter-btn {{ request('period','today') === 'today'  ? 'active' : '' }}">Hoje</a>
            <a href="{{ $periodBase . '?period=7days' }}"  class="filter-btn {{ request('period') === '7days'           ? 'active' : '' }}">7 dias</a>
            <a href="{{ $periodBase . '?period=30days' }}" class="filter-btn {{ request('period') === '30days'          ? 'active' : '' }}">30 dias</a>
            @endif
        </div>
        @if(auth()->user()->role === 'admin')
        <a href="{{ route('export.leads') }}" class="btn-export">
            <i data-lucide="download" style="width:13px;height:13px;"></i> Exportar
        </a>
        @endif
        <div class="topbar-icons">
            <i data-lucide="bell" class="bell-icon"></i>
            <i data-lucide="help-circle" class="help-icon"></i>
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
        </div>
    </header>

    @if(request()->routeIs('dashboard') || request()->routeIs('sales.*') || auth()->user()->role === 'admin')
    <div class="filter-bar-mobile">
        @if(request()->routeIs('dashboard') || request()->routeIs('sales.*'))
        @php $periodBase = request()->routeIs('sales.*') ? route('sales.index') : route('dashboard'); @endphp
        <a href="{{ $periodBase . '?period=today' }}"  class="filter-btn {{ request('period','today') === 'today'  ? 'active' : '' }}">Hoje</a>
        <a href="{{ $periodBase . '?period=7days' }}"  class="filter-btn {{ request('period') === '7days'           ? 'active' : '' }}">7 dias</a>
        <a href="{{ $periodBase . '?period=30days' }}" class="filter-btn {{ request('period') === '30days'          ? 'active' : '' }}">30 dias</a>
        @endif
        @if(auth()->user()->role === 'admin')
        <a href="{{ route('export.leads') }}" class="filter-btn" style="background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border-color:transparent;">
            <i data-lucide="download" style="width:12px;height:12px;display:inline;vertical-align:middle;margin-right:3px;"></i> Exportar
        </a>
        @endif
    </div>
    @endif

    <main class="page fade-in">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
</div>

<script>
    var isTabletOrMobile = function() { return window.innerWidth <= 1024; };

    function toggleSidebar() {
        if (isTabletOrMobile()) {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        } else {
            var sidebar = document.getElementById('sidebar');
            var mainWrap = document.querySelector('.main-wrap');
            sidebar.classList.toggle('collapsed');
            if (sidebar.classList.contains('collapsed')) {
                mainWrap.style.marginLeft = '56px';
                localStorage.setItem('sidebar-collapsed', '1');
            } else {
                mainWrap.style.marginLeft = '200px';
                localStorage.setItem('sidebar-collapsed', '0');
            }
            setTimeout(function(){ lucide.createIcons(); }, 250);
        }
    }

    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('active');
    }

    document.querySelectorAll('.sidebar .nav-item').forEach(function(el) {
        el.addEventListener('click', function() {
            if (isTabletOrMobile()) closeSidebar();
        });
    });

    window.addEventListener('DOMContentLoaded', function() {
        var sidebar  = document.getElementById('sidebar');
        var mainWrap = document.querySelector('.main-wrap');
        if (window.innerWidth > 1024) {
            if (localStorage.getItem('sidebar-collapsed') === '1') {
                sidebar.classList.add('collapsed');
                mainWrap.style.marginLeft = '56px';
            } else {
                mainWrap.style.marginLeft = '200px';
            }
        } else {
            mainWrap.style.marginLeft = '0px';
            sidebar.classList.remove('collapsed');
        }
    });

    window.addEventListener('resize', function() {
        var sidebar  = document.getElementById('sidebar');
        var mainWrap = document.querySelector('.main-wrap');
        if (window.innerWidth <= 1024) {
            mainWrap.style.marginLeft = '0px';
            sidebar.classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('active');
        } else {
            mainWrap.style.marginLeft = sidebar.classList.contains('collapsed') ? '56px' : '200px';
        }
    });

    var _undoRestoreUrl = null;
    var _undoTimer      = null;

    function confirmDelete(btn, label, deleteUrl, restoreUrl) {
        _undoRestoreUrl = restoreUrl || null;
        document.getElementById('confirmTitle').textContent = 'Remover ' + label;
        document.getElementById('confirmMsg').textContent =
            'Tem certeza que deseja remover este ' + label.toLowerCase() +
            '? Você terá 6 segundos para desfazer.';
        document.getElementById('confirmBtn').onclick = function() {
            closeConfirm();
            _submitDelete(deleteUrl, label);
        };
        document.getElementById('confirmOverlay').classList.add('active');
        lucide.createIcons();
    }

    function closeConfirm() {
        document.getElementById('confirmOverlay').classList.remove('active');
    }

    document.getElementById('confirmOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeConfirm();
    });

    function _submitDelete(deleteUrl, label) {
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch(deleteUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: '_method=DELETE'
        })
        .then(function(res) {
            if (!res.ok) throw new Error('Erro ao remover');
            _removeRowFromDOM(deleteUrl);
            _showUndoToast(label);
        })
        .catch(function() {
            var f = document.createElement('form');
            f.method = 'POST'; f.action = deleteUrl;
            f.innerHTML = '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                          '<input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(f);
            f.submit();
        });
    }

    function _removeRowFromDOM(deleteUrl) {
        var btn = document.querySelector('[data-delete-url="' + deleteUrl + '"]');
        if (!btn) return;
        var row = btn.closest('tr') || btn.closest('[data-removable]');
        if (row) {
            row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            row.style.opacity = '0';
            row.style.transform = 'translateX(16px)';
            setTimeout(function() { row.remove(); }, 300);
        }
    }

    function _showUndoToast(label) {
        clearTimeout(_undoTimer);
        var toast    = document.getElementById('undoToast');
        var progress = document.getElementById('undoProgress');
        var msg      = document.getElementById('undoMsg');
        msg.innerHTML = '<strong>' + label + '</strong> removido';
        toast.classList.add('show');
        progress.style.transition = 'none';
        progress.style.transform  = 'scaleX(1)';
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                progress.style.transition = 'transform 6s linear';
                progress.style.transform  = 'scaleX(0)';
            });
        });
        _undoTimer = setTimeout(function() {
            toast.classList.remove('show');
            _undoRestoreUrl = null;
        }, 6000);
    }

    document.getElementById('undoBtn').addEventListener('click', function() {
        if (!_undoRestoreUrl) return;
        clearTimeout(_undoTimer);
        document.getElementById('undoToast').classList.remove('show');
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch(_undoRestoreUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(function() { window.location.reload(); })
        .catch(function() { window.location.reload(); });
        _undoRestoreUrl = null;
    });
</script>
<script>lucide.createIcons();</script>
@stack('scripts')
</body>
</html>