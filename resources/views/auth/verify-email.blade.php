<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar e-mail — Soluv.IA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0d0d14;
            color: #e2e2f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .blob { position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.13; pointer-events: none; }
        .card {
            background: #13131f;
            border: 1px solid #2a2a45;
            border-radius: 16px;
            padding: 48px 40px;
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 10;
            text-align: center;
            animation: fadeUp 0.4s ease forwards;
        }
        .logo { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 32px; }
        .logo-icon { width: 32px; height: 32px; background: linear-gradient(135deg, #a855f7, #ec4899); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 800; color: #fff; }
        .logo-text { font-size: 18px; font-weight: 700; }
        .logo-text span { color: #a855f7; }
        .icon-wrap { width: 64px; height: 64px; border-radius: 16px; background: rgba(168,85,247,0.1); border: 1px solid rgba(168,85,247,0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 28px; }
        h1 { font-size: 20px; font-weight: 700; margin-bottom: 10px; }
        p { font-size: 13px; color: #6b6b90; line-height: 1.6; margin-bottom: 28px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 28px; background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; text-decoration: none; transition: opacity 0.15s; width: 100%; justify-content: center; }
        .btn:hover { opacity: 0.88; }
        .btn-ghost { background: transparent; border: 1px solid #2a2a45; color: #6b6b90; margin-top: 10px; }
        .btn-ghost:hover { color: #e2e2f0; }
        .alert-success { background: rgba(67,233,123,0.1); color: #43e97b; border: 1px solid rgba(67,233,123,0.2); border-radius: 8px; padding: 10px 13px; font-size: 13px; margin-bottom: 20px; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);} }
    </style>
</head>
<body>
    <div class="blob" style="width:400px;height:400px;background:#a855f7;top:-150px;left:-150px;"></div>
    <div class="blob" style="width:300px;height:300px;background:#ec4899;bottom:-100px;right:-100px;"></div>

    <div class="card">
        <div class="logo">
            <div class="logo-icon">S</div>
            <div class="logo-text">Soluv<span>.IA</span></div>
        </div>

        <div class="icon-wrap">✉️</div>
        <h1>Verifique seu e-mail</h1>
        <p>
            Enviamos um link de confirmação para <strong>{{ auth()->user()->email }}</strong>.<br>
            Clique no link para ativar sua conta e acessar o sistema.
        </p>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn">Reenviar e-mail de verificação</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" style="margin-top:10px;">
            @csrf
            <button type="submit" class="btn btn-ghost">Sair da conta</button>
        </form>
    </div>
</body>
</html>