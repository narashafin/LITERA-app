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
        if (mysqli_num_rows($cek) > 0) {
            $error = 'Kode rak sudah dipakai rak lain.';
        } else {
            mysqli_query($conn,
                "UPDATE racks SET kode_rak='$kode_rak', nama='$nama', lokasi='$lokasi',
                 kapasitas=$kapasitas, deskripsi='$deskripsi' WHERE id=$id"
            );
            header("Location: index.php?msg=" . urlencode('Rak buku berhasil diperbarui.'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Rak Buku — LITERA</title>
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
.alert.err{padding:12px 18px;border-radius:10px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:8px;background:#FEF2F2;border:1px solid #FECACA;color:var(--red)}
.form-card{background:#fff;border-radius:16px;padding:28px 32px;border:1px solid #E2ECF8;box-shadow:0 2px 12px rgba(30,58,95,.06);max-width:760px}
.form-card h2{font-size:1.05rem;font-weight:800;color:var(--navy);margin-bottom:20px}
.grid4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:16px}
.fg{display:flex;flex-direction:column;gap:5px}
.fg.full4{grid-column:span 4}
label{font-size:.76rem;font-weight:700;color:var(--navy);text-transform:uppercase;letter-spacing:.4px}
input[type=text],input[type=number],textarea{padding:11px 14px;border:2px solid #C7D8F8;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.88rem;color:var(--navy);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;width:100%}
input:focus,textarea:focus{border-color:var(--blue-dark);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
textarea{resize:vertical;min-height:100px}
.form-actions{display:flex;gap:12px;margin-top:20px}
.btn-primary{padding:11px 28px;background:linear-gradient(135deg,var(--navy),var(--blue-dark));color:#fff;border:none;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer;transition:opacity .2s,transform .15s}
.btn-primary:hover{opacity:.9;transform:translateY(-1px)}
.btn-cancel{padding:11px 22px;background:#F1F5F9;color:var(--navy);border:1px solid #D1DCF8;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.88rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center}
</style>
</head>
<body>
<?php
$active_page = 'racks';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<main class="main">
    <div class="page-header">
        <h1>Edit Rak Buku</h1>
        <p>Perbarui data rak penyimpanan buku.</p>
    </div>
    <div class="content">
        <?php if ($error): ?><div class="alert err">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <div class="form-card">
            <h2>✏️ Edit: <?= htmlspecialchars($rack['nama']) ?></h2>
            <form method="POST">
                <div class="grid4">
                    <div class="fg">
                        <label>Kode Rak</label>
                        <input type="text" name="kode_rak" required value="<?= htmlspecialchars($_POST['kode_rak'] ?? $rack['kode_rak']) ?>">
                    </div>
                    <div class="fg">
                        <label>Nama Rak</label>
                        <input type="text" name="nama" required value="<?= htmlspecialchars($_POST['nama'] ?? $rack['nama']) ?>">
                    </div>
                    <div class="fg">
                        <label>Lokasi</label>
                        <input type="text" name="lokasi" value="<?= htmlspecialchars($_POST['lokasi'] ?? $rack['lokasi']) ?>">
                    </div>
                    <div class="fg">
                        <label>Kapasitas</label>
                        <input type="number" name="kapasitas" min="1" value="<?= htmlspecialchars($_POST['kapasitas'] ?? $rack['kapasitas']) ?>">
                    </div>
                    <div class="fg full4">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi"><?= htmlspecialchars($_POST['deskripsi'] ?? $rack['deskripsi']) ?></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">💾 Simpan Perubahan</button>
                    <a href="index.php" class="btn-cancel">✕ Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>
<script src="/LITERA-app/assets/sidebar-drag.js"></script>
</body>
</html>
