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

        /* ── Sidebar ── */
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
        }
        .sidebar-logo {
            padding: 22px 20px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logo-icon {
            width: 28px; height: 28px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 800; color: #fff;
        }
        .logo-text {
            font-size: 17px; font-weight: 700; color: var(--text);
            letter-spacing: -0.3px;
        }
        .logo-text span { color: var(--accent); }

        nav { padding: 12px 10px; flex: 1; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            color: var(--muted);
            text-decoration: none;
            font-size: 13px; font-weight: 500;
            margin-bottom: 2px;
            transition: all 0.15s;
        }
        .nav-item svg { width: 15px; height: 15px; flex-shrink: 0; }
        .nav-item:hover { background: var(--surface2); color: var(--muted2); }
        .nav-item.active {
            background: linear-gradient(135deg, rgba(168,85,247,0.2), rgba(236,72,153,0.1));
            color: var(--accent);
            border: 1px solid rgba(168,85,247,0.2);
        }
        .nav-item.active svg { color: var(--accent); }

        /* ── Main ── */
        .main-wrap {
            margin-left: 200px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── Topbar ── */
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
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px; font-weight: 500;
            border: 1px solid var(--border);
            background: var(--surface2);
            color: var(--muted);
            cursor: pointer;
            transition: all 0.15s;
            font-family: 'Inter', sans-serif;
        }
        .filter-btn:hover, .filter-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }
        .btn-export {
            display: flex; align-items: center; gap: 6px;
            padding: 6px 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border: none; border-radius: 7px;
            color: #fff; font-size: 12px; font-weight: 600;
            cursor: pointer; font-family: 'Inter', sans-serif;
            transition: opacity 0.15s;
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

        /* ── Page ── */
        .page { padding: 28px 28px; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
        .page-header p  { font-size: 13px; color: var(--muted); margin-top: 3px; }

        /* ── Cards ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }
        .card-label {
            font-size: 12px; color: var(--muted); font-weight: 500; margin-bottom: 6px;
        }
        .card-value {
            font-size: 32px; font-weight: 700; letter-spacing: -1px; line-height: 1;
        }

        /* ── Alert ── */
        .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; }
        .alert-success { background: rgba(67,233,123,0.1); color: #43e97b; border: 1px solid rgba(67,233,123,0.2); }
        .alert-error   { background: rgba(255,101,132,0.1); color: #ff6584; border: 1px solid rgba(255,101,132,0.2); }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left; font-size: 11px; font-weight: 600;
            letter-spacing: 0.5px; color: var(--muted);
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 12px 14px; font-size: 13px;
            border-bottom: 1px solid rgba(42,42,69,0.5);
        }
        tr:hover td { background: rgba(168,85,247,0.04); }
        tr:last-child td { border-bottom: none; }

        /* ── Badge ── */
        .badge {
            display: inline-flex; align-items: center;
            padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }
        .badge-novo        { background: rgba(168,85,247,0.15); color: #a855f7; }
        .badge-em_conversa { background: rgba(67,233,123,0.15); color: #43e97b; }
        .badge-pediu_preco { background: rgba(255,193,7,0.15);  color: #ffc107; }
        .badge-encaminhado { background: rgba(13,202,240,0.15); color: #0dcaf0; }
        .badge-perdido     { background: rgba(255,101,132,0.15);color: #ff6584; }
        .badge-recuperacao { background: rgba(255,152,0,0.15);  color: #ff9800; }

        /* ── Inputs ── */
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

        /* ── Btn ── */
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

        /* ── Trend ── */
        .trend-up   { color: #43e97b; font-size: 12px; font-weight: 600; }
        .trend-down { color: #ff6584; font-size: 12px; font-weight: 600; }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease forwards; }

        /* ── Paginação ── */
        .pagination { display: flex; gap: 4px; align-items: center; flex-wrap: wrap; }
        .pagination > * { display: inline-flex; }
        nav[aria-label="Pagination"] { display: flex; justify-content: center; margin-top: 16px; }
        /* Tailwind pagination override para tema escuro */
        [aria-label="Pagination"] span,
        [aria-label="Pagination"] a {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 32px; height: 32px; padding: 0 10px;
            border-radius: 6px; font-size: 12px; font-weight: 500;
            border: 1px solid var(--border);
            background: var(--surface2); color: var(--muted);
            text-decoration: none; transition: all 0.15s; margin: 0 2px;
        }
        [aria-label="Pagination"] a:hover {
            background: var(--surface3); color: var(--text); border-color: var(--accent);
        }
        [aria-label="Pagination"] [aria-current="page"] > span,
        [aria-label="Pagination"] span[aria-current="page"] {
            background: var(--accent); color: #fff; border-color: var(--accent);
        }
        [aria-label="Pagination"] span.cursor-default {
            opacity: 0.4; cursor: default;
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">S</div>
        <div class="logo-text">Soluv<span>.IA</span></div>
    </div>
    <nav>
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i data-lucide="layout-dashboard"></i> Visão Geral
        </a>
        <a href="{{ route('funnel.index') }}" class="nav-item {{ request()->routeIs('funnel*') ? 'active' : '' }}">
            <i data-lucide="filter"></i> Funil
        </a>
        @if(auth()->user()->role === 'admin')
        <a href="{{ route('products.index') }}" class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
            <i data-lucide="shopping-bag"></i> Produtos
        </a>
        @endif
        <a href="{{ route('conversations.index') }}" class="nav-item {{ request()->routeIs('conversations.*') ? 'active' : '' }}">
            <i data-lucide="headphones"></i> Atendimento
        </a>
        @if(auth()->user()->role === 'admin')
        <a href="{{ route('followups.index') }}" class="nav-item {{ request()->routeIs('followups.*') ? 'active' : '' }}">
            <i data-lucide="refresh-cw"></i> Recuperação
        </a>
        <a href="{{ route('metrics.index') }}" class="nav-item {{ request()->routeIs('metrics.*') ? 'active' : '' }}">
            <i data-lucide="bar-chart-2"></i> Relatórios
        </a>
        @endif
        <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings*') ? 'active' : '' }}">
            <i data-lucide="settings"></i> Configurações
        </a>
    </nav>
    <div style="padding:14px 10px;border-top:1px solid var(--border);">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-ghost" style="width:100%;justify-content:center;font-size:12px;">
                <i data-lucide="log-out" style="width:13px;height:13px;"></i> Sair
            </button>
        </form>
    </div>
</aside>

<div class="main-wrap">
    <header class="topbar">
        <form method="GET" action="{{ route('leads.index') }}" style="flex:1;max-width:380px;">
        <div class="search-bar">
            <i data-lucide="search"></i>
            <input type="text" name="search" placeholder="Buscar lead, número ou produto..." value="{{ request('search') }}">
        </div>
        </form>
        @yield('topbar-extra')
        <div class="filter-btns">
            <a href="{{ request()->fullUrlWithQuery(['period' => 'today']) }}"
               class="filter-btn {{ request('period','today') === 'today' ? 'active' : '' }}">Hoje</a>
            <a href="{{ request()->fullUrlWithQuery(['period' => '7days']) }}"
               class="filter-btn {{ request('period') === '7days' ? 'active' : '' }}">7 dias</a>
            <a href="{{ request()->fullUrlWithQuery(['period' => '30days']) }}"
               class="filter-btn {{ request('period') === '30days' ? 'active' : '' }}">30 dias</a>
        </div>
        @if(auth()->user()->role === 'admin')
        <a href="{{ route('export.leads') }}" class="btn-export">
            <i data-lucide="download" style="width:13px;height:13px;"></i> Exportar
        </a>
        @endif
        <div class="topbar-icons">
            <i data-lucide="bell"></i>
            <i data-lucide="help-circle"></i>
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
        </div>
    </header>

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

<script>lucide.createIcons();</script>
@stack('scripts')
</body>
</html>