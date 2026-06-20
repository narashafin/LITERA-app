<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_admin();

if (!isset($_GET['id'])) { header('Location: index.php'); exit; }

$id   = (int)$_GET['id'];
$book = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM books WHERE id=$id LIMIT 1"));
if (!$book) { header('Location: index.php'); exit; }

$error = '';

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
    $bahasa    = trim($_POST['bahasa']       ?? 'Indonesia');
    $halaman   = (int)($_POST['halaman']     ?? 0);
    $sinopsis  = trim($_POST['sinopsis']     ?? '');

    if (empty($judul) || empty($penulis)) {
        $error = 'Judul dan penulis wajib diisi.';
    } else {
        $cover_clause = '';
        if (!empty($_FILES['cover']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed)) {
                $error = 'Format cover harus JPG, JPEG, PNG, atau WEBP.';
            } else {
                $dir = __DIR__ . '/../../uploads/covers/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = uniqid('book_') . '.' . $ext;
                if (move_uploaded_file($_FILES['cover']['tmp_name'], $dir . $fname)) {
                    $cover_clause = ", cover='" . mysqli_real_escape_string($conn, $fname) . "'";
                } else {
                    $error = 'Gagal upload cover.';
                }
            }
        }

        if (empty($error)) {
            $j  = mysqli_real_escape_string($conn, $judul);
            $pe = mysqli_real_escape_string($conn, $penulis);
            $pn = mysqli_real_escape_string($conn, $penerbit);
            $is = mysqli_real_escape_string($conn, $isbn);
            $de = mysqli_real_escape_string($conn, $deskripsi);
            $ba = mysqli_real_escape_string($conn, $bahasa);
            $si = mysqli_real_escape_string($conn, $sinopsis);

            mysqli_query($conn,
                "UPDATE books SET
                    category_id=$cat_id, rack_id=$rak_id, judul='$j', penulis='$pe',
                    penerbit='$pn', tahun_terbit=$tahun, isbn='$is', stok=$stok,
                    deskripsi='$de', bahasa='$ba', halaman=$halaman, sinopsis='$si'$cover_clause
                 WHERE id=$id"
            );
            header('Location: index.php?msg=' . urlencode('Buku berhasil diperbarui.'));
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
<title>Edit Buku — LITERA</title>
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

.cover-current {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 8px;
}

.cover-current img {
    height: 80px;
    width: 56px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid #E2ECF8;
}

.cover-current span {
    font-size: .78rem;
    color: var(--muted);
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
        <h1>Edit Buku</h1>
        <p>Perbarui data buku: <strong><?= htmlspecialchars($book['judul']) ?></strong></p>
    </div>
    <div class="content">
        <?php if ($error): ?>
        <div class="alert err">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <h2>✏️ Edit: <?= htmlspecialchars($book['judul']) ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="grid-form">

                    <div class="fg span2">
                        <label>Judul Buku *</label>
                        <input type="text" name="judul" required
                               value="<?= htmlspecialchars($_POST['judul'] ?? $book['judul'] ?? '') ?>">
                    </div>

                    <div class="fg">
                        <label>ISBN</label>
                        <input type="text" name="isbn"
                               value="<?= htmlspecialchars($_POST['isbn'] ?? $book['isbn'] ?? '') ?>"
                               placeholder="978-xxx-xxx">
                    </div>

                    <div class="fg">
                        <label>Penulis *</label>
                        <input type="text" name="penulis" required
                               value="<?= htmlspecialchars($_POST['penulis'] ?? $book['penulis'] ?? '') ?>">
                    </div>

                    <div class="fg">
                        <label>Penerbit</label>
                        <input type="text" name="penerbit"
                               value="<?= htmlspecialchars($_POST['penerbit'] ?? $book['penerbit'] ?? '') ?>">
                    </div>

                    <div class="fg">
                        <label>Tahun Terbit</label>
                        <input type="number" name="tahun" min="1900" max="<?= date('Y') ?>"
                               value="<?= htmlspecialchars($_POST['tahun'] ?? $book['tahun_terbit'] ?? '') ?>">
                    </div>

                    <div class="fg">
                        <label>Kategori</label>
                        <select name="category_id">
                            <option value="">— Pilih Kategori —</option>
                            <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($_POST['category_id'] ?? $book['category_id']) == $c['id'] ? 'selected' : '' ?>>
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
                            <option value="<?= $r['id'] ?>" <?= ($_POST['rak_id'] ?? $book['rack_id']) == $r['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['nama']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="fg">
                        <label>Stok</label>
                        <input type="number" name="stok" min="0"
                               value="<?= htmlspecialchars($_POST['stok'] ?? $book['stok'] ?? '0') ?>">
                    </div>

                    <div class="fg">
                        <label>Bahasa</label>
                        <select name="bahasa">
                            <option value="Indonesia" <?= ($_POST['bahasa'] ?? $book['bahasa'] ?? 'Indonesia') === 'Indonesia' ? 'selected' : '' ?>>Indonesia</option>
                            <option value="Inggris"   <?= ($_POST['bahasa'] ?? $book['bahasa'] ?? '') === 'Inggris' ? 'selected' : '' ?>>Inggris</option>
                        </select>
                    </div>

                    <div class="fg">
                        <label>Jumlah Halaman</label>
                        <input type="number" name="halaman" min="0"
                               value="<?= htmlspecialchars($_POST['halaman'] ?? $book['halaman'] ?? '0') ?>"
                               placeholder="Contoh: 320">
                    </div>

                    <div class="fg span3">
                        <label>Sinopsis</label>
                        <textarea name="sinopsis" placeholder="Sinopsis buku..."><?= htmlspecialchars($_POST['sinopsis'] ?? $book['sinopsis'] ?? '') ?></textarea>
                    </div>

                    <div class="fg span3">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" placeholder="Deskripsi singkat buku..."><?= htmlspecialchars($_POST['deskripsi'] ?? $book['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <div class="fg span2">
                        <label>Ganti Cover (opsional)</label>
                        <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp"
                               onchange="previewCover(this)">
                        <div class="cover-preview" id="coverPreview">
                            <img id="coverImg" src="" alt="Preview">
                        </div>
                    </div>

                    <?php if (!empty($book['cover'])): ?>
                    <div class="fg">
                        <label>Cover Saat Ini</label>
                        <div class="cover-current">
                            <img src="/LITERA-app/uploads/covers/<?= htmlspecialchars($book['cover']) ?>" alt=""
                                 onerror="this.style.display='none'">
                            <span>Kosongkan input file jika tidak ingin mengganti cover.</span>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
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