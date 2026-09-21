<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $st = db()->prepare('SELECT * FROM users WHERE username = ? AND active = 1 LIMIT 1');
    $st->execute([$username]);
    $u = $st->fetch();
    
    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OPD Reception · Login</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary-dark: #06382d;
            --primary-green: #0d8a5b;
            --primary-green-hover: #086d46;
            --primary-green-glow: rgba(13, 138, 91, 0.14);
            --accent-orange: #ff5a1f;
            --mint-bg: #f4faf7;
            --card-border: rgba(255, 255, 255, 0.95);
            --font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        html, body {
            height: 100%;
            width: 100%;
            font-family: var(--font-family);
            color: #0f2b23;
            background: linear-gradient(135deg, #f4faf7 0%, #f4faf7 42%, #ebf7f1 78%, #dcf3e7 100%);
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Master Fullscreen Viewport */
        .login-viewport {
            position: relative;
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        /* Hospital Reception Photo Stage */
        .hospital-stage-layer {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 1;
        }

        .hospital-stage-img {
            position: absolute;
            top: 0;
            height: 100%;
            width: auto;
            aspect-ratio: 16 / 9;
            /* Perfectly anchors receptionist at 51vw so the wooden text is cleanly covered by the card */
            left: calc(51vw - 100.3vh);
            mask-image: linear-gradient(90deg, 
                transparent 0%, 
                transparent 28%, 
                rgba(0, 0, 0, 0.2) 34%, 
                rgba(0, 0, 0, 0.85) 42%, 
                black 48%, 
                black 100%
            );
            -webkit-mask-image: linear-gradient(90deg, 
                transparent 0%, 
                transparent 28%, 
                rgba(0, 0, 0, 0.2) 34%, 
                rgba(0, 0, 0, 0.85) 42%, 
                black 48%, 
                black 100%
            );
            object-fit: cover;
        }

        /* Top Header Bar */
        .top-bar {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: clamp(14px, 2.6vh, 26px) clamp(24px, 3.8vw, 56px) 0;
        }

        .brand-tagline {
            font-size: clamp(10px, 0.8vw, 11.5px);
            font-weight: 700;
            letter-spacing: 2.2px;
            color: var(--primary-dark);
            text-transform: uppercase;
            opacity: 0.88;
        }

        .trust-tagline {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: clamp(11px, 0.85vw, 12.5px);
            font-weight: 600;
            color: var(--primary-dark);
            opacity: 0.88;
        }

        .pulse-icon {
            width: 32px;
            height: 18px;
            stroke: var(--primary-green);
            stroke-width: 2.2;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Center Main Stage */
        .main-stage {
            position: relative;
            z-index: 10;
            width: 100%;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 clamp(24px, 3.8vw, 56px);
            min-height: 0;
        }

        /* Left Hero Column (~34% - 38%) */
        .hero-section {
            width: clamp(340px, 32vw, 440px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            z-index: 12;
        }

        .hero-welcome {
            font-size: clamp(15px, 1.22vw, 19px);
            color: #4a6878;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .hero-title {
            font-size: clamp(32px, 3.2vw, 48px);
            font-weight: 800;
            color: var(--primary-dark);
            line-height: 1.08;
            letter-spacing: -0.9px;
            margin-bottom: 5px;
        }

        .hero-subtitle {
            font-size: clamp(13.5px, 1.15vw, 17px);
            font-weight: 700;
            color: var(--primary-green);
            letter-spacing: -0.2px;
            margin-bottom: clamp(12px, 2.2vh, 22px);
        }

        /* 3 Feature Bullets */
        .feature-bullets {
            display: flex;
            flex-direction: column;
            gap: clamp(10px, 1.7vh, 18px);
            margin-bottom: clamp(10px, 1.8vh, 18px);
        }

        .feature-row {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .feature-icon-circle {
            width: clamp(36px, 2.8vw, 44px);
            height: clamp(36px, 2.8vw, 44px);
            border-radius: 50%;
            background: #d7f5e8;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 3px 10px rgba(13, 138, 91, 0.12);
        }

        .feature-icon-circle svg {
            width: clamp(18px, 1.35vw, 22px);
            height: clamp(18px, 1.35vw, 22px);
            stroke: var(--primary-green);
            stroke-width: 2.2;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .feature-icon-circle.heart-circle svg {
            fill: var(--primary-green);
            stroke: none;
        }

        .feature-text-block {
            display: flex;
            flex-direction: column;
            line-height: 1.25;
        }

        .feature-title-bold {
            font-size: clamp(13.5px, 1.05vw, 16px);
            font-weight: 800;
            color: var(--primary-dark);
        }

        .feature-subtext {
            font-size: clamp(11.5px, 0.88vw, 13.5px);
            color: #50677a;
            font-weight: 500;
        }

        /* Authentic Campaign Graphic: Care Begins Here */
        .care-begins-box {
            margin-top: 2px;
            display: inline-block;
        }

        .care-begins-img {
            width: clamp(130px, 9.8vw, 165px);
            height: auto;
            display: block;
            filter: drop-shadow(0 2px 6px rgba(13, 138, 91, 0.1));
        }

        /* Right Floating Login Panel */
        .login-panel {
            width: clamp(340px, 27vw, 410px);
            flex-shrink: 0;
            z-index: 15;
            margin-left: auto;
        }

        .login-card {
            background: #ffffff;
            border-radius: 6px;
            padding: clamp(18px, 2.6vh, 26px) clamp(18px, 1.8vw, 26px) clamp(14px, 2vh, 18px);
            box-shadow: 0 24px 60px -15px rgba(6, 44, 34, 0.2), 0 4px 16px -2px rgba(0, 0, 0, 0.04), 0 0 0 1px var(--card-border) inset;
            border: 1px solid rgba(220, 238, 230, 0.8);
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Card Header with Official Plus Code Logo */
        .card-brand-header {
            text-align: center;
            margin-bottom: clamp(4px, 0.8vh, 8px);
        }

        .card-logo-img {
            max-width: clamp(135px, 10.5vw, 160px);
            height: auto;
            display: inline-block;
            margin-bottom: 2px;
        }

        .brand-subtitle-line {
            font-size: clamp(7.5px, 0.65vw, 8.8px);
            font-weight: 700;
            letter-spacing: 1.4px;
            color: #6b857d;
            text-transform: uppercase;
            margin-top: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .brand-subtitle-line::before, .brand-subtitle-line::after {
            content: '';
            display: inline-block;
            width: clamp(12px, 1.2vw, 20px);
            height: 1px;
            background: #dbeae3;
        }

        /* Card Title Block */
        .card-title-block {
            text-align: center;
            margin-bottom: clamp(8px, 1.2vh, 12px);
        }

        .card-title-block h2 {
            font-size: clamp(18px, 1.45vw, 22px);
            font-weight: 800;
            color: var(--primary-dark);
            letter-spacing: -0.3px;
            line-height: 1.15;
        }

        .card-title-block p {
            font-size: clamp(11.5px, 0.92vw, 13px);
            color: #5d7570;
            font-weight: 500;
            margin-top: 2px;
        }

        /* Error Alert */
        .login-alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 7px 10px;
            border-radius: 4px;
            font-size: clamp(10.5px, 0.8vw, 12px);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Form Inputs */
        .form-group {
            margin-bottom: clamp(8px, 1.2vh, 11px);
            position: relative;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-left {
            position: absolute;
            left: 14px;
            width: clamp(16px, 1.15vw, 18px);
            height: clamp(16px, 1.15vw, 18px);
            stroke: #108a5b;
            stroke-width: 2.2;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            height: clamp(40px, 4.8vh, 45px);
            padding: 0 40px 0 42px;
            font-size: clamp(13px, 0.96vw, 14.5px);
            font-family: inherit;
            color: #0f2e27;
            background: #fbfdfc;
            border: 1.5px solid #d8e6df;
            border-radius: 4px;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-input::placeholder {
            color: #8fa29a;
            font-weight: 400;
        }

        .form-input:focus {
            background: #ffffff;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px var(--primary-green-glow);
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password svg {
            width: clamp(16px, 1.15vw, 18px);
            height: clamp(16px, 1.15vw, 18px);
            stroke: #8fa29a;
            stroke-width: 2;
            transition: stroke 0.2s;
        }

        .toggle-password:hover svg {
            stroke: var(--primary-green);
        }

        /* Submit Button */
        .btn-login {
            width: 100%;
            height: clamp(40px, 4.8vh, 45px);
            background: linear-gradient(180deg, #108e5e 0%, #0c7b50 100%);
            color: #ffffff;
            border: none;
            border-radius: 4px;
            font-size: clamp(13.5px, 1.02vw, 15px);
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(13, 138, 91, 0.22);
            margin-top: 4px;
        }

        .btn-arrow {
            transition: transform 0.2s ease;
            display: inline-block;
        }

        .btn-login:hover {
            background: linear-gradient(180deg, #0c7b50 0%, #086641 100%);
            box-shadow: 0 8px 24px rgba(13, 138, 91, 0.36);
            transform: translateY(-1px);
        }

        .btn-login:hover .btn-arrow {
            transform: translateX(3px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Remember me & Forgot password */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: clamp(6px, 0.9vh, 9px);
            font-size: clamp(11.5px, 0.82vw, 12.5px);
        }

        .remember-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #1a3b34;
            cursor: pointer;
            font-weight: 600;
        }

        .remember-wrap input[type="checkbox"] {
            width: clamp(14px, 0.95vw, 16px);
            height: clamp(14px, 0.95vw, 16px);
            accent-color: var(--primary-green);
            border-radius: 4px;
            cursor: pointer;
        }

        .forgot-link {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            text-decoration: underline;
            color: var(--primary-green-hover);
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            margin: clamp(6px, 0.9vh, 9px) 0;
            color: #8e9fae;
            font-size: clamp(10px, 0.75vw, 11px);
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2ece7;
        }

        .divider span {
            padding: 0 8px;
        }

        /* Powered By Container */
        .powered-box {
            background: linear-gradient(180deg, #f8fcf9 0%, #edf7f2 100%);
            border-radius: 4px;
            padding: clamp(7px, 0.9vh, 9px) clamp(8px, 0.8vw, 12px);
            text-align: center;
            border: 1px solid #dceee4;
        }

        .powered-box .pow-small {
            font-size: clamp(8.5px, 0.65vw, 9.5px);
            color: #648077;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .powered-box .pow-brand {
            font-size: clamp(13px, 1vw, 14.5px);
            font-weight: 800;
            color: #072820;
            letter-spacing: -0.2px;
        }

        .powered-box .pow-brand span {
            color: var(--accent-orange);
        }

        .powered-box .pow-tagline {
            font-size: clamp(8.5px, 0.65vw, 9.5px);
            color: #1b4338;
            font-weight: 600;
            margin-top: 1px;
        }

        /* Lower Section: Multi-layered Organic Waves & 4 Badges */
        .bottom-section {
            position: relative;
            z-index: 5;
            width: 100%;
        }

        .waves-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: clamp(120px, 18vh, 175px);
            pointer-events: none;
            overflow: hidden;
        }

        .wave-svg {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        /* 4 Badges Row */
        .badges-bar {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: clamp(14px, 2.5vw, 36px);
            padding: 0 clamp(24px, 3.8vw, 56px) clamp(10px, 1.6vh, 16px);
        }

        .badge-item {
            display: flex;
            align-items: center;
            gap: 9px;
            position: relative;
        }

        .badge-item:not(:last-child)::after {
            content: '';
            position: absolute;
            right: calc(-1 * clamp(7px, 1.25vw, 18px));
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 28px;
            background: #c5dfd4;
        }

        .badge-icon {
            width: clamp(20px, 1.5vw, 24px);
            height: clamp(20px, 1.5vw, 24px);
            stroke: var(--primary-green);
            stroke-width: 2.2;
            fill: none;
            flex-shrink: 0;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .badge-text-box {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .badge-line-bold {
            font-size: clamp(11.5px, 0.85vw, 13px);
            font-weight: 800;
            color: var(--primary-dark);
        }

        .badge-line-sub {
            font-size: clamp(10.5px, 0.75vw, 12px);
            font-weight: 500;
            color: #50677a;
        }

        /* Bottom Footer Bar */
        .bottom-footer {
            position: relative;
            z-index: 10;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: clamp(6px, 1vh, 9px) clamp(24px, 3.8vw, 56px) clamp(8px, 1.2vh, 13px);
            background: rgba(244, 250, 247, 0.92);
            backdrop-filter: blur(4px);
            border-top: 1px solid rgba(215, 235, 226, 0.75);
        }

        .footer-left-text {
            font-size: clamp(9px, 0.7vw, 10.5px);
            font-weight: 700;
            letter-spacing: 2px;
            color: #4a6878;
            text-transform: uppercase;
        }

        .footer-right-text {
            font-size: clamp(9px, 0.7vw, 10.5px);
            font-weight: 500;
            color: #68857d;
        }

        @media (max-width: 960px) {
            html, body {
                overflow-y: auto;
                height: auto;
            }
            .login-viewport {
                height: auto;
                min-height: 100vh;
            }
            .hospital-stage-img {
                display: none;
            }
            .main-stage {
                flex-direction: column;
                padding: 24px 16px;
                gap: 24px;
            }
            .hero-section {
                width: 100%;
                text-align: center;
                align-items: center;
            }
            .feature-bullets {
                align-items: flex-start;
            }
            .login-panel {
                width: 100%;
                max-width: 380px;
                margin-left: 0;
            }
            .badges-bar {
                flex-wrap: wrap;
                justify-content: center;
                gap: 16px;
            }
            .badge-item:not(:last-child)::after {
                display: none;
            }
            .bottom-footer {
                flex-direction: column;
                gap: 6px;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<div class="login-viewport">
    <!-- Center-Right Hospital Reception Photo Stage -->
    <div class="hospital-stage-layer">
        <img src="assets/raw-hospital-photo.jpg" class="hospital-stage-img" alt="Hospital Reception Staff">
    </div>

    <!-- Top Header -->
    <header class="top-bar">
        <div class="brand-tagline">PEOPLE &nbsp;|&nbsp; CARE &nbsp;|&nbsp; A HEALTHIER TOMORROW</div>
        <div class="trust-tagline">
            <span>&mdash; Trusted Technology for Better Healthcare</span>
            <svg class="pulse-icon" viewBox="0 0 32 18">
                <path d="M2 9h8l3-6 5 12 4-9 3 4h7"/>
            </svg>
        </div>
    </header>

    <!-- Center Main Stage -->
    <main class="main-stage">
        <!-- Left Hero Area -->
        <div class="hero-section">
            <p class="hero-welcome">Welcome to</p>
            <h1 class="hero-title">OPD Reception</h1>
            <p class="hero-subtitle">Smarter Registration. Smoother Care.</p>

            <!-- 3 Feature Bullets -->
            <div class="feature-bullets">
                <!-- 1. Quick -->
                <div class="feature-row">
                    <div class="feature-icon-circle">
                        <svg viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="feature-text-block">
                        <span class="feature-title-bold">Quick</span>
                        <span class="feature-subtext">Patient Registration</span>
                    </div>
                </div>

                <!-- 2. Efficient -->
                <div class="feature-row">
                    <div class="feature-icon-circle">
                        <svg viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </div>
                    <div class="feature-text-block">
                        <span class="feature-title-bold">Efficient</span>
                        <span class="feature-subtext">OPD Management</span>
                    </div>
                </div>

                <!-- 3. Better -->
                <div class="feature-row">
                    <div class="feature-icon-circle heart-circle">
                        <svg viewBox="0 0 24 24">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                    </div>
                    <div class="feature-text-block">
                        <span class="feature-title-bold">Better</span>
                        <span class="feature-subtext">Patient Experience</span>
                    </div>
                </div>
            </div>

            <!-- Campaign Graphic: Authentic Script -->
            <div class="care-begins-box">
                <img src="assets/care-begins-here-hd.png" alt="Care Begins Here" class="care-begins-img">
            </div>
        </div>

        <!-- Right Floating Login Card -->
        <div class="login-panel">
            <div class="login-card">
                <div>
                    <!-- Brand Header with Official Logo -->
                    <div class="card-brand-header">
                        <img src="assets/pluscode-official-logo.png" alt="PLUS CODE" class="card-logo-img">
                        <div class="brand-subtitle-line">Technology for a brighter healthcare</div>
                    </div>

                    <!-- Title & Subtitle -->
                    <div class="card-title-block">
                        <h2>OPD Reception</h2>
                        <p>Login to continue</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="login-alert">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <span><?= e($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="post" action="login.php" id="loginForm">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                        <div class="form-group">
                            <div class="input-box">
                                <svg class="input-icon-left" viewBox="0 0 24 24" fill="none">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <input type="text" name="username" id="username" class="form-input" placeholder="Username" required autofocus autocomplete="username">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-box">
                                <svg class="input-icon-left" viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                                <input type="password" name="password" id="password" class="form-input" placeholder="Password" required autocomplete="current-password">
                                <button type="button" class="toggle-password" id="togglePassword" title="Toggle password visibility">
                                    <svg id="eyeIcon" viewBox="0 0 24 24" fill="none">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-login">
                            <span>Login</span>
                            <span class="btn-arrow">&rarr;</span>
                        </button>

                        <div class="form-options">
                            <label class="remember-wrap">
                                <input type="checkbox" name="remember" id="remember">
                                <span>Remember me</span>
                            </label>
                            <a href="javascript:void(0)" class="forgot-link" onclick="alert('Please contact your hospital IT administrator to reset your login password.');">Forgot password?</a>
                        </div>
                    </form>

                    <div class="divider">
                        <span>or</span>
                    </div>
                </div>

                <!-- Card Bottom Banner -->
                <div class="powered-box">
                    <div class="pow-small">Powered by</div>
                    <div class="pow-brand">PLUS <span>CODE</span></div>
                    <div class="pow-tagline">Innovating for a Healthier Tomorrow</div>
                </div>
            </div>
        </div>
    </main>

    <!-- Lower Section: Multi-layered Organic Waves & 4 Badges -->
    <div class="bottom-section">
        <div class="waves-container">
            <svg class="wave-svg" viewBox="0 0 1440 175" preserveAspectRatio="none">
                <path d="M0,60 C380,120 720,20 1100,75 C1260,95 1370,60 1440,50 L1440,175 L0,175 Z" fill="rgba(202, 242, 224, 0.58)"/>
                <path d="M0,90 C320,40 680,130 1080,60 C1250,30 1380,75 1440,80 L1440,175 L0,175 Z" fill="rgba(164, 232, 198, 0.44)"/>
                <path d="M0,120 C420,80 780,145 1160,100 C1310,80 1395,105 1440,110 L1440,175 L0,175 Z" fill="rgba(132, 220, 178, 0.36)"/>
            </svg>
        </div>

        <!-- 4 Badges Row -->
        <div class="badges-bar">
            <!-- 1. Trusted Technology -->
            <div class="badge-item">
                <svg class="badge-icon" viewBox="0 0 24 24">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <polyline points="9 12 11 14 15 10"/>
                </svg>
                <div class="badge-text-box">
                    <span class="badge-line-bold">Trusted</span>
                    <span class="badge-line-sub">Technology</span>
                </div>
            </div>

            <!-- 2. Streamlined Workflows -->
            <div class="badge-item">
                <svg class="badge-icon" viewBox="0 0 24 24">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                    <path d="M3 20h18"/>
                </svg>
                <div class="badge-text-box">
                    <span class="badge-line-bold">Streamlined</span>
                    <span class="badge-line-sub">Workflows</span>
                </div>
            </div>

            <!-- 3. Support Better Care -->
            <div class="badge-item">
                <svg class="badge-icon" viewBox="0 0 24 24">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <polyline points="16 11 18 13 22 9"/>
                </svg>
                <div class="badge-text-box">
                    <span class="badge-line-bold">Support</span>
                    <span class="badge-line-sub">Better Care</span>
                </div>
            </div>

            <!-- 4. For a Healthier Tomorrow -->
            <div class="badge-item">
                <svg class="badge-icon" viewBox="0 0 24 24">
                    <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/>
                    <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
                </svg>
                <div class="badge-text-box">
                    <span class="badge-line-bold">For a</span>
                    <span class="badge-line-sub">Healthier Tomorrow</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Information Bar -->
    <footer class="bottom-footer">
        <div class="footer-left-text">HOSPITAL &nbsp;|&nbsp; OPD &nbsp;|&nbsp; PEOPLE &nbsp;|&nbsp; POSSIBILITIES</div>
        <div class="footer-right-text">&copy; 2024 Plus Code. All rights reserved.</div>
    </footer>
</div>

<script>
const toggleBtn = document.getElementById('togglePassword');
const passInput = document.getElementById('password');
const eyeIcon = document.getElementById('eyeIcon');

if (toggleBtn && passInput) {
    toggleBtn.addEventListener('click', () => {
        const isPass = passInput.type === 'password';
        passInput.type = isPass ? 'text' : 'password';
        eyeIcon.innerHTML = isPass 
            ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>'
            : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    });
}
</script>
</body>
</html>
