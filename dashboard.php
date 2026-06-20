<?php
require_once 'includes/config.php';
require_once 'includes/auth_helper.php';
require_login();

$user    = current_user();
$user_id = $user['id'];
$is_admin = is_admin(); // true = admin, false = user biasa

//  DATA ADMIN
if ($is_admin) {
    $total_buku      = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM books"))[0] ?? 0;
    $total_kategori  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM categories"))[0] ?? 0;
    $total_rak       = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM racks"))[0] ?? 0;
    $total_pengguna  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users"))[0] ?? 0;
    $total_pinjam    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM borrowings WHERE status='dipinjam'"))[0] ?? 0;
    $total_kembali   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM borrowings WHERE status='dikembalikan'"))[0] ?? 0;
    $total_denda     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM fines WHERE status='belum_lunas'"))[0] ?? 0;
    $total_terlambat = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM borrowings WHERE status='dipinjam' AND tanggal_kembali < CURDATE()"))[0] ?? 0;

    // Chart 6 bulan terakhir
    $chart_res = mysqli_query($conn,
        "SELECT DATE_FORMAT(tanggal_pinjam,'%b') as bulan,
                MONTH(tanggal_pinjam) as bln_num,
                COUNT(*) as total
         FROM borrowings
         WHERE tanggal_pinjam >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
         GROUP BY MONTH(tanggal_pinjam), DATE_FORMAT(tanggal_pinjam,'%b')
         ORDER BY bln_num ASC");
    $chart_labels = [];
    $chart_data   = [];
    while ($row = mysqli_fetch_assoc($chart_res)) {
        $chart_labels[] = $row['bulan'];
        $chart_data[]   = (int)$row['total'];
    }
    if (empty($chart_labels)) {
        $chart_labels = ['Jan','Feb','Mar','Apr','Mei','Jun'];
        $chart_data   = [0,0,0,0,0,0];
    }
    $chart_labels_json = json_encode($chart_labels);
    $chart_data_json   = json_encode($chart_data);

    // Tabel peminjaman terbaru (semua user)
    $recent_res = mysqli_query($conn,
        "SELECT b.kode_pinjam, u.nama, b.tanggal_pinjam, b.tanggal_kembali, b.status
         FROM borrowings b
         JOIN users u ON b.user_id = u.id
         ORDER BY b.created_at DESC
         LIMIT 5");
}
//  DATA USER
if (!$is_admin) {
    $sedang_dipinjam = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM borrowings WHERE user_id=$user_id AND status='dipinjam'"))[0] ?? 0;
    $dikembalikan = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM borrowings WHERE user_id=$user_id AND status='dikembalikan'"))[0] ?? 0;
    $total_pinjam_user = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM borrowings WHERE user_id=$user_id"))[0] ?? 0;
    $denda_belum_lunas = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM fines f
         JOIN borrowings b ON f.borrowing_id = b.id
         WHERE b.user_id=$user_id AND f.status='belum_lunas'"))[0] ?? 0;

    // Peminjaman aktif milik user
    $pinjam_res = mysqli_query($conn,
        "SELECT bw.kode_pinjam, bk.judul, bw.tanggal_pinjam, bw.tanggal_kembali, bw.status
        FROM borrowings bw
        JOIN borrowing_details bd ON bd.borrowing_id = bw.id
        JOIN books bk ON bd.book_id = bk.id
        WHERE bw.user_id = $user_id
        ORDER BY bw.created_at DESC
        LIMIT 5");

    // Daftar buku tersedia
    $books_res = mysqli_query($conn,
        "SELECT id, judul, penulis, cover
         FROM books
         WHERE stok > 0
         ORDER BY created_at DESC
         LIMIT 6");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard LITERA</title>
<?php if ($is_admin): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<?php endif; ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap');
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root {
    --sidebar-bg : #C9D8E8;
    --sidebar-w  : 240px;
    --blue-dark  : #2563EB;
    --navy       : #1E3A5F;
    --white      : #ffffff;
    --bg         : #EDF2F7;
    --muted      : #64748B;
    --card-blue  : #D6E4F0;
}

body {
    font-family: 'Nunito', sans-serif;
    background: var(--bg);
    min-height: 100vh;
    display: flex;
}

/* ════ MAIN ════ */
.main {
    margin-left: var(--sidebar-w);
    flex: 1;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.page-header {
    padding: 20px 32px 18px;
    background: var(--white);
    border-bottom: 1px solid #E2E8F0;
}

.page-header .greeting { font-size: .85rem; color: var(--muted); font-weight: 500; }

.page-header h1 {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--navy);
    margin-top: 2px;
}

/* ── Role badge ── */
.role-badge {
    display: inline-block;
    margin-top: 6px;
    padding: 2px 12px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .5px;
    background: #DBEAFE;
    color: #1D4ED8;
}
.role-badge.admin { background: #FEF3C7; color: #92400E; }

.content { padding: 28px 32px; flex: 1; }

/* ── Welcome Banner (user only) ── */
.welcome-banner {
    background: #D0DCF0;
    border-radius: 16px;
    padding: 22px 28px;
    margin-bottom: 24px;
}
.welcome-banner h2 { font-size: 1.2rem; font-weight: 800; color: var(--navy); margin-bottom: 6px; }
.welcome-banner p  { font-size: .875rem; color: #4A6080; font-weight: 500; line-height: 1.5; }

/* ── Stats Grid ── */
.stats-grid {
    display: grid;
    gap: 16px;
    margin-bottom: 24px;
}
.stats-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
.stats-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }

.stat-card {
    background: var(--card-blue);
    border-radius: 14px;
    padding: 20px 22px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 1px solid rgba(37,99,235,.08);
    transition: transform .2s, box-shadow .2s;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37,99,235,.1);
}

/* user mode: stat tanpa icon */
.stat-card.simple { display: block; padding: 20px 22px; }
.stat-card.simple .num  { font-size: 2rem; font-weight: 800; color: var(--navy); line-height: 1; }
.stat-card.simple .label{ font-size: .8rem; color: var(--muted); font-weight: 600; margin-top: 6px; }

.stat-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    background: rgba(255,255,255,.6);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: var(--navy);
}
.stat-info .num   { font-size: 1.75rem; font-weight: 800; color: var(--navy); line-height: 1; }
.stat-info .label { font-size: .78rem; color: var(--muted); font-weight: 600; margin-top: 4px; }

.section-card {
    background: var(--white);
    border-radius: 16px;
    border: 1px solid #E2E8F0;
    overflow: hidden;
    margin-bottom: 24px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 24px 14px;
    border-bottom: 1px solid #F1F5F9;
    background: #FAFBFC;
}
.section-header h3 { font-size: 1rem; font-weight: 800; color: var(--navy); }

.chart-wrap { height: 220px; padding: 20px 24px; position: relative; }

.table-wrap { overflow-x: auto; }

table { width: 100%; border-collapse: collapse; }

thead th {
    padding: 11px 18px;
    text-align: left;
    font-size: .72rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .8px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}

tbody tr { border-bottom: 1px solid #F1F5F9; transition: background .15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #F8FBFF; }

tbody td {
    padding: 12px 18px;
    font-size: .85rem;
    color: #374151;
    font-weight: 500;
}

code {
    background: #EFF6FF;
    color: var(--blue-dark);
    padding: 2px 8px;
    border-radius: 6px;
    font-size: .78rem;
    font-family: 'Courier New', monospace;
    font-weight: 700;
}

.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
}
.badge-dipinjam    { background: #DBEAFE; color: #1D4ED8; }
.badge-dikembalikan{ background: #DCFCE7; color: #166534; }
.badge-terlambat   { background: #FEE2E2; color: #991B1B; }

.empty-state { text-align: center; padding: 40px 20px; color: #94A3B8; font-size: .875rem; font-weight: 500; }

/* ── Books Grid (user) ── */
.books-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    padding: 20px 24px;
}

.book-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}
.book-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(30,58,95,.1); }

.book-cover {
    width: 100%; height: 160px;
    object-fit: cover;
    background: #D6E4F0;
    display: block;
}

.book-cover-placeholder {
    width: 100%; height: 160px;
    background: linear-gradient(135deg, #C9D8E8 0%, #A8C3DB 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #7A9ABB;
}

.book-info { padding: 12px 14px; }
.book-title {
    font-size: .85rem; font-weight: 700; color: var(--navy); margin-bottom: 4px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.book-author { font-size: .75rem; color: var(--muted); font-weight: 500; }

.bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

@media (max-width: 1100px) {
    .bottom-grid { grid-template-columns: 1fr; }
}
@media (max-width: 900px) {
    .stats-grid.cols-3,
    .stats-grid.cols-4 { grid-template-columns: repeat(2, 1fr); }
    .books-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .sidebar { display: none; }
    .main { margin-left: 0; }
    .content { padding: 16px; }
    .stats-grid.cols-3,
    .stats-grid.cols-4 { grid-template-columns: 1fr; }
    .books-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>

<?php
$active_page = 'dashboard';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- MAIN CONTENT -->
<main class="main">

    <!-- Page Header -->
    <div class="page-header">
        <div class="greeting">Hallo</div>
        <h1>Welcome, <?= htmlspecialchars($user['nama']) ?>!</h1>
        <span class="role-badge <?= $is_admin ? 'admin' : '' ?>">
            <?= $is_admin ? 'Administrator' : 'Member' ?>
        </span>
    </div>

    <div class="content">

        <!-- TAMPILAN ADMIN-->
        <?php if ($is_admin): ?>
        <div class="stats-grid cols-3">

            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </div>
                <div class="stat-info">
                    <div class="num"><?= $total_buku ?></div>
                    <div class="label">Total Buku</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                </div>
                <div class="stat-info">
                    <div class="num"><?= $total_kategori ?></div>
                    <div class="label">Kategori</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
                <div class="stat-info">
                    <div class="num"><?= $total_pengguna ?></div>
                    <div class="label">Total Pengguna</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div class="stat-info">
                    <div class="num"><?= $total_pinjam ?></div>
                    <div class="label">Sedang Dipinjam</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                </div>
                <div class="stat-info">
                    <div class="num"><?= $total_kembali ?></div>
                    <div class="label">Dikembalikan</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div class="stat-info">
                    <div class="num"><?= $total_denda ?></div>
                    <div class="label">Denda Belum Lunas</div>
                </div>
            </div>

        </div>

        <div class="bottom-grid">

            <div class="section-card">
                <div class="section-header">
                    <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    <h3>Statistik Peminjaman (6 Bulan)</h3>
                </div>
                <div class="chart-wrap">
                    <canvas id="chartPeminjaman"></canvas>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <h3>Peminjaman Terbaru</h3>
                </div>
                <div class="table-wrap">
                    <?php if ($recent_res && mysqli_num_rows($recent_res) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Peminjam</th>
                                <th>Tgl Pinjam</th>
                                <th>Tgl Kembali</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = mysqli_fetch_assoc($recent_res)):
                                $terlambat = ($r['status'] === 'dipinjam' && $r['tanggal_kembali'] < date('Y-m-d'));
                                $badge = $terlambat ? 'badge-terlambat' : 'badge-' . $r['status'];
                                $label = $terlambat ? 'Terlambat' : ucfirst($r['status']);
                            ?>
                            <tr>
                                <td><code><?= htmlspecialchars($r['kode_pinjam']) ?></code></td>
                                <td><?= htmlspecialchars($r['nama']) ?></td>
                                <td><?= date('d M Y', strtotime($r['tanggal_pinjam'])) ?></td>
                                <td><?= date('d M Y', strtotime($r['tanggal_kembali'])) ?></td>
                                <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">Belum ada data peminjaman.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <?php endif; ?>

        <!--TAMPILAN USER BIASA -->
        <?php if (!$is_admin): ?>

        <div class="welcome-banner">
            <h2>Hallo, <?= htmlspecialchars($user['nama']) ?>!</h2>
            <p>Selamat datang di LITERA. Temukan buku favoritmu dan mulai membaca hari ini. Have a good day!</p>
        </div>

        <div class="stats-grid cols-4">
            <div class="stat-card simple">
                <div class="num"><?= $sedang_dipinjam ?></div>
                <div class="label">Sedang Dipinjam</div>
            </div>
            <div class="stat-card simple">
                <div class="num"><?= $dikembalikan ?></div>
                <div class="label">Dikembalikan</div>
            </div>
            <div class="stat-card simple">
                <div class="num"><?= $total_pinjam_user ?></div>
                <div class="label">Total Pinjam</div>
            </div>
            <div class="stat-card simple">
                <div class="num"><?= $denda_belum_lunas ?></div>
                <div class="label">Denda Belum Lunas</div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <h3>Peminjaman Saya</h3>
            </div>
            <div class="table-wrap">
                <?php if ($pinjam_res && mysqli_num_rows($pinjam_res) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Judul Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = mysqli_fetch_assoc($pinjam_res)):
                            $terlambat = ($r['status'] === 'dipinjam' && $r['tanggal_kembali'] < date('Y-m-d'));
                            $badge = $terlambat ? 'badge-terlambat' : 'badge-' . $r['status'];
                            $label = $terlambat ? 'Terlambat' : ucfirst($r['status']);
                        ?>
                        <tr>
                            <td><code><?= htmlspecialchars($r['kode_pinjam']) ?></code></td>
                            <td><?= htmlspecialchars($r['judul']) ?></td>
                            <td><?= date('d M Y', strtotime($r['tanggal_pinjam'])) ?></td>
                            <td><?= date('d M Y', strtotime($r['tanggal_kembali'])) ?></td>
                            <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">Belum ada peminjaman aktif.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                <h3>Buku Tersedia</h3>
            </div>
            <div class="books-grid">
                <?php if ($books_res && mysqli_num_rows($books_res) > 0):
                    while ($b = mysqli_fetch_assoc($books_res)): ?>
                <div class="book-card">
                    <?php if (!empty($b['cover'])): ?>
                        <img src="/LITERA-app/uploads/covers/<?= htmlspecialchars($b['cover']) ?>"
                             alt="<?= htmlspecialchars($b['judul']) ?>"
                             class="book-cover"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="book-cover-placeholder" style="display:none">
                            <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                    <?php else: ?>
                        <div class="book-cover-placeholder">
                            <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                    <?php endif; ?>
                    <div class="book-info">
                        <div class="book-title"><?= htmlspecialchars($b['judul']) ?></div>
                        <div class="book-author"><?= htmlspecialchars($b['penulis']) ?></div>
                    </div>
                </div>
                    <?php endwhile;
                else: ?>
                <div class="empty-state" style="grid-column:1/-1">Belum ada buku tersedia.</div>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>

    </div>
</main>

<?php if ($is_admin): ?>
<script>
const ctx = document.getElementById('chartPeminjaman').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $chart_labels_json ?>,
        datasets: [{
            label: 'Jumlah Peminjaman',
            data: <?= $chart_data_json ?>,
            backgroundColor: 'rgba(37,99,235,.18)',
            borderColor: '#2563EB',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1E3A5F',
                titleColor: '#fff',
                bodyColor: '#CBD5E1',
                padding: 10,
                cornerRadius: 8,
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#94A3B8', font: { family: 'Nunito', size: 11 } }
            },
            y: {
                grid: { color: '#F1F5F9' },
                ticks: { color: '#94A3B8', font: { family: 'Nunito', size: 11 }, stepSize: 1 },
                beginAtZero: true
            }
        }
    }
});
</script>
<?php endif; ?>
</body>
</html>