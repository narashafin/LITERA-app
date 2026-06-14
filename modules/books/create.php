<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_admin();

$error = '';

// Ambil dropdown data
$cats_res  = mysqli_query($conn, "SELECT id, nama FROM categories ORDER BY nama ASC");
$racks_res = mysqli_query($conn, "SELECT id, nama FROM racks ORDER BY nama ASC");
$cats = $racks = [];
while ($r = mysqli_fetch_assoc($cats_res))  $cats[]  = $r;
while ($r = mysqli_fetch_assoc($racks_res)) $racks[] = $r;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul     = trim($_POST['judul']        ?? '');
    $penulis   = trim($_POST['penulis']      ?? '');
    $penerbit  = trim($_POST['penerbit']     ?? '');
    $tahun     = (int)($_POST['tahun']       ?? 0);
    $isbn      = trim($_POST['isbn']         ?? '');
    $stok      = (int)($_POST['stok']        ?? 0);
    $rak_id    = (int)($_POST['rak_id']      ?? 0) ?: 'NULL';
    $cat_id    = (int)($_POST['category_id'] ?? 0) ?: 'NULL';
    $deskripsi = trim($_POST['deskripsi']    ?? '');

    if (empty($judul) || empty($penulis)) {
        $error = 'Judul dan penulis wajib diisi.';
    } else {
        $cover = 'NULL';
        if (!empty($_FILES['cover']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed)) {
                $error = 'Format cover harus JPG, JPEG, PNG, atau WEBP.';
            } else {
                $dir = __DIR__ . '/../../uploads/covers/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = uniqid('book_') . '.' . $ext;
                if (!move_uploaded_file($_FILES['cover']['tmp_name'], $dir . $fname)) {
                    $error = 'Gagal upload cover.';
                } else {
                    $cover = "'" . mysqli_real_escape_string($conn, $fname) . "'";
                }
            }
        }

        if (empty($error)) {
            $j  = mysqli_real_escape_string($conn, $judul);
            $pe = mysqli_real_escape_string($conn, $penulis);
            $pn = mysqli_real_escape_string($conn, $penerbit);
            $is = mysqli_real_escape_string($conn, $isbn);
            $de = mysqli_real_escape_string($conn, $deskripsi);

            mysqli_query($conn,
                "INSERT INTO books (category_id, rack_id, judul, penulis, penerbit, tahun_terbit, isbn, stok, cover, deskripsi)
                 VALUES ($cat_id, $rak_id, '$j', '$pe', '$pn', $tahun, '$is', $stok, $cover, '$de')"
            );
            header('Location: index.php?msg=' . urlencode('Buku berhasil ditambahkan.'));
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
<title>Tambah Buku — LITERA</title>
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
.alert{padding:12px 18px;border-radius:10px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.err{background:#FEF2F2;border:1px solid #FECACA;color:var(--red)}
.form-card{background:#fff;border-radius:16px;padding:28px 32px;border:1px solid #E2ECF8;box-shadow:0 2px 12px rgba(30,58,95,.06)}
.form-card h2{font-size:1.05rem;font-weight:800;color:var(--navy);margin-bottom:20px}
.grid-form{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.span2{grid-column:span 2}
.span3{grid-column:span 3}
.fg{display:flex;flex-direction:column;gap:5px}
label{font-size:.76rem;font-weight:700;color:var(--navy);text-transform:uppercase;letter-spacing:.4px}
input[type=text],input[type=number],input[type=file],select,textarea{padding:11px 14px;border:2px solid #C7D8F8;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.88rem;color:var(--navy);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;width:100%}
input:focus,select:focus,textarea:focus{border-color:var(--blue-dark);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
textarea{resize:vertical;min-height:80px}
select{cursor:pointer}
.form-actions{display:flex;gap:10px;margin-top:24px}
.btn-primary{padding:11px 28px;background:linear-gradient(135deg,var(--navy),var(--blue-dark));color:#fff;border:none;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer;transition:opacity .2s,transform .15s}
.btn-primary:hover{opacity:.9;transform:translateY(-1px)}
.btn-cancel{padding:11px 22px;background:#F1F5F9;color:#64748B;border-radius:10px;text-decoration:none;font-weight:700;font-size:.88rem;display:inline-flex;align-items:center;transition:background .2s}
.btn-cancel:hover{background:#E2E8F0}
.cover-preview{display:none;margin-top:10px}
.cover-preview img{height:100px;border-radius:8px;object-fit:cover;border:2px solid #E2ECF8}
@media(max-width:900px){.grid-form{grid-template-columns:1fr 1fr}.span3{grid-column:span 2}}
@media(max-width:640px){.sidebar{display:none}.main{margin-left:0}.content{padding:16px}.grid-form{grid-template-columns:1fr}.span2,.span3{grid-column:span 1}}
</style>
</head>
<body>

<?php

$active_page = 'books';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main">
    <div class="page-header">
        <h1>Tambah Buku</h1>
        <p>Tambahkan data buku baru ke koleksi perpustakaan.</p>
    </div>
    <div class="content">
        <?php if ($error): ?>
        <div class="alert err">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <h2>+ Data Buku Baru</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="grid-form">
                    <div class="fg span2">
                        <label>Judul Buku *</label>
                        <input type="text" name="judul" required value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>" placeholder="Masukkan judul buku">
                    </div>
                    <div class="fg">
                        <label>ISBN</label>
                        <input type="text" name="isbn" value="<?= htmlspecialchars($_POST['isbn'] ?? '') ?>" placeholder="978-xxx-xxx">
                    </div>
                    <div class="fg">
                        <label>Penulis *</label>
                        <input type="text" name="penulis" required value="<?= htmlspecialchars($_POST['penulis'] ?? '') ?>" placeholder="Nama penulis">
                    </div>
                    <div class="fg">
                        <label>Penerbit</label>
                        <input type="text" name="penerbit" value="<?= htmlspecialchars($_POST['penerbit'] ?? '') ?>" placeholder="Nama penerbit">
                    </div>
                    <div class="fg">
                        <label>Tahun Terbit</label>
                        <input type="number" name="tahun" min="1900" max="<?= date('Y') ?>" value="<?= htmlspecialchars($_POST['tahun'] ?? '') ?>" placeholder="<?= date('Y') ?>">
                    </div>
                    <div class="fg">
                        <label>Kategori</label>
                        <select name="category_id">
                            <option value="">— Pilih Kategori —</option>
                            <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($_POST['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nama']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Rak Buku</label>
                        <select name="rak_id">
                            <option value="">— Pilih Rak —</option>
                            <?php foreach ($racks as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= ($_POST['rak_id'] ?? '') == $r['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['nama']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Stok</label>
                        <input type="number" name="stok" min="0" value="<?= htmlspecialchars($_POST['stok'] ?? '0') ?>">
                    </div>
                    <div class="fg span3">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" placeholder="Deskripsi singkat buku..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                    </div>
                    <div class="fg span3">
                        <label>Upload Cover</label>
                        <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp" onchange="previewCover(this)">
                        <div class="cover-preview" id="coverPreview">
                            <img id="coverImg" src="" alt="Preview">
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">+ Tambah Buku</button>
                    <a href="index.php" class="btn-cancel">✕ Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
function previewCover(input) {
    const preview = document.getElementById('coverPreview');
    const img     = document.getElementById('coverImg');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>