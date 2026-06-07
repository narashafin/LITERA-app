<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth_helper.php';
require_admin();

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }

$id   = (int)$_GET['id'];
$res  = mysqli_query($conn, "SELECT * FROM racks WHERE id=$id LIMIT 1");
$rack = mysqli_fetch_assoc($res);
if (!$rack) { header("Location: index.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_rak  = mysqli_real_escape_string($conn, trim($_POST['kode_rak']  ?? ''));
    $nama      = mysqli_real_escape_string($conn, trim($_POST['nama']      ?? ''));
    $lokasi    = mysqli_real_escape_string($conn, trim($_POST['lokasi']    ?? ''));
    $kapasitas = (int)($_POST['kapasitas'] ?? 50);
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));

    if (empty($kode_rak) || empty($nama)) {
        $error = 'Kode rak dan nama rak wajib diisi.';
    } else {
        $cek = mysqli_query($conn, "SELECT id FROM racks WHERE kode_rak='$kode_rak' AND id!=$id");
        if (mysqli_num_rows($cek) >
0) { $error = 'Kode rak sudah dipakai rak lain.'; } else { mysqli_query($conn,
"UPDATE racks SET kode_rak='$kode_rak', nama='$nama', lokasi='$lokasi',
kapasitas=$kapasitas, deskripsi='$deskripsi' WHERE id=$id" ); header("Location:
index.php?msg=" . urlencode('Rak buku berhasil diperbarui.')); exit; } } } ?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Rak Buku — LITERA</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="/LITERA-app/assets/css/rack-edit.css" />
  </head>
  <body>
    <aside class="sidebar">
      <div class="sidebar-logo">
        <img src="/LITERA-app/assets/LITERA.png" alt="LITERA" />
        <span class="logo-text">LITERA</span>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-group-label">Main</div>
        <a href="/LITERA-app/dashboard.php" class="nav-item">
          <svg
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
          </svg>
          Dashboard
        </a>
        <div class="nav-group-label">Koleksi</div>
        <a href="/LITERA-app/modules/books/index.php" class="nav-item">
          <svg
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path
              d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"
            />
          </svg>
          Buku
        </a>
        <a
          href="/LITERA-app/modules/categories-racks/category/index.php"
          class="nav-item"
        >
          <svg
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
            <path
              d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"
            />
          </svg>
          Kategori
        </a>
        <a
          href="/LITERA-app/modules/categories-racks/rack/index.php"
          class="nav-item active"
        >
          <svg
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
            <rect x="2" y="3" width="20" height="5" rx="1" />
            <rect x="2" y="10" width="20" height="5" rx="1" />
            <rect x="2" y="17" width="20" height="5" rx="1" />
          </svg>
          Rak Buku
        </a>
        <div class="nav-group-label">Transaksi</div>
        <a href="/LITERA-app/modules/borrowings/index.php" class="nav-item">
          <svg
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
            <path
              d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
            />
            <polyline points="14 2 14 8 20 8" />
            <line x1="16" y1="13" x2="8" y2="13" />
            <line x1="16" y1="17" x2="8" y2="17" />
          </svg>
          Peminjaman
        </a>
        <a
          href="/LITERA-app/modules/returns-fines/returns/index.php"
          class="nav-item"
        >
          <svg
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
            <polyline points="1 4 1 10 7 10" />
            <path d="M3.51 15a9 9 0 1 0 .49-3.5" />
          </svg>
          Pengembalian
        </a>
        <a
          href="/LITERA-app/modules/returns-fines/fines/index.php"
          class="nav-item"
        >
          <svg
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
          Denda
        </a>
         <?php if (is_admin()): ?>
        <div class="nav-group-label">Manajemen</div>
        <a href="/LITERA-app/pages/users.php" class="nav-item">
          <svg
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
          Pengguna
        </a>
        <a
          href="reports.php"
          class="nav-item <?= $cur==='reports.php'?'active':'' ?>"
        >
          <svg
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
            <line x1="18" y1="20" x2="18" y2="10" />
            <line x1="12" y1="20" x2="12" y2="4" />
            <line x1="6" y1="20" x2="6" y2="14" />
          </svg>
          Laporan
        </a>
        <?php endif; ?>
      </nav>
      <div class="sidebar-footer">
        <a href="/LITERA-app/modules/users-auth/logout.php" class="btn-logout">
          <svg
            width="15"
            height="15"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
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
        <h1>Edit Rak Buku</h1>
        <p>Perbarui data rak penyimpanan buku.</p>
      </div>
      <div class="content">
        <?php if ($error): ?>
        <div class="alert err">
          <strong>Terjadi Kesalahan.</strong> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <div class="form-card">
          <h2>Edit: <?= htmlspecialchars($rack['nama']) ?></h2>
          <form method="POST">
            <div class="grid4">
              <div class="fg">
                <label>Kode Rak</label>
                <input
                  type="text"
                  name="kode_rak"
                  required
                  value="<?= htmlspecialchars($_POST['kode_rak'] ?? $rack['kode_rak']) ?>"
                />
              </div>
              <div class="fg">
                <label>Nama Rak</label>
                <input
                  type="text"
                  name="nama"
                  required
                  value="<?= htmlspecialchars($_POST['nama'] ?? $rack['nama']) ?>"
                />
              </div>
              <div class="fg">
                <label>Lokasi</label>
                <input
                  type="text"
                  name="lokasi"
                  value="<?= htmlspecialchars($_POST['lokasi'] ?? $rack['lokasi']) ?>"
                />
              </div>
              <div class="fg">
                <label>Kapasitas</label>
                <input
                  type="number"
                  name="kapasitas"
                  min="1"
                  value="<?= htmlspecialchars($_POST['kapasitas'] ?? $rack['kapasitas']) ?>"
                />
              </div>
              <div class="fg full4">
                <label>Deskripsi</label>
                <textarea name="deskripsi">
<?= htmlspecialchars($_POST['deskripsi'] ?? $rack['deskripsi']) ?></textarea
                >
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-primary">
                Simpan Perubahan
              </button>
              <a href="index.php" class="btn-cancel">✕ Batal</a>
            </div>
          </form>
        </div>
      </div>
    </main>
    <script src="/LITERA-app/assets/sidebar-drag.js"></script>
  </body>
</html>
