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
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--sidebar-bg:#C9D8E8;--sidebar-w:240px;--blue-dark:#2563EB;--navy:#1E3A5F;--bg:#EDF2F7;--muted:#64748B;--red:#EF4444}
body{font-family:'Nunito',sans-serif;background:var(--bg);min-height:100vh;display:flex}
.sidebar{width:var(--sidebar-w);height:100vh;background:var(--sidebar-bg);border-radius:0 24px 24px 0;display:flex;flex-direction:column;padding-bottom:24px;position:fixed;top:0;left:0;z-index:100;box-shadow:2px 0 16px rgba(30,58,95,.08);overflow:hidden}
.sidebar-logo{display:flex;flex-direction:column;align-items:center;padding:28px 16px 20px;border-bottom:1px solid rgba(30,58,95,.12)}
.sidebar-logo img{width:90px;height:90px;object-fit:contain}
.sidebar-logo .logo-text{font-size:1.25rem;font-weight:800;letter-spacing:4px;background:linear-gradient(90deg,#4ecdc4,#45b7d1,#96c93d,#f7971e,#f9d62e);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-top:4px}
.sidebar-nav{flex:1;padding:16px 0;overflow-y:auto}
.nav-group-label{font-size:.68rem;font-weight:800;color:var(--navy);letter-spacing:1.4px;text-transform:uppercase;padding:14px 24px 6px}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 24px 9px 32px;color:#374151;text-decoration:none;font-size:.875rem;font-weight:500;border-radius:0 20px 20px 0;margin-right:16px;transition:all .2s;position:relative}
.nav-item:hover{background:rgba(37,99,235,.1);color:var(--blue-dark);font-weight:600}
.nav-item.active{background:#fff;color:var(--blue-dark);font-weight:700;box-shadow:0 2px 8px rgba(37,99,235,.12)}
.nav-item.active::before{content:'';position:absolute;left:0;top:6px;bottom:6px;width:3px;background:var(--blue-dark);border-radius:0 3px 3px 0}
.sidebar-footer{padding:0 16px;margin-top:8px}
.btn-logout{display:flex;align-items:center;gap:8px;width:100%;padding:10px 16px;background:rgba(239,68,68,.12);color:#DC2626;border:none;border-radius:12px;font-family:'Nunito',sans-serif;font-size:.85rem;font-weight:700;cursor:pointer;text-decoration:none;transition:background .2s}
.btn-logout:hover{background:rgba(239,68,68,.22)}
.main{margin-left:var(--sidebar-w);flex:1;min-height:100vh;display:flex;flex-direction:column}
.page-header{padding:20px 32px 18px;background:#fff;border-bottom:1px solid #E2E8F0}
.page-header h1{font-size:1.35rem;font-weight:800;color:var(--navy)}
.page-header p{font-size:.85rem;color:var(--muted);margin-top:3px}
.content{padding:28px 32px;flex:1}
.form-card{background:#fff;border-radius:16px;padding:28px 32px;border:1px solid #E2ECF8;box-shadow:0 2px 12px rgba(30,58,95,.06);max-width:600px}
.form-card h2{font-size:1.05rem;font-weight:800;color:var(--navy);margin-bottom:20px}
.info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #F1F5F9;font-size:.875rem}
.info-row:last-of-type{border-bottom:none}
.info-label{color:var(--muted);font-weight:600}
.info-val{color:var(--navy);font-weight:700;text-align:right}
.divider{height:1px;background:#E2ECF8;margin:20px 0}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:16px}
.fg label{font-size:.76rem;font-weight:700;color:var(--navy);text-transform:uppercase;letter-spacing:.4px}
.fg select{padding:11px 14px;border:2px solid #C7D8F8;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.88rem;color:var(--navy);background:#fff;outline:none;transition:border-color .2s;width:100%}
.fg select:focus{border-color:var(--blue-dark)}
.form-actions{display:flex;gap:12px;margin-top:8px}
.btn-primary{padding:11px 28px;background:linear-gradient(135deg,var(--navy),var(--blue-dark));color:#fff;border:none;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer;transition:opacity .2s}
.btn-primary:hover{opacity:.9}
.btn-cancel{padding:11px 22px;background:#F1F5F9;color:var(--navy);border:1px solid #D1DCF8;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.88rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center}
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