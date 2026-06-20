<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth_helper.php';
require_admin();

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }

$id       = (int)$_GET['id'];
$res      = mysqli_query($conn, "SELECT * FROM categories WHERE id=$id LIMIT 1");
$category = mysqli_fetch_assoc($res);
if (!$category) { header("Location: index.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode      = mysqli_real_escape_string($conn, trim($_POST['kode']      ?? ''));
    $nama      = mysqli_real_escape_string($conn, trim($_POST['nama']      ?? ''));
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));

    if (empty($kode) || empty($nama)) {
        $error = 'Kode dan nama kategori wajib diisi.';
    } else {
        $cek = mysqli_query($conn, "SELECT id FROM categories WHERE kode='$kode' AND id!=$id");
        if (mysqli_num_rows($cek) >
0) { $error = 'Kode kategori sudah dipakai kategori lain.'; } else {
mysqli_query($conn, "UPDATE categories SET kode='$kode', nama='$nama',
deskripsi='$deskripsi' WHERE id=$id"); header("Location: index.php?msg=" .
urlencode('Kategori berhasil diperbarui.')); exit; } } } ?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Kategori — LITERA</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <style>
      *,
      *::before,
      *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }
      :root {
        --sidebar-bg: #c9d8e8;
        --sidebar-w: 240px;
        --blue-dark: #2563eb;
        --navy: #1e3a5f;
        --bg: #e8eef4;
        --muted: #64748b;
        --red: #ef4444;
      }
      body {
        margin: 0;
        font-family: "Nunito", sans-serif;
        background: var(--bg);
        min-height: 100vh;
        display: flex;
      }
      .main {
        margin-left: 240px;
        flex: 1;
        min-height: 100vh;
        padding-top: 85px;
      }
      .page-header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 100;

        background: #fff;
        padding: 20px 32px 18px calc(var(--sidebar-w) + 32px);
        box-shadow: 0 2px 10px rgba(30, 58, 95, 0.08);
      }
      .page-header h1 {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--navy);
      }
      .page-header p {
        font-size: 0.85rem;
        color: var(--muted);
        margin-top: 3px;
      }
      .content {
        padding: 28px 32px;
        flex: 1;
      }
      .alert.err {
        padding: 12px 18px;
        border-radius: 10px;
        font-size: 0.875rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: var(--red);
      }
      .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 28px 32px;
        border: 1px solid #e2ecf8;
        box-shadow: 0 2px 12px rgba(30, 58, 95, 0.06);
        max-width: 680px;
      }
      .form-card h2 {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--navy);
        margin-bottom: 20px;
      }
      .grid2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
      }
      .fg {
        display: flex;
        flex-direction: column;
        gap: 5px;
      }
      .fg.full {
        grid-column: span 2;
      }
      label {
        font-size: 0.76rem;
        font-weight: 700;
        color: var(--navy);
        text-transform: uppercase;
        letter-spacing: 0.4px;
      }
      input[type="text"],
      textarea {
        padding: 11px 14px;
        border: 2px solid #c7d8f8;
        border-radius: 10px;
        font-family: "Nunito", sans-serif;
        font-size: 0.88rem;
        color: var(--navy);
        background: #fff;
        outline: none;
        transition:
          border-color 0.2s,
          box-shadow 0.2s;
        width: 100%;
      }
      input:focus,
      textarea:focus {
        border-color: var(--blue-dark);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
      }
      textarea {
        resize: vertical;
        min-height: 100px;
      }
      .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 20px;
      }
      .btn-primary {
        padding: 11px 28px;
        background: var(--blue-dark);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-family: "Nunito", sans-serif;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        transition:
          opacity 0.2s,
          transform 0.15s;
      }
      .btn-primary:hover {
        opacity: 0.9;
        transform: translateY(-1px);
      }
      .btn-cancel {
        padding: 11px 22px;
        background: #f1f5f9;
        color: var(--navy);
        border: 1px solid #d1dcf8;
        border-radius: 10px;
        font-family: "Nunito", sans-serif;
        font-size: 0.88rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
      }
    </style>
  </head>

  <body>
    <?php $active_page = 'categories'; require_once __DIR__ .
    '/../../../includes/sidebar.php'; ?>

    <main class="main">
      <div class="page-header">
        <h1>Edit Kategori</h1>
        <p>Perbarui data kategori buku.</p>
      </div>
      <div class="content">
        <?php if ($error): ?>
        <div class="alert err">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <div class="form-card">
          <h2>Edit: <?= htmlspecialchars($category['nama']) ?></h2>
          <form method="POST">
            <div class="grid2">
              <div class="fg">
                <label>Kode Kategori</label>
                <input
                  type="text"
                  name="kode"
                  required
                  value="<?= htmlspecialchars($_POST['kode'] ?? $category['kode']) ?>"
                />
              </div>
              <div class="fg">
                <label>Nama Kategori</label>
                <input
                  type="text"
                  name="nama"
                  required
                  value="<?= htmlspecialchars($_POST['nama'] ?? $category['nama']) ?>"
                />
              </div>
              <div class="fg full">
                <label>Deskripsi</label>
                <textarea name="deskripsi">
<?= htmlspecialchars($_POST['deskripsi'] ?? $category['deskripsi']) ?></textarea
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
