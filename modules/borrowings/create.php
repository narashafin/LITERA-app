<?php
require_once '../../includes/config.php';
require_once '../../includes/auth_helper.php';
require_login();

// Cek apakah admin
$isAdmin = ($_SESSION['role_id'] ?? 0) == 1;

// Ambil data untuk form
$users = mysqli_query($conn, "SELECT id, nama, username FROM users WHERE role_id = 2 AND status = 'aktif' ORDER BY nama ASC");
$books = mysqli_query($conn, "SELECT id, judul, stok_tersedia FROM books WHERE stok_tersedia > 0 ORDER BY judul ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Peminjaman — Litera</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0
    }

    :root {
        --sidebar-bg: #C9D8E8;
        --sidebar-w: 240px;
        --blue-dark: #2563EB;
        --navy: #1E3A5F;
        --bg: #EDF2F7;
        --muted: #64748B;
        --red: #EF4444
    }

    body {
        font-family: 'Nunito', sans-serif;
        background: var(--bg);
        min-height: 100vh;
        display: flex
    }

    /* === SEMUA CSS DARI FILE KAMU (sama persis) === */
    .sidebar {
        width: var(--sidebar-w);
        height: 100vh;
        background: var(--sidebar-bg);
        border-radius: 0 24px 24px 0;
        display: flex;
        flex-direction: column;
        padding-bottom: 24px;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 100;
        box-shadow: 2px 0 16px rgba(30, 58, 95, .08);
        overflow: hidden
    }

    .sidebar-logo {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 28px 16px 20px;
        border-bottom: 1px solid rgba(30, 58, 95, .12)
    }

    .sidebar-logo img {
        width: 90px;
        height: 90px;
        object-fit: contain
    }

    .sidebar-logo .logo-text {
        font-size: 1.25rem;
        font-weight: 800;
        letter-spacing: 4px;
        background: linear-gradient(90deg, #4ecdc4, #45b7d1, #96c93d, #f7971e, #f9d62e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-top: 4px
    }

    .sidebar-nav {
        flex: 1;
        padding: 16px 0;
        overflow-y: auto
    }

    .nav-group-label {
        font-size: .68rem;
        font-weight: 800;
        color: var(--navy);
        letter-spacing: 1.4px;
        text-transform: uppercase;
        padding: 14px 24px 6px
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 24px 9px 32px;
        color: #374151;
        text-decoration: none;
        font-size: .875rem;
        font-weight: 500;
        border-radius: 0 20px 20px 0;
        margin-right: 16px;
        transition: all .2s;
        position: relative
    }

    .nav-item:hover {
        background: rgba(37, 99, 235, .1);
        color: var(--blue-dark);
        font-weight: 600
    }

    .nav-item.active {
        background: #fff;
        color: var(--blue-dark);
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(37, 99, 235, .12)
    }

    .nav-item.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 6px;
        bottom: 6px;
        width: 3px;
        background: var(--blue-dark);
        border-radius: 0 3px 3px 0
    }

    .sidebar-footer {
        padding: 0 16px;
        margin-top: 8px
    }

    .btn-logout {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 10px 16px;
        background: rgba(239, 68, 68, .12);
        color: #DC2626;
        border: none;
        border-radius: 12px;
        font-family: 'Nunito', sans-serif;
        font-size: .85rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: background .2s
    }

    .main {
        margin-left: var(--sidebar-w);
        flex: 1;
        min-height: 100vh;
        display: flex;
        flex-direction: column
    }

    .page-header {
        padding: 20px 32px 18px;
        background: #fff;
        border-bottom: 1px solid #E2E8F0
    }

    .page-header h1 {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--navy)
    }

    .page-header p {
        font-size: .85rem;
        color: var(--muted);
        margin-top: 3px
    }

    .content {
        padding: 28px 32px;
        flex: 1
    }

    .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 28px 32px;
        border: 1px solid #E2ECF8;
        box-shadow: 0 2px 12px rgba(30, 58, 95, .06);
        margin-bottom: 28px
    }

    .form-card h2 {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--navy);
        margin-bottom: 20px
    }

    .grid2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px
    }

    .fg {
        display: flex;
        flex-direction: column;
        gap: 5px
    }

    .fg.full {
        grid-column: span 2
    }

    label {
        font-size: .76rem;
        font-weight: 700;
        color: var(--navy);
        text-transform: uppercase;
        letter-spacing: .4px
    }

    input[type=text], input[type=date], select, textarea {
        padding: 11px 14px;
        border: 2px solid #C7D8F8;
        border-radius: 10px;
        font-family: 'Nunito', sans-serif;
        font-size: .88rem;
        color: var(--navy);
        background: #fff;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        width: 100%
    }

    input:focus, select:focus, textarea:focus {
        border-color: var(--blue-dark);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .1)
    }

    textarea {
        resize: vertical;
        min-height: 80px
    }

    .btn-primary {
        padding: 11px 28px;
        background: linear-gradient(135deg, var(--navy), var(--blue-dark));
        color: #fff;
        border: none;
        border-radius: 10px;
        font-family: 'Nunito', sans-serif;
        font-size: .9rem;
        font-weight: 700;
        cursor: pointer;
        transition: opacity .2s, transform .15s
    }

    .btn-primary:hover {
        opacity: .9;
        transform: translateY(-1px)
    }

    .btn-secondary {
        padding: 11px 28px;
        background: #64748B;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-family: 'Nunito', sans-serif;
        font-size: .9rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    </style>
</head>

<body>

  <?php
$active_page = 'borrowings';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main">
    <div class="page-header">
        <h1>Tambah Peminjaman Baru</h1>
        <p>Isi data peminjaman buku perpustakaan</p>
    </div>

    <div class="content">
        <div class="form-card">
            <h2>Form Peminjaman</h2>
            <form method="POST" action="store.php">
                <div class="grid2">
                    <div class="fg">
                        <label>Anggota</label>
                        <select name="user_id" required>
                            <option value="">-- Pilih Anggota --</option>
                            <?php while($u = mysqli_fetch_assoc($users)): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?> (<?= $u['username'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="fg">
                        <label>Tanggal Pinjam</label>
                        <input type="date" name="tanggal_pinjam" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="fg full">
                        <label>Pilih Buku (tekan Ctrl/Cmd untuk multiple)</label>
                        <select name="book_ids[]" multiple size="10" required style="min-height: 200px;">
                            <?php while($b = mysqli_fetch_assoc($books)): ?>
                                <option value="<?= $b['id'] ?>">
                                    <?= htmlspecialchars($b['judul']) ?> (Stok: <?= $b['stok_tersedia'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="fg full">
                        <label>Catatan (Opsional)</label>
                        <textarea name="catatan" placeholder="Catatan tambahan jika ada..."></textarea>
                    </div>
                </div>

                <div style="margin-top: 25px;">
                    <button type="submit" class="btn-primary">Simpan Peminjaman</button>
                    <a href="index.php" class="btn-secondary" style="margin-left: 12px;">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script src="/LITERA-app/assets/sidebar-drag.js"></script>
</body>
</html>