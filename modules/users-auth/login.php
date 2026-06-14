<?php
session_start();
ob_start();
require_once '../../includes/config.php';
require_once '../../includes/auth_helper.php';

if (is_logged_in()) {
    header("Location: /LITERA-app/dashboard.php");
exit();
}

$error  = '';
$logout = isset($_GET['logout']);
$registered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = 'Username/email dan password wajib diisi.';
    } else {
        $i = mysqli_real_escape_string($conn, $identifier);
        $sql = "SELECT u.*, r.nama_role
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.username = '$i' OR u.email = '$i'
                LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            if ($user['status'] !== 'aktif') {
                $error = 'Akun Anda dinonaktifkan. Hubungi admin.';
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['nama']      = $user['nama'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role_id']   = $user['role_id'];
                $_SESSION['nama_role'] = $user['nama_role'];

                echo "<script>window.location.href='/LITERA-app/dashboard.php';</script>";
                exit();
            } else {
                $error = 'Password yang Anda masukkan salah.';
            }
        } else {
            $error = 'Username atau email tidak ditemukan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Litera</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Nunito', sans-serif; 
        }
        
        body {
            background: linear-gradient(135deg, #EBF3FC 0%, #C9D8E8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 16px;
        }

        .auth-card {
            background: #ffffff;
            padding: 24px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(30, 58, 95, 0.06);
            width: 100%;
            max-width: 360px;
        }

        .auth-head { 
            text-align: center; 
            margin-bottom: 20px; 
        }
        .auth-head img { 
            height: 32px; 
            margin-bottom: 8px; 
        }
        .auth-head h2 { 
            font-size: 1.15rem; 
            color: #1E3A5F; 
            font-weight: 800; 
        }
        .auth-head p { 
            font-size: 0.8rem; 
            color: #64748B; 
            margin-top: 2px; 
        }

        .alert {
            padding: 10px; 
            border-radius: 6px; 
            font-size: 0.8rem; 
            margin-bottom: 14px;
            font-weight: 600;
        }
        .alert-error { 
            background-color: #FEF2F2; 
            border: 1px solid #FEE2E2; 
            color: #DC2626; 
        }
        .alert-success {
            background-color: #ECFDF5;
            border: 1px solid #A7F3D0;
            color: #059669;
        }

        .fg { 
            margin-bottom: 14px; 
        }
        .fg label { 
            display: block; 
            font-size: 0.78rem; 
            font-weight: 700; 
            color: #475569; 
            margin-bottom: 4px; 
        }
        .fg input {
            width: 100%; 
            padding: 9px 12px;
            border: 1.5px solid #CBD5E1;
            border-radius: 6px; 
            font-size: 0.85rem; 
            color: #1E293B; 
            transition: all 0.2s ease;
        }
        .fg input:focus { 
            outline: none; 
            border-color: #2563EB; 
        }

        .pass-wrapper { 
            position: relative; 
        }
        .eye-btn {
            position: absolute; 
            right: 12px; 
            top: 50%; 
            transform: translateY(-50%);
            background: none; 
            border: none; 
            color: #64748B; 
            cursor: pointer; 
            display: flex; 
            align-items: center;
        }

        .btn-submit {
            width: 100%; 
            padding: 10px; 
            background-color: #2563EB; 
            color: white;
            border: none; 
            border-radius: 6px; 
            font-size: 0.9rem; 
            font-weight: 700;
            cursor: pointer; 
            transition: background 0.2s; 
            margin-top: 4px;
        }
        .btn-submit:hover { 
            background-color: #1D4ED8; 
        }

        .auth-footer { 
            text-align: center; 
            margin-top: 16px; 
            font-size: 0.8rem; 
            color: #64748B; 
        }
        .auth-footer a { 
            color: #2563EB; 
            text-decoration: none; 
            font-weight: 700; 
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }

        @media (min-width: 768px) {
            .auth-card { 
                max-width: 380px; 
                padding: 32px 28px; 
            }
            .auth-head h2 { 
                font-size: 1.3rem; 
            }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-head">
            <img src="/LITERA-app/assets/LITERA.png" alt="Logo" onerror="this.style.display='none'">
            <h2>Masuk ke Akun</h2>
            <p>Silakan masukkan kredensial Anda</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($logout): ?>
            <div class="alert alert-success">Anda telah berhasil keluar.</div>
        <?php endif; ?>

        <?php if ($registered): ?>
            <div class="alert alert-success">Pendaftaran berhasil! Silakan login.</div>
        <?php endif; ?>

        <form method="POST" action="/LITERA-app/modules/users-auth/login.php">
            <div class="fg">
                <label>Username atau Email</label>
                <input type="text" name="identifier" placeholder="Masukkan username/email" value="<?= htmlspecialchars($identifier ?? '') ?>" required>
            </div>
            <div class="fg">
                <label>Password</label>
                <div class="pass-wrapper">
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                    <button type="button" class="eye-btn" onclick="togglePass()">
                        <svg id="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg id="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-submit">Masuk</button>
        </form>
        
        <div class="auth-footer">Belum punya akun? <a href="register.php">Daftar sekarang</a></div>
    </div>

    <script>
        function togglePass() {
            const pad = document.getElementById('password');
            const open = document.getElementById('eye-open');
            const close = document.getElementById('eye-closed');
            if (pad.type === 'password') {
                pad.type = 'text'; open.style.display = 'none'; close.style.display = 'block';
            } else {
                pad.type = 'password'; open.style.display = 'block'; close.style.display = 'none';
            }
        }
    </script>
</body>
</html>