<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth_helper.php';
require_admin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id  = (int)$_GET['id'];
$res = mysqli_query($conn, "
    SELECT f.*, b.kode_pinjam, u.nama as nama_user,
           GROUP_CONCAT(bk.judul SEPARATOR ', ') as buku_list
    FROM fines f
    JOIN borrowings b ON f.borrowing_id = b.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN borrowing_details bd ON b.id = bd.borrowing_id
    LEFT JOIN books bk ON bd.book_id = bk.id
    WHERE f.id = $id
    GROUP BY f.id
    LIMIT 1
");
$fine = mysqli_fetch_assoc($res);

if (!$fine) {
    header('Location: index.php?error=' . urlencode('Data denda tidak ditemukan.'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    if (!in_array($status, ['belum_lunas', 'lunas'])) {
        header('Location: index.php?error=' . urlencode('Status tidak valid.'));
        exit();
    }
    mysqli_query($conn, "UPDATE fines SET status='$status' WHERE id=$id");
    header('Location: index.php?msg=' . urlencode('Status denda berhasil diperbarui.'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Denda — LITERA</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.main {
  margin-left: var(--sidebar-w);
  flex: 1;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.page-header {
  padding: 20px 32px 18px;
  background: #fff;
  border-bottom: 1px solid #E2E8F0;
}

.page-header h1 {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--navy);
}

.page-header p {
  font-size: .85rem;
  color: var(--muted);
  margin-top: 3px;
}

.content {
  padding: 28px 32px;
  flex: 1;
}

/* ==========================================================================
   Form Card Component
   ========================================================================== */
.form-card {
  background: #fff;
  border-radius: 16px;
  padding: 28px 32px;
  border: 1px solid #E2ECF8;
  box-shadow: 0 2px 12px rgba(30, 58, 95, .06);
  max-width: 600px;
}

.form-card h2 {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--navy);
  margin-bottom: 20px;
}

/* Info Row Display */
.info-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #F1F5F9;
  font-size: .875rem;
}

.info-row:last-of-type {
  border-bottom: none;
}

.info-label {
  color: var(--muted);
  font-weight: 600;
}

.info-val {
  color: var(--navy);
  font-weight: 700;
  text-align: right;
}

.divider {
  height: 1px;
  background: #E2ECF8;
  margin: 20px 0;
}

/* Form Group (fg) */
.fg {
  display: flex;
  flex-direction: column;
  gap: 5px;
  margin-bottom: 16px;
}

.fg label {
  font-size: .76rem;
  font-weight: 700;
  color: var(--navy);
  text-transform: uppercase;
  letter-spacing: .4px;
}

.fg select {
  padding: 11px 14px;
  border: 2px solid #C7D8F8;
  border-radius: 10px;
  font-family: 'Nunito', sans-serif;
  font-size: .88rem;
  color: var(--navy);
  background: #fff;
  outline: none;
  transition: border-color .2s;
  width: 100%;
}

.fg select:focus {
  border-color: var(--blue-dark);
}

/* Form Actions (Buttons) */
.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
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
  transition: opacity .2s;
}

.btn-primary:hover {
  opacity: .9;
}

.btn-cancel {
  padding: 11px 22px;
  background: #F1F5F9;
  color: var(--navy);
  border: 1px solid #D1DCF8;
  border-radius: 10px;
  font-family: 'Nunito', sans-serif;
  font-size: .88rem;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}
</style>
</head>
<body>

<?php
$active_page = 'fines';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<main class="main">
    <div class="page-header">
        <h1>Edit Denda</h1>
        <p>Perbarui status pembayaran denda.</p>
    </div>
    <div class="content">
        <div class="form-card">
            <h2>Detail Denda</h2>

            <div class="info-row">
                <span class="info-label">Kode Pinjam</span>
                <span class="info-val"><?= htmlspecialchars($fine['kode_pinjam']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Peminjam</span>
                <span class="info-val"><?= htmlspecialchars($fine['nama_user']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Buku</span>
                <span class="info-val" style="max-width:280px"><?= htmlspecialchars($fine['buku_list']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Hari Terlambat</span>
                <span class="info-val" style="color:#DC2626"><?= $fine['hari_terlambat'] ?> hari</span>
            </div>
            <div class="info-row">
                <span class="info-label">Denda per Hari</span>
                <span class="info-val">Rp <?= number_format($fine['denda_per_hari'], 0, ',', '.') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Denda</span>
                <span class="info-val" style="color:#DC2626;font-size:1.1rem">Rp <?= number_format($fine['total_denda'], 0, ',', '.') ?></span>
            </div>

            <div class="divider"></div>

            <form method="POST">
                <div class="fg">
                    <label>Status Pembayaran</label>
                    <select name="status">
                        <option value="belum_lunas" <?= $fine['status'] === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                        <option value="lunas" <?= $fine['status'] === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                    <a href="index.php" class="btn-cancel">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>

</body>
</html>