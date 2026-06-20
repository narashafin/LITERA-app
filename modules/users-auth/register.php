<?php
require_once '../../includes/config.php';
require_once '../../includes/auth_helper.php';

if (is_logged_in()) {
    header('Location: /LITERA-app/dashboard.php');
    exit();
}

$error_user = ''; $error_email = ''; $error_general = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $no_hp    = trim($_POST['no_hp'] ?? '');

    if (empty($nama) || empty($username) || empty($email) || empty($password)) {
        $error_general = 'Mohon isi semua bidang wajib (*).';
    } else {
        $u = mysqli_real_escape_string($conn, $username);
        $e = mysqli_real_escape_string($conn, $email);

        $cek_user = mysqli_query($conn, "SELECT id FROM users WHERE username = '$u' LIMIT 1");
        if (mysqli_num_rows($cek_user) > 0) { $error_user = 'Username ini sudah terdaftar.'; }

        $cek_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$e' LIMIT 1");
        if (mysqli_num_rows($cek_email) > 0) { $error_email = 'Email ini sudah terdaftar.'; }

        if (empty($error_user) && empty($error_email)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $n  = mysqli_real_escape_string($conn, $nama);
            $hp = mysqli_real_escape_string($conn, $no_hp);
            
            $sql = "INSERT INTO users (nama, username, email, password, no_hp, role_id, status, created_at) 
                    VALUES ('$n', '$u', '$e', '$hashed_password', '$hp', 2, 'aktif', NOW())";
            if (mysqli_query($conn, $sql)) {
                header('Location: login.php?registered=1');
                exit();
            } else { $error_general = 'Gagal mendaftar, terjadi gangguan sistem.'; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Anggota — Litera</title>
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
    padding: 20px 16px; /* Biar ada jarak manis di atas-bawah layar HP */
}

.auth-card {
    background: #ffffff;
    padding: 24px 20px; /* Diperkecil dari 30px */
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(30, 58, 95, 0.06);
    width: 100%;
    max-width: 360px; /* KUNCI: Di HP pun kita batasi lebarnya biar ga melar kesamping */
}

.auth-head { 
    text-align: center; 
    margin-bottom: 20px; 
}
.auth-head img { 
    height: 32px; /* Diperkecil biar ga menonjol banget */
    margin-bottom: 8px; 
}
.auth-head h2 { 
    font-size: 1.15rem; /* Lebih ramah di mata mobile */
    color: #1E3A5F; 
    font-weight: 800; 
}
.auth-head p { 
    font-size: 0.8rem; 
    color: #64748B; 
    margin-top: 2px; 
}

.alert-error { 
    background-color: #FEF2F2; 
    border: 1px solid #FEE2E2; 
    color: #DC2626; 
    padding: 10px; 
    border-radius: 6px; 
    font-size: 0.8rem; 
    margin-bottom: 12px; 
}

.fg { 
    margin-bottom: 12px; 
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

/* Pesan Error di Bawah Input */
.input-error-msg { 
    color: #DC2626; 
    font-size: 0.72rem; 
    font-weight: 600; 
    margin-top: 4px; 
    display: block; 
}
.input-has-error { 
    border-color: #EF4444 !important; 
    background-color: #FFF5F5; 
}

/* Tombol Submit Minimalis */
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
    margin-top: 6px;
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

/* DESKTOP BREAKPOINT (Jika dibuka di PC, sedikit dimelarkan tapi tetap proporsional) */
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
            <h2>Daftar Anggota Baru</h2>
            <p>Mulai perjalanan literasi Anda bersama Litera</p>
        </div>

        <?php if (!empty($error_general)): ?>
            <div class="alert-error"><?= $error_general ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="fg">
                <label>Nama Lengkap *</label>
                <input type="text" name="nama" placeholder="Nama lengkap Anda" value="<?= htmlspecialchars($nama ?? '') ?>" required>
            </div>
            <div class="fg">
                <label>Username *</label>
                <input type="text" name="username" placeholder="Buat username unik" 
                       class="<?= !empty($error_user) ? 'input-has-error' : '' ?>" 
                       value="<?= htmlspecialchars($username ?? '') ?>" required>
                <?php if (!empty($error_user)): ?>
                    <span class="input-error-msg">⚠️ <?= $error_user ?></span>
                <?php endif; ?>
            </div>
            <div class="fg">
                <label>Email *</label>
                <input type="email" name="email" placeholder="nama@email.com" 
                       class="<?= !empty($error_email) ? 'input-has-error' : '' ?>" 
                       value="<?= htmlspecialchars($email ?? '') ?>" required>
                <?php if (!empty($error_email)): ?>
                    <span class="input-error-msg">⚠️ <?= $error_email ?></span>
                <?php endif; ?>
            </div>
            <div class="fg">
                <label>Nomor HP (Opsional)</label>
                <input type="text" name="no_hp" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($no_hp ?? '') ?>">
            </div>
            <div class="fg">
                <label>Password *</label>
                <div class="pass-wrapper">
                    <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
                    <button type="button" class="eye-btn" onclick="togglePass()">
                        <svg id="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg id="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-submit">Daftar Sekarang</button>
        </form>
        <div class="auth-footer">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
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