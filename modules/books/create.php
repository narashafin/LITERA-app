<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_admin();

$error  = '';
$errors = [];

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
    $rak_id    = (int)($_POST['rak_id']      ?? 0);
    $cat_id    = (int)($_POST['category_id'] ?? 0);
    $deskripsi = trim($_POST['deskripsi']    ?? '');
    $bahasa    = trim($_POST['bahasa']       ?? 'Indonesia');
    $halaman   = (int)($_POST['halaman']     ?? 0);
    $sinopsis  = trim($_POST['sinopsis']     ?? '');

    if (empty($judul))   $errors['judul']       = 'Judul buku wajib diisi.';
    if (empty($penulis)) $errors['penulis']      = 'Nama penulis wajib diisi.';
    if ($cat_id === 0)   $errors['category_id']  = 'Kategori wajib dipilih.';

    if (empty($errors)) {
        $cover = 'NULL';
        if (!empty($_FILES['cover']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed)) {
                $errors['cover'] = 'Format cover harus JPG, JPEG, PNG, atau WEBP.';
            } else {
                $dir = __DIR__ . '/../../uploads/covers/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = uniqid('book_') . '.' . $ext;
                if (!move_uploaded_file($_FILES['cover']['tmp_name'], $dir . $fname)) {
                    $errors['cover'] = 'Gagal upload cover.';
                } else {
                    $cover = "'" . mysqli_real_escape_string($conn, $fname) . "'";
                }
            }
        }

        if (empty($errors)) {
            $j       = mysqli_real_escape_string($conn, $judul);
            $pe      = mysqli_real_escape_string($conn, $penulis);
            $pn      = mysqli_real_escape_string($conn, $penerbit);
            $is      = mysqli_real_escape_string($conn, $isbn);
            $de      = mysqli_real_escape_string($conn, $deskripsi);
            $ba      = mysqli_real_escape_string($conn, $bahasa);
            $si      = mysqli_real_escape_string($conn, $sinopsis);
            $rak_val = $rak_id > 0 ? $rak_id : 'NULL';

            mysqli_query($conn,
                "INSERT INTO books (category_id, rack_id, judul, penulis, penerbit, tahun_terbit, isbn, stok, cover, deskripsi, bahasa, halaman, sinopsis)
                 VALUES ($cat_id, $rak_val, '$j', '$pe', '$pn', $tahun, '$is', $stok, $cover, '$de', '$ba', $halaman, '$si')"
            );
            header('Location: index.php?msg=' . urlencode('Buku berhasil ditambahkan.'));
            exit;
        }
    }
}

function fieldErr($field) {
    global $errors;
    return isset($errors[$field]) ? ' is-invalid' : '';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Buku LITERA</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/app.css">
<style>
.grid-form {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
}

.span2 {
    grid-column: span 2;
}

.span3 {
    grid-column: span 3;
}

select {
    cursor: pointer;
}

.cover-preview {
    display: none;
    margin-top: 10px;
}

.cover-preview img {
    height: 100px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid #E2ECF8;
}

input.is-invalid,
select.is-invalid,
textarea.is-invalid {
    border-color: var(--red) !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, .1) !important;
}

.field-error {
    font-size: .75rem;
    color: var(--red);
    margin-top: 3px;
    font-weight: 600;
}

@media (max-width: 900px) {
    .grid-form {
        grid-template-columns: 1fr 1fr;
    }
    .span3 {
        grid-column: span 2;
    }
}

@media (max-width: 640px) {
    .grid-form {
        grid-template-columns: 1fr;
    }
    .span2,
    .span3 {
        grid-column: span 1;
    }
}
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

        <?php if (!empty($errors)): ?>
        <div class="alert err">⚠ Mohon periksa kembali field yang belum diisi dengan benar.</div>
        <?php endif; ?>

        <div class="form-card">
            <h2>+ Data Buku Baru</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="grid-form">

                    <div class="fg span2">
                        <label>Judul Buku *</label>
                        <input type="text" name="judul" class="<?= fieldErr('judul') ?>"
                               value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>"
                               placeholder="Masukkan judul buku">
                        <?php if (isset($errors['judul'])): ?>
                        <span class="field-error">⚠ <?= $errors['judul'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="fg">
                        <label>ISBN</label>
                        <input type="text" name="isbn"
                               value="<?= htmlspecialchars($_POST['isbn'] ?? '') ?>"
                               placeholder="978-xxx-xxx">
                    </div>

                    <div class="fg">
                        <label>Penulis *</label>
                        <input type="text" name="penulis" class="<?= fieldErr('penulis') ?>"
                               value="<?= htmlspecialchars($_POST['penulis'] ?? '') ?>"
                               placeholder="Nama penulis">
                        <?php if (isset($errors['penulis'])): ?>
                        <span class="field-error">⚠ <?= $errors['penulis'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="fg">
                        <label>Penerbit</label>
                        <input type="text" name="penerbit"
                               value="<?= htmlspecialchars($_POST['penerbit'] ?? '') ?>"
                               placeholder="Nama penerbit">
                    </div>

                    <div class="fg">
                        <label>Tahun Terbit</label>
                        <input type="number" name="tahun" min="1900" max="<?= date('Y') ?>"
                               value="<?= htmlspecialchars($_POST['tahun'] ?? '') ?>"
                               placeholder="<?= date('Y') ?>">
                    </div>

                    <div class="fg">
                        <label>Kategori *</label>
                        <select name="category_id" class="<?= fieldErr('category_id') ?>">
                            <option value="">— Pilih Kategori —</option>
                            <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($_POST['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nama']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category_id'])): ?>
                        <span class="field-error">⚠ <?= $errors['category_id'] ?></span>
                        <?php endif; ?>
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
                        <input type="number" name="stok" min="0"
                               value="<?= htmlspecialchars($_POST['stok'] ?? '0') ?>">
                    </div>

                    <div class="fg">
                        <label>Bahasa</label>
                        <select name="bahasa">
                            <option value="Indonesia" <?= ($_POST['bahasa'] ?? 'Indonesia') === 'Indonesia' ? 'selected' : '' ?>>Indonesia</option>
                            <option value="Inggris"   <?= ($_POST['bahasa'] ?? '') === 'Inggris' ? 'selected' : '' ?>>Inggris</option>
                        </select>
                    </div>

                    <div class="fg">
                        <label>Jumlah Halaman</label>
                        <input type="number" name="halaman" min="0"
                               value="<?= htmlspecialchars($_POST['halaman'] ?? '0') ?>"
                               placeholder="Contoh: 320">
                    </div>

                    <div class="fg span3">
                        <label>Sinopsis</label>
                        <textarea name="sinopsis" placeholder="Sinopsis buku..."><?= htmlspecialchars($_POST['sinopsis'] ?? '') ?></textarea>
                    </div>

                    <div class="fg span3">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" placeholder="Deskripsi singkat buku..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <div class="fg span3">
                        <label>Upload Cover</label>
                        <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp"
                               class="<?= fieldErr('cover') ?>"
                               onchange="previewCover(this)"> <!-- gambr lngsung muncul sblm disimpan-->
                        <?php if (isset($errors['cover'])): ?>
                        <span class="field-error">⚠ <?= $errors['cover'] ?></span>
                        <?php endif; ?>
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
        const reader = new FileReader(); //baca file dr komputer user
        reader.onload = e => { img.src = e.target.result; preview.style.display = 'block'; }; //ngsi gmbr preview
        reader.readAsDataURL(input.files[0]); //nmpilin preview
    }
}
</script>
</body>
</html>