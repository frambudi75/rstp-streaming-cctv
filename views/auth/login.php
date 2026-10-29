<?php
declare(strict_types=1);
$pageTitle = 'Traffic Operation Console';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login // Traffic Operation Console (TMC)</title>
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/img/favicon.svg">
    <link rel="alternate icon" type="image/png" href="<?= APP_URL ?>/assets/img/favicon.png">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            background: #090d16;
            color: #e2e8f0;
            overflow-x: hidden;
        }

        .auth-split {
            display: flex;
            width: 100vw;
            min-height: 100vh;
        }

        /* Left Hero / Telemetry Side */
        .auth-hero {
            flex: 1.2;
            background: linear-gradient(145deg, #0d1527 0%, #080c17 100%);
            border-right: 1px solid #1e293b;
            padding: 3.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .auth-hero::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
        }

        .hero-top {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.65rem;
            background: #111e38;
            border: 1px solid #1e3a8a;
            color: #60a5fa;
            font-size: 0.75rem;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .hero-title {
            font-size: 2.4rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.03em;
            color: #f8fafc;
            margin-bottom: 1rem;
        }

        .hero-desc {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.6;
            max-width: 460px;
        }

        .hero-telemetry {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin: 2rem 0;
            padding: 1.25rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid #1e293b;
            border-radius: 6px;
        }

        .telemetry-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .telemetry-label {
            font-size: 0.72rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-family: ui-monospace, monospace;
        }

        .telemetry-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: #e2e8f0;
            font-family: ui-monospace, monospace;
        }

        .hero-footer {
            position: relative;
            z-index: 2;
            font-size: 0.75rem;
            color: #475569;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: ui-monospace, monospace;
        }

        /* Right Form Side */
        .auth-form-side {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem;
            background: #090d16;
        }

        .form-box {
            width: 100%;
            max-width: 380px;
        }

        .form-header {
            margin-bottom: 2rem;
        }

        .form-title {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #f8fafc;
            margin-bottom: 0.35rem;
        }

        .form-subtitle {
            color: #64748b;
            font-size: 0.825rem;
        }

        .terminal-input {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            padding: 0.75rem 0.9rem;
            border-radius: 4px;
            color: #f8fafc;
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .terminal-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 1px #3b82f6;
        }

        .btn-submit {
            width: 100%;
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 0.8rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: background-color 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            background: #1d4ed8;
        }

        .quick-accounts {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #1e293b;
        }

        .quick-label {
            font-size: 0.725rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-family: ui-monospace, monospace;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
        }

        .account-chips {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .account-chip {
            background: #111827;
            border: 1px solid #1f2937;
            padding: 0.6rem;
            border-radius: 4px;
            cursor: pointer;
            text-align: left;
            transition: border-color 0.15s, background-color 0.15s;
        }

        .account-chip:hover {
            border-color: #3b82f6;
            background: #1e293b;
        }

        .chip-role {
            font-size: 0.675rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #60a5fa;
            font-family: ui-monospace, monospace;
            display: block;
        }

        .chip-creds {
            font-size: 0.75rem;
            color: #94a3b8;
            font-family: ui-monospace, monospace;
        }

        @media (max-width: 900px) {
            .auth-hero {
                display: none;
            }
            .auth-form-side {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="auth-split">
    <!-- Left Industrial Hero -->
    <div class="auth-hero">
        <div class="hero-top">
            <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 1.25rem;">
                <img src="<?= APP_URL ?>/assets/img/logo.svg" alt="TMC Logo" width="44" height="44" style="border-radius: 8px;">
                <div class="hero-badge" style="margin-bottom: 0;">
                    <span style="width: 6px; height: 6px; background: #10b981; border-radius: 50%; display: inline-block;"></span>
                    <span>TMC Gateway v1.1 // Online</span>
                </div>
            </div>
            <h1 class="hero-title">Traffic & Roadway<br>Video Surveillance</h1>
            <p class="hero-desc">
                Konsol pemantauan video CCTV lalu lintas jalan raya dan perimeter fasilitas berbasis protokol RTSP real-time transmuxing.
            </p>
        </div>

        <div class="hero-telemetry">
            <div class="telemetry-item">
                <span class="telemetry-label">Streaming Core</span>
                <span class="telemetry-value">Node.js + FFmpeg 9.0</span>
            </div>
            <div class="telemetry-item">
                <span class="telemetry-label">Gateway Protocol</span>
                <span class="telemetry-value">WebSocket MJPEG/fMP4</span>
            </div>
            <div class="telemetry-item">
                <span class="telemetry-label">Target Latency</span>
                <span class="telemetry-value">&lt; 1000 ms</span>
            </div>
            <div class="telemetry-item">
                <span class="telemetry-label">System Time</span>
                <span class="telemetry-value" id="liveTime">--:--:-- WIB</span>
            </div>
        </div>

        <div class="hero-footer">
            <span>OPERATIONAL MONITORING CONSOLE</span>
            <span>RESTRICTED ACCESS</span>
        </div>
    </div>

    <!-- Right Login Form -->
    <div class="auth-form-side">
        <div class="form-box">
            <div class="form-header" style="text-align: center; margin-bottom: 1.5rem;">
                <img src="<?= APP_URL ?>/assets/img/logo.svg" alt="TMC Logo" width="52" height="52" style="margin-bottom: 0.75rem; border-radius: 10px;">
                <h2 class="form-title">Autentikasi Operator</h2>
                <p class="form-subtitle">Masukkan kredensial akun untuk mengakses live grid.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error" style="border-radius: 4px; font-size: 0.8rem; margin-bottom: 1.25rem;">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form action="<?= APP_URL ?>/login" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" for="username" style="font-size: 0.775rem; color: #94a3b8;">USERNAME</label>
                    <input type="text" id="username" name="username" class="terminal-input" required autofocus autocomplete="username" placeholder="operator / admin">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" for="password" style="font-size: 0.775rem; color: #94a3b8;">PASSWORD</label>
                    <input type="password" id="password" name="password" class="terminal-input" required autocomplete="current-password" placeholder="••••••••">
                </div>

                <button type="submit" class="btn-submit">
                    <span>Masuk ke Konsol</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </form>

            <div class="quick-accounts">
                <div class="quick-label">
                    <span>Pilih Cepat (Quick Fill)</span>
                    <span style="color: #475569;">Klik untuk isi form</span>
                </div>
                <div class="account-chips">
                    <button type="button" class="account-chip" onclick="fillCreds('admin', 'admin123')">
                        <span class="chip-role">Administrator</span>
                        <span class="chip-creds">admin / admin123</span>
                    </button>
                    <button type="button" class="account-chip" onclick="fillCreds('observer', 'observer123')">
                        <span class="chip-role" style="color: #10b981;">Observer</span>
                        <span class="chip-creds">observer / observer123</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillCreds(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
    document.getElementById('username').focus();
}

function updateClock() {
    const el = document.getElementById('liveTime');
    if (!el) return;
    const now = new Date();
    const str = now.toLocaleTimeString('id-ID', { hour12: false }) + ' WIB';
    el.textContent = str;
}
setInterval(updateClock, 1000);
updateClock();
</script>

</body>
</html>
