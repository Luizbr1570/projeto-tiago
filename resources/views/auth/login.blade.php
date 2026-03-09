<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Soluv.IA</title>
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
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.15;
            pointer-events: none;
        }
        .card {
            background: #13131f;
            border: 1px solid #2a2a45;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 10;
            animation: fadeUp 0.4s ease forwards;
        }
        .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 28px; }
        .logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; font-weight: 800; color: #fff;
        }
        .logo-text { font-size: 20px; font-weight: 700; }
        .logo-text span { color: #a855f7; }
        h2 { font-size: 20px; font-weight: 700; margin-bottom: 6px; }
        .sub { font-size: 13px; color: #6b6b90; margin-bottom: 28px; }
        label { font-size: 12px; color: #6b6b90; font-weight: 500; display: block; margin-bottom: 5px; }
        .input {
            background: #1a1a2e; border: 1px solid #2a2a45;
            border-radius: 8px; padding: 10px 13px;
            color: #e2e2f0; font-size: 14px;
            font-family: 'Inter', sans-serif; width: 100%; outline: none;
            transition: border-color 0.15s; margin-bottom: 16px;
        }
        .input:focus { border-color: #a855f7; }
        .input::placeholder { color: #3a3a55; }
        .btn {
            width: 100%;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            color: #fff; border: none; border-radius: 8px;
            padding: 12px; font-size: 14px; font-weight: 600;
            cursor: pointer; font-family: 'Inter', sans-serif;
            transition: opacity 0.15s; margin-top: 4px;
        }
        .btn:hover { opacity: 0.88; }
        .alert { background: rgba(255,101,132,0.1); color: #ff6584; border: 1px solid rgba(255,101,132,0.2); border-radius: 8px; padding: 10px 13px; font-size: 13px; margin-bottom: 18px; }
        .link { color: #a855f7; text-decoration: none; font-size: 13px; }
        .link:hover { text-decoration: underline; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);} }
    </style>
</head>
<body>
    <div class="blob" style="width:450px;height:450px;background:#a855f7;top:-150px;left:-150px;"></div>
    <div class="blob" style="width:350px;height:350px;background:#ec4899;bottom:-100px;right:-100px;"></div>

    <div class="card">
        <div class="logo">
            <div class="logo-icon">S</div>
            <div class="logo-text">Soluv<span>.IA</span></div>
        </div>
        <h2>Bem-vindo de volta</h2>
        <p class="sub">Entre na sua conta para continuar</p>

        @if($errors->any())
            <div class="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('auth.login') }}">
            @csrf
            <label>E-mail</label>
            <input type="email" name="email" class="input" placeholder="seu@email.com" value="{{ old('email') }}" required autofocus>
            <label>Senha</label>
            <input type="password" name="password" class="input" placeholder="••••••••" required>
            <button type="submit" class="btn">Entrar</button>
        </form>

        <p style="text-align:center;margin-top:22px;font-size:13px;color:#6b6b90;">
            Não tem conta? <a href="{{ route('register') }}" class="link">Criar agora</a>
        </p>
    </div>
</body>
</html>
