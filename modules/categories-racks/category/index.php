<?php
require_once '../../../includes/config.php';
require_once '../../../includes/auth_helper.php';
require_login();

$msg   = $_GET['msg']   ?? '';
$error = $_GET['error'] ?? '';

$search = trim($_GET['q'] ?? '');
$sq     = mysqli_real_escape_string($conn, $search);
$where  = $search ? "WHERE c.nama LIKE '%$sq%' OR c.kode LIKE '%$sq%'" : '';

$data = mysqli_query($conn,
    "SELECT c.*, COUNT(b.id) AS total_buku
     FROM categories c
     LEFT JOIN books b ON c.id = b.category_id
     $where
     GROUP BY c.id
     ORDER BY c.nama ASC"
);
$total = mysqli_num_rows($data);
?>

<!-- HTML -->
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kelola Kategori — Litera</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="/LITERA-app/assets/css/categories.css" />
  </head>
  <body>
    <!-- SIDE BAR - KELOLA KATEGORI -->
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
        <a
          href="books.php"
          class="nav-item <?= $cur==='books.php'?'active':'' ?>"
        >
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
            <path
              d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"
            />
          </svg>
          Kategori
        </a>
        <a
          href="/LITERA-app/modules/categories-racks/rack/index.php"
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
            <rect x="2" y="3" width="20" height="5" rx="1" />
            <rect x="2" y="10" width="20" height="5" rx="1" />
            <rect x="2" y="17" width="20" height="5" rx="1" />
          </svg>
          Rak Buku
        </a>

        <div class="nav-group-label">Transaksi</div>
        <a
          href="borrowings.php"
          class="nav-item <?= $cur==='borrowings.php'?'active':'' ?>"
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
              d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
            />
            <polyline points="14 2 14 8 20 8" />
            <line x1="16" y1="13" x2="8" y2="13" />
            <line x1="16" y1="17" x2="8" y2="17" />
          </svg>
          Peminjaman
        </a>
        <a
          href="returns.php"
          class="nav-item <?= $cur==='returns.php'?'active':'' ?>"
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
          href="fines.php"
          class="nav-item <?= $cur==='fines.php'?'active':'' ?>"
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
        <a href="../auth/logout.php" class="btn-logout">
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

<!-- MAIN FORM - KELOLA KATEGORI -->
    <main class="main">
      <div class="page-header">
        <h1><?= is_admin() ? 'Kelola Kategori' : 'Kategori Buku' ?></h1>
        <p>
          <?= is_admin() ? 'Tambah, edit, dan hapus kategori buku perpustakaan.'
          : 'Daftar kategori buku yang tersedia di perpustakaan.' ?>
        </p>
      </div>
      <div class="content">
        <?php if ($msg): ?>
        <div class="alert ok">
          <strong>Berhasil.</strong> <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?> <?php if ($error): ?>
        <div class="alert err">
          <strong>Terjadi Kesalahan.</strong> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?> <?php if (is_admin()): ?>
        <div class="form-card">
          <h2>+ Tambah Kategori Baru</h2>
          <form method="POST" action="create.php">
            <div class="grid2">
              <div class="fg">
                <label>Kode Kategori</label>
                <input
                  type="text"
                  name="kode"
                  placeholder="Contoh: FIK, SCI, HIS"
                  required
                />
              </div>
              <div class="fg">
                <label>Nama Kategori</label>
                <input
                  type="text"
                  name="nama"
                  placeholder="Contoh: Fiksi, Sains, Sejarah"
                  required
                />
              </div>
              <div class="fg full">
                <label>Deskripsi</label>
                <textarea
                  name="deskripsi"
                  placeholder="Deskripsi singkat kategori (opsional)"
                ></textarea>
              </div>
            </div>
            <div style="margin-top: 20px">
              <button type="submit" class="btn-primary">
                + Tambah Kategori
              </button>
            </div>
          </form>
        </div>
        <?php endif; ?>

        <div class="table-card">
          <div class="table-head">
            <h2>Daftar Kategori (<?= $total ?>)</h2>
            <form method="GET" class="search-form">
              <input
                type="text"
                name="q"
                placeholder="Cari kode / nama…"
                value="<?= htmlspecialchars($search) ?>"
              />
              <button type="submit">Cari</button>
            </form>
          </div>
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Kode</th>
                <th>Nama Kategori</th>
                <th>Deskripsi</th>
                <th>Total Buku</th>
                <?php if (is_admin()): ?>
                <th>Aksi</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if ($total === 0): ?>
              <tr>
                <td colspan="6" class="empty">Tidak ada kategori ditemukan.</td>
              </tr>
              <?php else: $no = 1; while ($row = mysqli_fetch_assoc($data)): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td>
                  <code
                    style="
                      background: #eff6ff;
                      color: var(--blue-dark);
                      padding: 2px 8px;
                      border-radius: 6px;
                      font-size: 0.78rem;
                      font-weight: 700;
                    "
                    ><?= htmlspecialchars($row['kode']) ?></code
                  >
                </td>
                <td style="font-weight: 600">
                  <?= htmlspecialchars($row['nama']) ?>
                </td>
                <td style="color: var(--muted)">
                  <?= htmlspecialchars($row['deskripsi'] ?: '-') ?>
                </td>
                <td>
                  <span class="badge-buku"
                    ><?= $row['total_buku'] ?>
                    buku</span
                  >
                </td>
                <?php if (is_admin()): ?>
                <td>
                  <div class="actions">
                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn-edit"
                      >Edit</a
                    >
                    <a
                      href="delete.php?id=<?= $row['id'] ?>"
                      class="btn-del"
                      onclick="return confirm('Hapus kategori &quot;<?= htmlspecialchars($row['nama']) ?>&quot;?')"
                    >
                      Hapus
                    </a>
                  </div>
                </td>
                <?php endif; ?>
              </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </body>
</html>
