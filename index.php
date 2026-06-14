<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth_helper.php';

// Jika user sudah login sebelumnya, langsung arahkan ke dashboard
if (is_logged_in()) {
    header('Location: /LITERA-app/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang di LITERA</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Nunito', sans-serif; 
        }
        
        /* BASE MOBILE FIRST (Layar HP) */
        body {
            background: linear-gradient(135deg, #EBF3FC 0%, #C9D8E8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 16px;
        }

        .landing-card {
            background: #ffffff;
            padding: 28px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(30, 58, 95, 0.06);
            width: 100%;
            max-width: 340px;
            text-align: center;
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .logo-wrapper img { 
            height: 32px; 
            width: auto; 
        }
        
        .logo-text { 
            font-size: 1.4rem; 
            font-weight: 800; 
            color: #1E3A8A; 
            letter-spacing: -0.5px;
        }

        .welcome-title { 
            font-size: 1.15rem; 
            font-weight: 800; 
            color: #1E293B; 
            margin-bottom: 6px; 
            line-height: 1.3; 
        }
        
        .welcome-desc { 
            font-size: 0.82rem; 
            color: #64748B; 
            line-height: 1.5; 
            margin-bottom: 24px; 
        }

        .btn-group { 
            display: flex; 
            flex-direction: column; 
            gap: 10px; 
        }
        
        .btn {
            display: block;
            padding: 11px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            text-align: center;
        }

        .btn-login { 
            background-color: #2563EB; 
            color: #ffffff; 
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.12); 
        }
        .btn-login:hover { 
            background-color: #1D4ED8; 
        }
        
        .btn-register { 
            background-color: #F1F5F9; 
            color: #334155; 
            border: 1px solid #E2E8F0; 
        }
        .btn-register:hover { 
            background-color: #E2E8F0; 
        }

        .footer-text { 
            margin-top: 24px; 
            font-size: 0.7rem; 
            color: #94A3B8; 
        }

        /* DESKTOP BREAKPOINT */
        @media (min-width: 768px) {
            .landing-card { 
                max-width: 360px;
                padding: 36px 28px; 
            }
            .welcome-title { 
                font-size: 1.25rem; 
            }
        }
    </style>
</head>
<body>
    <div class="landing-card">
        <div class="logo-wrapper">
            <img src="/LITERA-app/assets/LITERA.png" alt="Logo" onerror="this.style.display='none'">
            <span class="logo-text">LITERA</span>
        </div>
        
        <h1 class="welcome-title">Selamat Datang</h1>
        <p class="welcome-desc">Silakan masuk atau daftar akun baru untuk mulai mengakses layanan perpustakaan digital.</p>
        
        <div class="btn-group">
            <a href="/LITERA-app/modules/users-auth/login.php" class="btn btn-login">Masuk ke Akun</a>
            <a href="/LITERA-app/modules/users-auth/register.php" class="btn btn-register">Daftar Anggota</a>
        </div>
        
        <div class="footer-text">&copy; <?= date('Y') ?> LITERA App. All rights reserved.</div>
    </div>
</body>
</html>