<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth_helper.php';
require_admin();

$error = '';

// Ambil semua peminjaman aktif
$borrowings = mysqli_query($conn, "
    SELECT b.id, b.kode_pinjam, b.tanggal_kembali,
           u.nama,
           GROUP_CONCAT(bk.judul SEPARATOR ', ') as buku_list
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    LEFT JOIN borrowing_details bd ON b.id = bd.borrowing_id
    LEFT JOIN books bk ON bd.book_id = bk.id
    WHERE b.status = 'dipinjam'
    GROUP BY b.id
    ORDER BY u.nama ASC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../return_helper.php';

    $borrowing_id    = intval($_POST['borrowing_id'] ?? 0);
    $tanggal_kembali = trim($_POST['tanggal_kembali'] ?? '');
    $kondisi_buku    = trim($_POST['kondisi_buku'] ?? '');
    $catatan         = trim($_POST['catatan'] ?? '');

    if (!$borrowing_id || !$tanggal_kembali || !$kondisi_buku) {
        $error = 'Semua field wajib diisi.';
    } else {
        try {
            $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $helper = new ReturnHelper($db);
            $helper->validateBorrowingStatus($borrowing_id);
            $helper->checkReturnExists($borrowing_id);

            $borrowing = $helper->getBorrowingData($borrowing_id);
            if (!$borrowing) throw new Exception("Data peminjaman tidak ditemukan.");

            $details = $helper->getAllBorrowingDetails($borrowing_id);
            if (empty($details)) throw new Exception("Detail peminjaman tidak ditemukan.");

            $denda_info = $helper->hitungDenda($borrowing['tanggal_kembali'], $tanggal_kembali);

            $db->beginTransaction();
            $helper->saveReturnData($borrowing_id, $tanggal_kembali, $kondisi_buku, $catatan, (int)$_SESSION['user_id']);
            $helper->saveFineData($borrowing_id, $borrowing['user_id'], $denda_info);
            $helper->updateBorrowingStatus($borrowing_id);
            $helper->updateAllStok($details);
            $db->commit();

            header('Location: index.php?msg=' . urlencode('Pengembalian berhasil dicatat.'));
            exit();
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Proses Pengembalian — LITERA</title>
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
    --sidebar-bg: #C9D8E8;
    --sidebar-w: 240px;
    --blue-dark: #2563EB;
    --navy: #1E3A5F;
    --bg: #EDF2F7;
    --muted: #64748B;
    --red: #EF4444;
}

body {
    font-family: 'Nunito', sans-serif;
    background: var(--bg);
    min-height: 100vh;
    display: flex;
}

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

.alert-err {
    padding: 12px 18px;
    border-radius: 10px;
    font-size: .875rem;
    margin-bottom: 20px;
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: var(--red);
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-card {
    background: #fff;
    border-radius: 16px;
    padding: 28px 32px;
    border: 1px solid #E2ECF8;
    box-shadow: 0 2px 12px rgba(30, 58, 95, .06);
    max-width: 680px;
}

.form-card h2 {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 20px;
}

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

.fg select,
.fg input,
.fg textarea {
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

.fg select:focus,
.fg input:focus,
.fg textarea:focus {
    border-color: var(--blue-dark);
}

.fg textarea {
    resize: vertical;
    min-height: 90px;
}

.radio-group {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    padding: 4px 0;
}

.radio-group label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .88rem;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    text-transform: none;
    letter-spacing: 0;
}

.denda-preview {
    background: #FEF3C7;
    border: 1px solid #FDE68A;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 16px;
    display: none;
}

.denda-preview.show {
    display: block;
}

.denda-preview .lbl {
    font-size: .75rem;
    font-weight: 700;
    color: #92400E;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.denda-preview .val {
    font-size: 1.3rem;
    font-weight: 800;
    color: #92400E;
    margin-top: 2px;
}

.denda-preview .sub {
    font-size: .78rem;
    color: #92400E;
    margin-top: 2px;
}

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
$active_page = 'retuns';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<main class="main">
    <div class="page-header">
        <h1>Proses Pengembalian</h1>
        <p>Catat pengembalian buku dan hitung denda keterlambatan.</p>
    </div>
    <div class="content">
        <?php if ($error): ?>
        <div class="alert-err">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <h2>Form Pengembalian Buku</h2>
            <form method="POST">
                <div class="fg">
                    <label>Pilih Peminjaman</label>
                    <select name="borrowing_id" id="selBorrowing" required onchange="hitungDenda()">
                        <option value="">— Pilih Peminjam —</option>
                        <?php while ($b = mysqli_fetch_assoc($borrowings)): ?>
                        <option value="<?= $b['id'] ?>" data-jatuh="<?= $b['tanggal_kembali'] ?>"
                            <?= (isset($_POST['borrowing_id']) && $_POST['borrowing_id'] == $b['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['kode_pinjam'] . ' — ' . $b['nama'] . ' (' . $b['buku_list'] . ')') ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="fg">
                    <label>Tanggal Pengembalian</label>
                    <input type="date" name="tanggal_kembali" id="tglKembali"
                           value="<?= htmlspecialchars($_POST['tanggal_kembali'] ?? date('Y-m-d')) ?>"
                           required onchange="hitungDenda()">
                </div>

                <div class="denda-preview" id="dendaPreview">
                    <div class="lbl">⚠ Keterlambatan Terdeteksi</div>
                    <div class="val" id="dendaVal"></div>
                    <div class="sub" id="dendaSub"></div>
                </div>

                <div class="fg">
                    <label>Kondisi Buku</label>
                    <div class="radio-group">
                        <?php foreach (['Baik Sekali','Baik','Rusak Ringan','Rusak Berat'] as $k): ?>
                        <label>
                            <input type="radio" name="kondisi_buku" value="<?= $k ?>"
                                <?= (($_POST['kondisi_buku'] ?? '') === $k) ? 'checked' : '' ?> required>
                            <?= $k ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="fg">
                    <label>Catatan (opsional)</label>
                    <textarea name="catatan" placeholder="Catatan tambahan..."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Simpan Pengembalian</button>
                    <a href="index.php" class="btn-cancel">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
function hitungDenda() {
    const sel  = document.getElementById('selBorrowing');
    const tgl  = document.getElementById('tglKembali').value;
    const prev = document.getElementById('dendaPreview');
    if (!sel.value || !tgl) { prev.classList.remove('show'); return; }
    const jatuh = sel.options[sel.selectedIndex].getAttribute('data-jatuh');
    if (!jatuh) { prev.classList.remove('show'); return; }
    const due  = new Date(jatuh);
    const ret  = new Date(tgl);
    const diff = Math.floor((ret - due) / 86400000);
    if (diff > 0) {
        document.getElementById('dendaVal').textContent = 'Rp ' + (diff * 5000).toLocaleString('id-ID');
        document.getElementById('dendaSub').textContent = diff + ' hari × Rp 5.000/hari';
        prev.classList.add('show');
    } else {
        prev.classList.remove('show');
    }
}
// Trigger on load jika ada nilai dari POST
window.addEventListener('DOMContentLoaded', hitungDenda);
</script>
</body>
</html>