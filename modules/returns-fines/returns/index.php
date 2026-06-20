<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth_helper.php';
require_login();

$msg   = $_GET['msg']   ?? '';
$error = $_GET['error'] ?? '';
$search = trim($_GET['q'] ?? '');
$sq     = mysqli_real_escape_string($conn, $search);
$user_id = (int)$_SESSION['user_id'];
$is_admin = is_admin();

// Build WHERE
if ($is_admin) {
    $where = $search
        ? "WHERE b.kode_pinjam LIKE '%$sq%' OR u.nama LIKE '%$sq%'"
        : "";
} else {
    $where = $search
        ? "WHERE b.user_id = $user_id AND (b.kode_pinjam LIKE '%$sq%')"
        : "WHERE b.user_id = $user_id";
}

$data = mysqli_query($conn, "
    SELECT r.id as return_id, r.tanggal_kembali as tgl_aktual_kembali,
           r.kondisi_buku, r.catatan,
           b.id as borrowing_id, b.kode_pinjam, b.tanggal_pinjam, b.tanggal_kembali as tgl_jatuh_tempo,
           u.nama as nama_user,
           COUNT(bd.book_id) as total_buku,
           GROUP_CONCAT(bk.judul SEPARATOR ', ') as buku_list,
           CASE WHEN r.tanggal_kembali > b.tanggal_kembali THEN DATEDIFF(r.tanggal_kembali, b.tanggal_kembali) ELSE 0 END as hari_terlambat,
           CASE WHEN f.id IS NOT NULL THEN f.total_denda ELSE 0 END as total_denda,
           CASE WHEN f.id IS NOT NULL THEN f.status ELSE 'lunas' END as status_denda
    FROM returns r
    JOIN borrowings b ON r.borrowing_id = b.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN borrowing_details bd ON b.id = bd.borrowing_id
    LEFT JOIN books bk ON bd.book_id = bk.id
    LEFT JOIN fines f ON f.borrowing_id = b.id
    $where
    GROUP BY r.id
    ORDER BY r.id DESC
");
$total = mysqli_num_rows($data);

// Borrowings yang masih dipinjam (untuk proses pengembalian)
$active_borrowings = mysqli_query($conn, "
    SELECT b.id, b.kode_pinjam, u.nama,
           GROUP_CONCAT(bk.judul SEPARATOR ', ') as buku_list,
           b.tanggal_kembali,
           CASE WHEN b.tanggal_kembali < CURDATE() THEN 1 ELSE 0 END as terlambat
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    LEFT JOIN borrowing_details bd ON b.id = bd.borrowing_id
    LEFT JOIN books bk ON bd.book_id = bk.id
    WHERE b.status = 'dipinjam'
    " . ($is_admin ? "" : "AND b.user_id = $user_id") . "
    GROUP BY b.id
    ORDER BY b.tanggal_kembali ASC
");
$total_active = mysqli_num_rows($active_borrowings);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengembalian Buku LITERA</title>
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

.alert {
    padding: 12px 18px;
    border-radius: 10px;
    font-size: .875rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ok {
    background: #F0FDF4;
    border: 1px solid #BBF7D0;
    color: #16A34A;
}

.err {
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: var(--red);
}

.top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 12px;
    flex-wrap: wrap;
}

.search-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-wrap input {
    padding: 9px 16px;
    border: 2px solid #C7D8F8;
    border-radius: 9px;
    font-family: 'Nunito', sans-serif;
    font-size: .875rem;
    color: var(--navy);
    outline: none;
    width: 260px;
    background: #fff;
}

.search-wrap input:focus {
    border-color: var(--blue-dark);
}

.search-wrap button {
    padding: 9px 18px;
    background: var(--blue-dark);
    color: #fff;
    border: none;
    border-radius: 9px;
    cursor: pointer;
    font-size: .875rem;
    font-weight: 700;
    font-family: 'Nunito', sans-serif;
}

.btn-proses {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 22px;
    background: linear-gradient(135deg, var(--navy), var(--blue-dark));
    color: #fff;
    text-decoration: none;
    border-radius: 10px;
    font-size: .875rem;
    font-weight: 700;
    font-family: 'Nunito', sans-serif;
    transition: opacity .2s;
    white-space: nowrap;
}

.btn-proses:hover {
    opacity: .9;
}

.tabs {
    display: flex;
    gap: 4px;
    background: #E2ECF8;
    border-radius: 12px;
    padding: 4px;
    margin-bottom: 20px;
    width: fit-content;
}

.tab {
    padding: 8px 20px;
    border-radius: 8px;
    font-size: .875rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: transparent;
    color: var(--muted);
    font-family: 'Nunito', sans-serif;
    transition: all .2s;
}

.tab.active {
    background: #fff;
    color: var(--blue-dark);
    box-shadow: 0 2px 8px rgba(37, 99, 235, .12);
}

.table-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #E2ECF8;
    box-shadow: 0 2px 12px rgba(30, 58, 95, .06);
    overflow: hidden;
}

.table-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #F1F5F9;
}

.table-head h2 {
    font-size: 1rem;
    font-weight: 800;
    color: var(--navy);
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    padding: 12px 18px;
    text-align: left;
    font-size: .72rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .5px;
    border-bottom: 2px solid #F1F5F9;
    background: #FAFBFF;
}

td {
    padding: 12px 18px;
    font-size: .86rem;
    color: var(--navy);
    border-bottom: 1px solid #F8FAFC;
    vertical-align: middle;
}

tr:last-child td {
    border-bottom: none;
}

tr:hover td {
    background: #FAFBFF;
}

.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
}

.badge-tepat {
    background: #DCFCE7;
    color: #166534;
}

.badge-terlambat {
    background: #FEE2E2;
    color: #991B1B;
}

.badge-lunas {
    background: #DCFCE7;
    color: #166534;
}

.badge-belum {
    background: #FEF3C7;
    color: #92400E;
}

.badge-dipinjam {
    background: #DBEAFE;
    color: #1D4ED8;
}

.empty-state {
    text-align: center;
    padding: 48px 20px;
    color: #94A3B8;
    font-size: .875rem;
    font-weight: 500;
}

.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .5);
    z-index: 200;
    align-items: center;
    justify-content: center;
}

.modal-overlay.show {
    display: flex;
}

.modal {
    background: #fff;
    border-radius: 20px;
    padding: 32px;
    width: 100%;
    max-width: 560px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
}

.modal h2 {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 4px;
}

.modal .subtitle {
    font-size: .85rem;
    color: var(--muted);
    margin-bottom: 24px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-size: .82rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 6px;
}

.form-group select,
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #C7D8F8;
    border-radius: 9px;
    font-family: 'Nunito', sans-serif;
    font-size: .875rem;
    color: var(--navy);
    outline: none;
    background: #fff;
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
    border-color: var(--blue-dark);
}

.denda-preview {
    background: #FEF3C7;
    border: 1px solid #FDE68A;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 18px;
    display: none;
}

.denda-preview.show {
    display: block;
}

.denda-preview .denda-label {
    font-size: .78rem;
    font-weight: 700;
    color: #92400E;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.denda-preview .denda-val {
    font-size: 1.3rem;
    font-weight: 800;
    color: #92400E;
    margin-top: 2px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 24px;
}

.btn-cancel {
    padding: 10px 22px;
    background: #F1F5F9;
    color: var(--muted);
    border: none;
    border-radius: 10px;
    font-family: 'Nunito', sans-serif;
    font-size: .875rem;
    font-weight: 700;
    cursor: pointer;
}

.btn-submit {
    padding: 10px 24px;
    background: linear-gradient(135deg, var(--navy), var(--blue-dark));
    color: #fff;
    border: none;
    border-radius: 10px;
    font-family: 'Nunito', sans-serif;
    font-size: .875rem;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .2s;
}

.btn-submit:hover {
    opacity: .9;
}

.radio-group {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.radio-group label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .85rem;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
}

@media (max-width: 640px) {
    .sidebar {
        display: none;
    }
    .main {
        margin-left: 0;
    }
    .content {
        padding: 16px;
    }
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
        <h1>Pengembalian Buku</h1>
        <p><?= $is_admin ? 'Kelola semua pengembalian buku dari pengguna perpustakaan.' : 'Daftar pengembalian buku Anda.' ?></p>
    </div>

    <div class="content">
        <?php if ($msg): ?><div class="alert ok">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert err">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Top bar: search + proses button (matching screenshot) -->
        <div class="top-bar">
            <form method="GET" class="search-wrap">
                <input type="text" name="q" placeholder="Cari kode pinjam / nama..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Cari</button>
            </form>
            <?php if ($is_admin): ?>
            <button class="btn-proses" onclick="document.getElementById('modalProses').classList.add('show')">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                Proses Pengembalian
            </button>
            <?php endif; ?>
        </div>

        <!-- Tabs: Menunggu Dikembalikan | Sudah Dikembalikan -->
        <div class="tabs">
            <button class="tab active" id="tabPending" onclick="switchTab('pending')">
                Menunggu Dikembalikan
                <?php if ($total_active > 0): ?>
                <span style="background:#FEE2E2;color:#991B1B;padding:1px 7px;border-radius:20px;font-size:.7rem;margin-left:6px"><?= $total_active ?></span>
                <?php endif; ?>
            </button>
            <button class="tab" id="tabDone" onclick="switchTab('done')">
                Sudah Dikembalikan
                <span style="background:#DCFCE7;color:#166534;padding:1px 7px;border-radius:20px;font-size:.7rem;margin-left:6px"><?= $total ?></span>
            </button>
        </div>

        <!-- TAB: Menunggu dikembalikan -->
        <div id="panePending" class="table-card">
            <div class="table-head">
                <h2>Daftar Peminjaman Aktif</h2>
                <span style="font-size:.78rem;color:var(--muted);font-weight:600"><?= $total_active ?> data</span>
            </div>
            <?php if ($total_active > 0): ?>
            <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Peminjam</th>
                        <th>Buku</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <?php if ($is_admin): ?><th>Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php while ($r = mysqli_fetch_assoc($active_borrowings)): ?>
                <tr>
                    <td><code style="background:#EFF6FF;color:#2563EB;padding:2px 8px;border-radius:6px;font-size:.78rem;font-weight:700"><?= htmlspecialchars($r['kode_pinjam']) ?></code></td>
                    <td style="font-weight:600"><?= htmlspecialchars($r['nama']) ?></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($r['buku_list']) ?></td>
                    <td><?= date('d M Y', strtotime($r['tanggal_kembali'])) ?></td>
                    <td>
                        <?php if ($r['terlambat']): ?>
                            <span class="badge badge-terlambat">Terlambat</span>
                        <?php else: ?>
                            <span class="badge badge-dipinjam">Aktif</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($is_admin): ?>
                    <td>
                        <button class="btn-proses" style="padding:5px 14px;font-size:.78rem"
                            onclick="openProses(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['nama'])) ?>', '<?= htmlspecialchars(addslashes($r['buku_list'])) ?>', '<?= $r['tanggal_kembali'] ?>')">
                            Proses
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="margin-bottom:12px;opacity:.4"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                <p>Tidak ada peminjaman aktif.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- TAB: Sudah dikembalikan -->
        <div id="paneDone" class="table-card" style="display:none">
            <div class="table-head">
                <h2>Riwayat Pengembalian</h2>
                <span style="font-size:.78rem;color:var(--muted);font-weight:600"><?= $total ?> data</span>
            </div>
            <?php if ($total > 0): ?>
            <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Peminjam</th>
                        <th>Buku</th>
                        <th>Jatuh Tempo</th>
                        <th>Tgl Kembali</th>
                        <th>Kondisi</th>
                        <th>Keterlambatan</th>
                        <th>Denda</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($r = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><code style="background:#EFF6FF;color:#2563EB;padding:2px 8px;border-radius:6px;font-size:.78rem;font-weight:700"><?= htmlspecialchars($r['kode_pinjam']) ?></code></td>
                    <td style="font-weight:600"><?= htmlspecialchars($r['nama_user']) ?></td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($r['buku_list']) ?></td>
                    <td><?= date('d M Y', strtotime($r['tgl_jatuh_tempo'])) ?></td>
                    <td><?= date('d M Y', strtotime($r['tgl_aktual_kembali'])) ?></td>
                    <td><span style="font-size:.82rem;color:var(--muted)"><?= htmlspecialchars($r['kondisi_buku'] ?? '—') ?></span></td>
                    <td>
                        <?php if ($r['hari_terlambat'] > 0): ?>
                            <span class="badge badge-terlambat"><?= $r['hari_terlambat'] ?> hari</span>
                        <?php else: ?>
                            <span class="badge badge-tepat">Tepat Waktu</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['total_denda'] > 0): ?>
                            <span style="font-weight:700;color:#DC2626">Rp <?= number_format($r['total_denda'], 0, ',', '.') ?></span>
                            <?php if ($r['status_denda'] === 'belum_lunas'): ?>
                                <span class="badge badge-belum" style="margin-left:4px">Belum Lunas</span>
                            <?php else: ?>
                                <span class="badge badge-lunas" style="margin-left:4px">Lunas</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#94A3B8">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="margin-bottom:12px;opacity:.4"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                <p>Belum ada riwayat pengembalian.</p>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /content -->
</main>

<!-- Modal Proses Pengembalian -->
<?php if ($is_admin): ?>
<div class="modal-overlay" id="modalProses">
    <div class="modal">
        <h2>Proses Pengembalian</h2>
        <p class="subtitle">Isi form berikut untuk mencatat pengembalian buku</p>

        <form method="POST" action="/LITERA-app/modules/returns-fines/returns/store.php">
            <div class="form-group">
                <label>Pilih Peminjaman</label>
                <select name="borrowing_id" id="selectBorrowing" required onchange="hitungDenda(this)">
                    <option value="">— Pilih Peminjam —</option>
                    <?php
                    // Reset active_borrowings pointer
                    $active2 = mysqli_query($conn, "
                        SELECT b.id, b.kode_pinjam, u.nama,
                               GROUP_CONCAT(bk.judul SEPARATOR ', ') as buku_list,
                               b.tanggal_kembali
                        FROM borrowings b
                        JOIN users u ON b.user_id = u.id
                        LEFT JOIN borrowing_details bd ON b.id = bd.borrowing_id
                        LEFT JOIN books bk ON bd.book_id = bk.id
                        WHERE b.status = 'dipinjam'
                        GROUP BY b.id
                        ORDER BY u.nama ASC
                    ");
                    while ($ab = mysqli_fetch_assoc($active2)): ?>
                    <option value="<?= $ab['id'] ?>" data-jatuh="<?= $ab['tanggal_kembali'] ?>">
                        <?= htmlspecialchars($ab['kode_pinjam'] . ' — ' . $ab['nama']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tanggal Pengembalian</label>
                <input type="date" name="tanggal_kembali" id="tglKembali" value="<?= date('Y-m-d') ?>" required onchange="hitungDenda()">
            </div>

            <div class="denda-preview" id="dendaPreview">
                <div class="denda-label">⚠ Keterlambatan Terdeteksi</div>
                <div class="denda-val" id="dendaVal">Rp 0</div>
                <div style="font-size:.78rem;color:#92400E;margin-top:4px" id="dendaDetail"></div>
            </div>

            <div class="form-group">
                <label>Kondisi Buku</label>
                <div class="radio-group">
                    <label><input type="radio" name="kondisi_buku" value="Baik Sekali" required> Baik Sekali</label>
                    <label><input type="radio" name="kondisi_buku" value="Baik"> Baik</label>
                    <label><input type="radio" name="kondisi_buku" value="Rusak Ringan"> Rusak Ringan</label>
                    <label><input type="radio" name="kondisi_buku" value="Rusak Berat"> Rusak Berat</label>
                </div>
            </div>

            <div class="form-group">
                <label>Catatan (opsional)</label>
                <textarea name="catatan" rows="3" placeholder="Catatan tambahan..."></textarea>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalProses').classList.remove('show')">Batal</button>
                <button type="submit" class="btn-submit">Simpan Pengembalian</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function switchTab(tab) {
    document.getElementById('panePending').style.display = tab === 'pending' ? 'block' : 'none';
    document.getElementById('paneDone').style.display    = tab === 'done'    ? 'block' : 'none';
    document.getElementById('tabPending').classList.toggle('active', tab === 'pending');
    document.getElementById('tabDone').classList.toggle('active',    tab === 'done');
}

function openProses(id, nama, buku, jatuhTempo) {
    const sel = document.getElementById('selectBorrowing');
    sel.value = id;
    hitungDenda(sel);
    document.getElementById('modalProses').classList.add('show');
}

function hitungDenda() {
    const sel  = document.getElementById('selectBorrowing');
    const tgl  = document.getElementById('tglKembali').value;
    const prev = document.getElementById('dendaPreview');
    if (!sel.value || !tgl) { prev.classList.remove('show'); return; }
    const opt  = sel.options[sel.selectedIndex];
    const jatuh = opt.getAttribute('data-jatuh');
    if (!jatuh) { prev.classList.remove('show'); return; }
    const due   = new Date(jatuh);
    const ret   = new Date(tgl);
    const diff  = Math.floor((ret - due) / 86400000);
    if (diff > 0) {
        const total = diff * 5000;
        document.getElementById('dendaVal').textContent = 'Rp ' + total.toLocaleString('id-ID');
        document.getElementById('dendaDetail').textContent = diff + ' hari × Rp 5.000/hari';
        prev.classList.add('show');
    } else {
        prev.classList.remove('show');
    }
}
</script>
</body>
</html>