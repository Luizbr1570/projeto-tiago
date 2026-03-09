<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muitas tentativas — Soluv.IA</title>
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
            opacity: 0.12;
            pointer-events: none;
        }
        .card {
            background: #13131f;
            border: 1px solid #2a2a45;
            border-radius: 16px;
            padding: 48px 40px;
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 10;
            text-align: center;
            animation: fadeUp 0.4s ease forwards;
        }
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 32px;
        }
        .logo-icon {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; font-weight: 800; color: #fff;
        }
        .logo-text { font-size: 18px; font-weight: 700; }
        .logo-text span { color: #a855f7; }
        .icon-wrap {
            width: 64px; height: 64px;
            border-radius: 16px;
            background: rgba(255, 101, 132, 0.1);
            border: 1px solid rgba(255, 101, 132, 0.2);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px;
            font-size: 28px;
        }
        h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }
        p {
            font-size: 13px;
            color: #6b6b90;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 24px;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .btn:hover { opacity: 0.88; }
        .timer {
            margin-top: 20px;
            font-size: 12px;
            color: #6b6b90;
        }
        .timer span {
            color: #a855f7;
            font-weight: 600;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="blob" style="width:400px;height:400px;background:#ff6584;top:-150px;left:-150px;"></div>
    <div class="blob" style="width:300px;height:300px;background:#a855f7;bottom:-100px;right:-100px;"></div>

    <div class="card">
        <div class="logo">
            <div class="logo-icon">S</div>
            <div class="logo-text">Soluv<span>.IA</span></div>
        </div>

        <div class="icon-wrap">🔒</div>

        <h1>Muitas tentativas</h1>
        <p>
            Você fez tentativas demais em pouco tempo.<br>
            Por segurança, bloqueamos temporariamente o acesso.<br>
            Aguarde um momento e tente novamente.
        </p>

        <a href="{{ url()->previous() }}" class="btn">← Voltar</a>

        <div class="timer" id="timer-wrap" style="display:none;">
            Tente novamente em <span id="countdown"></span>
        </div>
    </div>

    <script>
        // Tenta ler o header Retry-After se disponível
        const retryAfter = {{ $exception->getHeaders()['Retry-After'] ?? 60 }};

        if (retryAfter > 0) {
            document.getElementById('timer-wrap').style.display = 'block';
            let seconds = retryAfter;

            function updateCountdown() {
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                document.getElementById('countdown').textContent =
                    m > 0 ? `${m}m ${s}s` : `${s}s`;

                if (seconds <= 0) {
                    document.getElementById('timer-wrap').textContent = 'Você já pode tentar novamente.';
                    return;
                }
                seconds--;
                setTimeout(updateCountdown, 1000);
            }

            updateCountdown();
        }
    </script>
</body>
</html>