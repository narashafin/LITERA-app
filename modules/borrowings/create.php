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

    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="/LITERA-app/assets/LITERA.png" alt="LITERA">
            <span class="logo-text">LITERA</span>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-group-label">Main</div>
            <a href="/LITERA-app/dashboard.php" class="nav-item">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1" />
                    <rect x="14" y="3" width="7" height="7" rx="1" />
                    <rect x="3" y="14" width="7" height="7" rx="1" />
                    <rect x="14" y="14" width="7" height="7" rx="1" />
                </svg>
                Dashboard
            </a>
            <div class="nav-group-label">Koleksi</div>
            <a href="/LITERA-app/modules/books/index.php" class="nav-item">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                </svg>
                Buku
            </a>
            <a href="/LITERA-app/modules/categories-racks/category/index.php" class="nav-item">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                </svg>
                Kategori
            </a>
            <a href="/LITERA-app/modules/categories-racks/rack/index.php" class="nav-item">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="2" y="3" width="20" height="5" rx="1" />
                    <rect x="2" y="10" width="20" height="5" rx="1" />
                    <rect x="2" y="17" width="20" height="5" rx="1" />
                </svg>
                Rak Buku
            </a>
            <div class="nav-group-label">Transaksi</div>
            <a href="/LITERA-app/modules/borrowings/index.php" class="nav-item active">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                </svg>
                Peminjaman
            </a>
            <a href="/LITERA-app/modules/returns-fines/returns/index.php" class="nav-item">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="1 4 1 10 7 10" />
                    <path d="M3.51 15a9 9 0 1 0 .49-3.5" />
                </svg>
                Pengembalian
            </a>
            <a href="/LITERA-app/modules/returns-fines/fines/index.php" class="nav-item">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                Denda
            </a>
            <?php if (is_admin()): ?>
            <div class="nav-group-label">Manajemen</div>
            <a href="/LITERA-app/pages/users.php" class="nav-item">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
                Pengguna
            </a>
            <a href="reports.php" class="nav-item <?= $cur==='reports.php'?'active':'' ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="18" y1="20" x2="18" y2="10" />
                    <line x1="12" y1="20" x2="12" y2="4" />
                    <line x1="6" y1="20" x2="6" y2="14" />
                </svg>
                Laporan
            </a>
            <?php endif; ?>
        <!-- Tambahkan link Pengembalian & Denda sesuai kebutuhan -->
        </nav>
        <div class="sidebar-footer">
            <a href="/LITERA-app/modules/users-auth/logout.php" class="btn-logout">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                Logout
            </a>
        </div>
    </aside>

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