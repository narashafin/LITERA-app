<?php
require_once '../../../includes/config.php';
require_once '../../../includes/auth_helper.php';
require_login();

$msg   = $_GET['msg']   ?? '';
$error = $_GET['error'] ?? '';

$search = trim($_GET['q'] ?? '');
$sq     = mysqli_real_escape_string($conn, $search);
$where  = $search ? "WHERE r.nama LIKE '%$sq%' OR r.kode_rak LIKE '%$sq%' OR r.lokasi LIKE '%$sq%'" : '';

$data = mysqli_query($conn,
    "SELECT r.*, COUNT(b.id) AS total_buku
     FROM racks r
     LEFT JOIN books b ON r.id = b.rack_id
     $where
     GROUP BY r.id
     ORDER BY r.kode_rak ASC"
);
$total = mysqli_num_rows($data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Rak Buku Litera</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
  padding-top: 85px; /* sesuaikan tinggi header */
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
.alert {
  padding: 12px 18px;
  border-radius: 10px;
  font-size: 0.875rem;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.ok {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #16a34a;
}
.err {
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
  margin-bottom: 28px;
}
.form-card h2 {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--navy);
  margin-bottom: 20px;
}
.grid4 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr;
  gap: 16px;
}
.fg {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.fg.full4 {
  grid-column: span 4;
}
label {
  font-size: 0.76rem;
  font-weight: 700;
  color: var(--navy);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
input[type="text"],
input[type="number"],
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
  min-height: 80px;
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
.table-card {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e2ecf8;
  box-shadow: 0 2px 12px rgba(30, 58, 95, 0.06);
  overflow: hidden;
}
.table-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 28px;
  border-bottom: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 12px;
}
.table-head h2 {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--navy);
}
.search-form {
  display: flex;
  gap: 8px;
}
.search-form input {
  padding: 9px 14px;
  border: 2px solid #c7d8f8;
  border-radius: 9px;
  font-family: "Nunito", sans-serif;
  font-size: 0.85rem;
  color: var(--navy);
  outline: none;
  width: 220px;
}
.search-form input:focus {
  border-color: var(--blue-dark);
}
.search-form button {
  padding: 9px 18px;
  background: var(--blue-dark);
  color: #fff;
  border: none;
  border-radius: 9px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 700;
  font-family: "Nunito", sans-serif;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th {
  padding: 12px 18px;
  text-align: left;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #dbe4f0;
  background: #fafbff;
}
td {
  padding: 13px 18px;
  font-size: 0.86rem;
  color: var(--navy);
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
}
tr:last-child td {
  border-bottom: none;
}
tr:hover td {
  background: #fafbff;
}
.badge-lokasi {
  display: inline-block;
  padding: 3px 11px;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}
.actions {
  display: flex;
  gap: 7px;
}
.btn-edit {
  padding: 5px 14px;
  background: #eff6ff;
  color: var(--blue-dark);
  border: 1px solid #bfdbfe;
  border-radius: 7px;
  font-size: 0.78rem;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.2s;
}
.btn-edit:hover {
  background: #dbeafe;
}
.btn-del {
  padding: 5px 12px;
  background: #fef2f2;
  color: var(--red);
  border: 1px solid #fecaca;
  border-radius: 7px;
  font-size: 0.78rem;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.2s;
}
.btn-del:hover {
  background: #fee2e2;
}
.empty {
  text-align: center;
  padding: 40px;
  color: var(--muted);
  font-size: 0.9rem;
}
.rack-code {
  background: #eff6ff;
  color: var(--blue-dark);
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 700;
}
.rack-name {
  font-weight: 600;
}
.bar-count {
  font-size: 0.8rem;
  font-weight: 700;
  min-width: 20px;
}
.bar-percent {
  font-size: 0.75rem;
  color: var(--muted);
}
.bar-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}
.bar-bg {
  flex: 1;
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
  min-width: 80px;
}
.bar-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s;
}
</style>
</head>
<body>
<?php
$active_page = 'racks';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<main class="main">
    <div class="page-header">
        <h1><?= is_admin() ? 'Kelola Rak Buku' : 'Rak Buku' ?></h1>
        <p><?= is_admin() ? 'Tambah, edit, dan hapus data rak penyimpanan buku perpustakaan.' : 'Daftar rak buku yang tersedia di perpustakaan.' ?></p>
    </div>
    <div class="content">
        <?php if ($msg):  ?><div class="alert ok" >✅ <?= htmlspecialchars($msg)   ?></div><?php endif; ?>
        <?php if ($error):?><div class="alert err">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <?php if (is_admin()): ?>
        <div class="form-card">
            <h2>+ Tambah Rak Buku Baru</h2>
            <form method="POST" action="create.php">
                <div class="grid4">
                    <div class="fg">
                        <label>Kode Rak</label>
                        <input type="text" name="kode_rak" placeholder="Contoh: RAK-A1" required>
                    </div>
                    <div class="fg">
                        <label>Nama Rak</label>
                        <input type="text" name="nama" placeholder="Contoh: Rak Fiksi A" required>
                    </div>
                    <div class="fg">
                        <label>Lokasi</label>
                        <input type="text" name="lokasi" placeholder="Contoh: Lantai 1">
                    </div>
                    <div class="fg">
                        <label>Kapasitas</label>
                        <input type="number" name="kapasitas" value="50" min="1">
                    </div>
                    <div class="fg full4">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" placeholder="Deskripsi singkat rak (opsional)"></textarea>
                    </div>
                </div>
                <div style="margin-top:20px">
                    <button type="submit" class="btn-primary">+ Tambah Rak</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="table-card">
            <div class="table-head">
                <h2>Daftar Rak Buku (<?= $total ?>)</h2>
                <form method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Cari kode / nama / lokasi…" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Cari</button>
                </form>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Kode Rak</th>
                        <th>Nama Rak</th>
                        <th>Lokasi</th>
                        <th>Kapasitas</th>
                        <?php if (is_admin()): ?><th>Terisi</th><?php endif; ?>
                        <?php if (is_admin()): ?><th>Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total === 0): ?>
                        <tr><td colspan="<?= is_admin() ? 7 : 5 ?>" class="empty">Tidak ada rak ditemukan.</td></tr>
                    <?php else:
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($data)):
                            $pct = $row['kapasitas'] > 0 ? min(100, round($row['total_buku'] / $row['kapasitas'] * 100)) : 0;
                            $color = $pct >= 90 ? '#EF4444' : ($pct >= 70 ? '#F59E0B' : '#2563EB');
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><code style="background:#EFF6FF;color:var(--blue-dark);padding:2px 8px;border-radius:6px;font-size:.78rem;font-weight:700"><?= htmlspecialchars($row['kode_rak']) ?></code></td>
                            <td style="font-weight:600"><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= $row['lokasi'] ? '<span class="badge-lokasi">'.htmlspecialchars($row['lokasi']).'</span>' : '-' ?></td>
                            <td><?= $row['kapasitas'] ?> buku</td>
                            <?php if (is_admin()): ?>
                            <td>
                                <div class="bar-wrap">
                                    <span style="font-size:.8rem;font-weight:700;min-width:20px"><?= $row['total_buku'] ?></span>
                                    <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div></div>
                                    <span style="font-size:.75rem;color:var(--muted)"><?= $pct ?>%</span>
                                </div>
                            </td>
                            <?php endif; ?>
                            <?php if (is_admin()): ?>
                            <td>
                                <div class="actions">
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn-edit">Edit</a>
                                    <a href="delete.php?id=<?= $row['id'] ?>"
                                       class="btn-del"
                                       onclick="return confirm('Hapus rak &quot;<?= htmlspecialchars($row['nama']) ?>&quot;?')">
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
<script src="/LITERA-app/assets/sidebar-drag.js"></script>
</body>
</html>
