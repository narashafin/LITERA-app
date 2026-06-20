<?php
require_once '../includes/config.php';
require_once '../includes/auth_helper.php';
require_admin();

// ─── Statistik umum ─────────────────────────────────────────
$total_buku      = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM books"))[0] ?? 0;
$total_pengguna  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role_id != 1"))[0] ?? 0;
$total_pinjam    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM borrowings"))[0] ?? 0;
$sedang_dipinjam = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM borrowings WHERE status='dipinjam'"))[0] ?? 0;
$dikembalikan    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM borrowings WHERE status='dikembalikan'"))[0] ?? 0;

$total_denda = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_denda),0) FROM fines"))[0] ?? 0;
$denda_lunas = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_denda),0) FROM fines WHERE status='lunas'"))[0] ?? 0;
$denda_belum = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_denda),0) FROM fines WHERE status='belum_lunas'"))[0] ?? 0;

// ─── Buku terpopuler (untuk tampilan) ────────────────────────
$buku_populer = mysqli_query($conn, "
    SELECT bk.judul, bk.penulis, COUNT(bd.id) AS total_pinjam
    FROM books bk
    LEFT JOIN borrowing_details bd ON bd.book_id = bk.id
    GROUP BY bk.id
    ORDER BY total_pinjam DESC
    LIMIT 5
");

// ─── Member aktif (untuk tampilan) ───────────────────────────
$member_aktif = mysqli_query($conn, "
    SELECT u.nama, u.username, COUNT(b.id) AS total_pinjam
    FROM users u
    LEFT JOIN borrowings b ON b.user_id = u.id
    WHERE u.role_id != 1
    GROUP BY u.id
    ORDER BY total_pinjam DESC
    LIMIT 5
");

//EXCELL
// Sheet: Daftar Buku 
$q_books = mysqli_query($conn, "
    SELECT
        bk.id,
        bk.judul,
        bk.penulis,
        bk.penerbit,
        bk.isbn,
        bk.tahun_terbit,
        c.nama  AS kategori,
        c.kode  AS kode_kategori,
        r.kode_rak,
        r.nama  AS nama_rak,
        r.lokasi,
        bk.stok,
        bk.stok_tersedia,
        bk.halaman,
        bk.bahasa,
        bk.status,
        bk.created_at
    FROM books bk
    LEFT JOIN categories c ON c.id = bk.category_id
    LEFT JOIN racks r      ON r.id = bk.rack_id
    ORDER BY bk.id ASC
");
$data_books = [];
while ($r = mysqli_fetch_assoc($q_books)) $data_books[] = $r;

// Sheet: Kategori
$q_categories = mysqli_query($conn, "
    SELECT
        c.id,
        c.kode,
        c.nama,
        c.deskripsi,
        COUNT(bk.id) AS jumlah_buku,
        c.created_at
    FROM categories c
    LEFT JOIN books bk ON bk.category_id = c.id
    GROUP BY c.id
    ORDER BY c.id ASC
");
$data_categories = [];
while ($r = mysqli_fetch_assoc($q_categories)) $data_categories[] = $r;

// Sheet: Rak
$q_racks = mysqli_query($conn, "
    SELECT
        r.id,
        r.kode_rak,
        r.nama,
        r.lokasi,
        r.kapasitas,
        COUNT(bk.id) AS jumlah_buku,
        r.deskripsi,
        r.created_at
    FROM racks r
    LEFT JOIN books bk ON bk.rack_id = r.id
    GROUP BY r.id
    ORDER BY r.id ASC
");
$data_racks = [];
while ($r = mysqli_fetch_assoc($q_racks)) $data_racks[] = $r;

// Sheet: Daftar Member
$q_members = mysqli_query($conn, "
    SELECT
        u.id,
        u.nama,
        u.username,
        u.email,
        u.no_hp,
        u.status,
        COUNT(b.id)                                          AS total_pinjam,
        SUM(CASE WHEN b.status='dipinjam'     THEN 1 ELSE 0 END) AS sedang_pinjam,
        SUM(CASE WHEN b.status='dikembalikan' THEN 1 ELSE 0 END) AS sudah_kembali,
        SUM(CASE WHEN b.status='terlambat'    THEN 1 ELSE 0 END) AS terlambat,
        u.created_at
    FROM users u
    LEFT JOIN borrowings b ON b.user_id = u.id
    WHERE u.role_id != 1
    GROUP BY u.id
    ORDER BY total_pinjam DESC
");
$data_members = [];
while ($r = mysqli_fetch_assoc($q_members)) $data_members[] = $r;

// Sheet: Riwayat Peminjaman (semua, lengkap)
$q_borrowings = mysqli_query($conn, "
    SELECT
        bw.kode_pinjam,
        u.nama          AS nama_peminjam,
        u.username,
        GROUP_CONCAT(bk.judul ORDER BY bk.judul SEPARATOR ' | ') AS buku_dipinjam,
        bw.tanggal_pinjam,
        bw.tanggal_kembali,
        bw.status,
        CASE
            WHEN bw.status != 'dikembalikan' AND CURDATE() > bw.tanggal_kembali
                 THEN DATEDIFF(CURDATE(), bw.tanggal_kembali)
            ELSE 0
        END             AS hari_terlambat,
        bw.catatan,
        bw.created_at
    FROM borrowings bw
    LEFT JOIN users u             ON u.id = bw.user_id
    LEFT JOIN borrowing_details bd ON bd.borrowing_id = bw.id
    LEFT JOIN books bk            ON bk.id = bd.book_id
    GROUP BY bw.id
    ORDER BY bw.tanggal_pinjam DESC
");
$data_borrowings = [];
while ($r = mysqli_fetch_assoc($q_borrowings)) $data_borrowings[] = $r;

// Sheet: Denda
$q_fines = mysqli_query($conn, "
    SELECT
        bw.kode_pinjam,
        u.nama              AS nama_peminjam,
        u.username,
        GROUP_CONCAT(bk.judul ORDER BY bk.judul SEPARATOR ' | ') AS buku,
        f.hari_terlambat,
        f.denda_per_hari,
        f.total_denda,
        f.status,
        f.tanggal_bayar,
        f.keterangan,
        f.created_at
    FROM fines f
    LEFT JOIN borrowings bw       ON bw.id = f.borrowing_id
    LEFT JOIN users u             ON u.id  = f.user_id
    LEFT JOIN borrowing_details bd ON bd.borrowing_id = bw.id
    LEFT JOIN books bk            ON bk.id = bd.book_id
    GROUP BY f.id
    ORDER BY f.created_at DESC
");
$data_fines = [];
while ($r = mysqli_fetch_assoc($q_fines)) $data_fines[] = $r;

// Sheet: Pengembalian
$q_returns = mysqli_query($conn, "
    SELECT
        bw.kode_pinjam,
        u.nama          AS nama_peminjam,
        GROUP_CONCAT(bk.judul ORDER BY bk.judul SEPARATOR ' | ') AS buku,
        bw.tanggal_pinjam,
        bw.tanggal_kembali  AS deadline_kembali,
        rt.tanggal_kembali  AS tanggal_aktual_kembali,
        rt.kondisi_buku,
        rt.catatan,
        rt.created_at
    FROM returns rt
    LEFT JOIN borrowings bw       ON bw.id = rt.borrowing_id
    LEFT JOIN users u             ON u.id  = bw.user_id
    LEFT JOIN borrowing_details bd ON bd.borrowing_id = bw.id
    LEFT JOIN books bk            ON bk.id = bd.book_id
    GROUP BY rt.id
    ORDER BY rt.tanggal_kembali DESC
");
$data_returns = [];
while ($r = mysqli_fetch_assoc($q_returns)) $data_returns[] = $r;

// Encode semua ke JSON untuk diteruskan ke JS
$json_books      = json_encode($data_books,      JSON_UNESCAPED_UNICODE);
$json_categories = json_encode($data_categories, JSON_UNESCAPED_UNICODE);
$json_racks      = json_encode($data_racks,      JSON_UNESCAPED_UNICODE);
$json_members    = json_encode($data_members,    JSON_UNESCAPED_UNICODE);
$json_borrowings = json_encode($data_borrowings, JSON_UNESCAPED_UNICODE);
$json_fines      = json_encode($data_fines,      JSON_UNESCAPED_UNICODE);
$json_returns    = json_encode($data_returns,    JSON_UNESCAPED_UNICODE);


$per_bulan = mysqli_query($conn, "
    SELECT DATE_FORMAT(tanggal_pinjam,'%b %Y') AS bln,
           MONTH(tanggal_pinjam) AS bln_num,
           YEAR(tanggal_pinjam) AS thn,
           COUNT(*) AS total
    FROM borrowings
    WHERE tanggal_pinjam >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(tanggal_pinjam), MONTH(tanggal_pinjam), DATE_FORMAT(tanggal_pinjam,'%b %Y')
    ORDER BY thn ASC, bln_num ASC
");
$chart_labels = [];
$chart_data   = [];
while ($r = mysqli_fetch_assoc($per_bulan)) {
    $chart_labels[] = $r['bln'];
    $chart_data[]   = (int)$r['total'];
}
if (empty($chart_labels)) {
    $chart_labels = ['Jan','Feb','Mar','Apr','Mei','Jun'];
    $chart_data   = [0,0,0,0,0,0];
}
$chart_labels_json = json_encode($chart_labels);
$chart_data_json   = json_encode($chart_data);


$active_page = 'reports';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan LITERA</title>
<link rel="stylesheet" href="/LITERA-app/assets/app.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<style>
/* Stats Grid */
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
.stat-card { background: #D6E4F0; border-radius: 14px; padding: 22px; border: 1px solid rgba(37,99,235,.08); transition: transform .2s, box-shadow .2s; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(37,99,235,.1); }
.stat-card .num { font-size: 1.8rem; font-weight: 800; color: #1E3A5F; line-height: 1; }
.stat-card .lbl { font-size: .78rem; color: #64748B; font-weight: 600; margin-top: 6px; }

.section-card { background: #fff; border-radius: 16px; border: 1px solid #E2E8F0; overflow: hidden; margin-bottom: 24px; }
.section-header { display: flex; align-items: center; gap: 10px; padding: 18px 24px 14px; border-bottom: 1px solid #F1F5F9; background: #FAFBFC; }
.section-header h3 { font-size: 1rem; font-weight: 800; color: #1E3A5F; }

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }

.chart-wrap { height: 220px; padding: 20px 24px; position: relative; }

.section-card table { width: 100%; border-collapse: collapse; }
.section-card thead th { padding: 11px 18px; text-align: left; font-size: .72rem; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: .8px; background: #F8FAFC; border-bottom: 1px solid #E2E8F0; }
.section-card tbody tr { border-bottom: 1px solid #F1F5F9; transition: background .15s; }
.section-card tbody tr:last-child { border-bottom: none; }
.section-card tbody tr:hover { background: #F8FBFF; }
.section-card tbody td { padding: 12px 18px; font-size: .85rem; color: #374151; font-weight: 500; }

.badge-green { background: #DCFCE7; color: #166534; }
.badge-red { background: #FEE2E2; color: #991B1B; }

.empty-state { text-align: center; padding: 32px 20px; color: #94A3B8; font-size: .875rem; }

.export-actions { display: flex; gap: 8px; margin-top: 12px; }
.btn-export { display: inline-flex; align-items: center; gap: 7px; padding: 8px 18px; border: none; border-radius: 9px; font-family: "Nunito", sans-serif; font-size: .82rem; font-weight: 700; cursor: pointer; transition: opacity .2s, transform .15s; text-decoration: none; }
.btn-export:hover { opacity: .88; transform: translateY(-1px); }
.btn-excel { background: linear-gradient(135deg,#1d6f42,#217346); color: #fff; }
.btn-print { background: linear-gradient(135deg,#1e3a5f,#2563eb); color: #fff; }

/* ── Responsive ── */
@media (max-width: 1100px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .stats-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 400px) {
    .stats-grid { grid-template-columns: 1fr; }
}

/* ── Print / PDF ── */
@media print {
    .sidebar, .hamburger-btn, .sidebar-overlay, .export-actions, .page-header p { display: none !important; }
    body { background: #fff !important; padding-top: 0 !important; font-family: "Nunito", Arial, sans-serif; }
    .main { margin-left: 0 !important; }
    .content { padding: 0 16px !important; }
    .page-header { border-bottom: 2px solid #1e3a5f !important; padding: 12px 16px !important; background: #fff !important; }
    .page-header h1 { font-size: 1.2rem !important; color: #1e3a5f !important; }
    .stat-card { background: #d6e4f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #b0c4de !important; break-inside: avoid; }
    .stats-grid { grid-template-columns: repeat(4,1fr) !important; gap: 10px !important; margin-bottom: 18px !important; }
    .stat-card .num { font-size: 1.4rem !important; }
    .section-card { border: 1px solid #dce6f4 !important; break-inside: avoid; margin-bottom: 14px !important; }
    .section-header { background: #f0f6ff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; padding: 10px 16px !important; }
    .section-header h3 { font-size: .9rem !important; }
    .section-card thead th { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; font-size: .68rem !important; padding: 8px 12px !important; }
    .section-card tbody td { padding: 8px 12px !important; font-size: .8rem !important; }
    .badge-green { background: #dcfce7 !important; color: #166534 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .badge-red { background: #fee2e2 !important; color: #991b1b !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .grid-2 { grid-template-columns: 1fr 1fr !important; gap: 14px !important; }
    .chart-wrap { height: 180px !important; padding: 12px 16px !important; }
    .print-footer { display: block !important; text-align: center; font-size: .72rem; color: #94a3b8; margin-top: 20px; padding-top: 10px; border-top: 1px solid #e2e8f0; }
    @page { size: A4 landscape; margin: 15mm 12mm; }
}

/* Sembunyikan print footer di layar */
.print-footer { display: none; }
</style>
</head>
<body>

<?php
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- ════ MAIN CONTENT ════ -->
<main class="main">

    <div class="page-header">
        <h1>Laporan &amp; Statistik</h1>
        <p>Ringkasan data perpustakaan LITERA.</p>
        <div class="export-actions">
            <button class="btn-export btn-excel" onclick="exportExcel()">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                Export Excel
            </button>
            <button class="btn-export btn-print" onclick="window.print()">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Print / PDF
            </button>
        </div>
    </div>

    <div class="content">

        <!-- ── Stats Cards ── -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="num"><?= number_format($total_buku) ?></div>
                <div class="lbl">Total Buku</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= number_format($total_pengguna) ?></div>
                <div class="lbl">Total Member</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= number_format($sedang_dipinjam) ?></div>
                <div class="lbl">Sedang Dipinjam</div>
            </div>
            <div class="stat-card">
                <div class="num" style="font-size:1.3rem">Rp <?= number_format($denda_belum, 0, ',', '.') ?></div>
                <div class="lbl">Denda Belum Lunas</div>
            </div>
        </div>

        <!-- ── Chart + Buku Populer ── -->
        <div class="grid-2">

            <!-- Chart peminjaman -->
            <div class="section-card">
                <div class="section-header">
                    <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6"  y1="20" x2="6"  y2="14"/>
                    </svg>
                    <h3>Peminjaman 6 Bulan Terakhir</h3>
                </div>
                <div class="chart-wrap">
                    <canvas id="chartPeminjaman"></canvas>
                </div>
            </div>

            <!-- Buku terpopuler -->
            <div class="section-card">
                <div class="section-header">
                    <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                    <h3>Buku Terpopuler</h3>
                </div>
                <?php if ($buku_populer && mysqli_num_rows($buku_populer) > 0): ?>
                <table>
                    <thead>
                        <tr><th>#</th><th>Judul</th><th>Penulis</th><th>Dipinjam</th></tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($r = mysqli_fetch_assoc($buku_populer)): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($r['judul']) ?></td>
                            <td><?= htmlspecialchars($r['penulis']) ?></td>
                            <td><span class="badge badge-green"><?= $r['total_pinjam'] ?>x</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">Belum ada data peminjaman.</div>
                <?php endif; ?>
            </div>

        </div>

        <div class="grid-2">

            <!-- Member paling aktif -->
            <div class="section-card">
                <div class="section-header">
                    <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                    <h3>Member Paling Aktif</h3>
                </div>
                <?php if ($member_aktif && mysqli_num_rows($member_aktif) > 0): ?>
                <table>
                    <thead>
                        <tr><th>#</th><th>Nama</th><th>Username</th><th>Total Pinjam</th></tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($r = mysqli_fetch_assoc($member_aktif)): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($r['nama']) ?></td>
                            <td><?= htmlspecialchars($r['username']) ?></td>
                            <td><span class="badge badge-green"><?= $r['total_pinjam'] ?>x</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">Belum ada data member.</div>
                <?php endif; ?>
            </div>

            <!-- Ringkasan denda -->
            <div class="section-card">
                <div class="section-header">
                    <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8"  x2="12"   y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <h3>Ringkasan Denda</h3>
                </div>
                <table>
                    <thead>
                        <tr><th>Keterangan</th><th>Jumlah</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Denda Keseluruhan</td>
                            <td style="font-weight:700">Rp <?= number_format($total_denda, 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>Denda Sudah Lunas</td>
                            <td><span class="badge badge-green">Rp <?= number_format($denda_lunas, 0, ',', '.') ?></span></td>
                        </tr>
                        <tr>
                            <td>Denda Belum Lunas</td>
                            <td><span class="badge badge-red">Rp <?= number_format($denda_belum, 0, ',', '.') ?></span></td>
                        </tr>
                        <tr>
                            <td>Peminjaman Selesai</td>
                            <td style="font-weight:700"><?= number_format($dikembalikan) ?> transaksi</td>
                        </tr>
                        <tr>
                            <td>Total Semua Peminjaman</td>
                            <td style="font-weight:700"><?= number_format($total_pinjam) ?> transaksi</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

    </div>

    <div class="print-footer">
        Dicetak dari Sistem LITERA &mdash; <?= date('d F Y, H:i') ?> WIB
    </div>

</main>

<script>
const DB = {
    stats: {
        total_buku      : <?= (int)$total_buku ?>,
        total_pengguna  : <?= (int)$total_pengguna ?>,
        total_pinjam    : <?= (int)$total_pinjam ?>,
        sedang_dipinjam : <?= (int)$sedang_dipinjam ?>,
        dikembalikan    : <?= (int)$dikembalikan ?>,
        total_denda     : <?= (float)$total_denda ?>,
        denda_lunas     : <?= (float)$denda_lunas ?>,
        denda_belum     : <?= (float)$denda_belum ?>,
    },
    books      : <?= $json_books ?>,
    categories : <?= $json_categories ?>,
    racks      : <?= $json_racks ?>,
    members    : <?= $json_members ?>,
    borrowings : <?= $json_borrowings ?>,
    fines      : <?= $json_fines ?>,
    returns    : <?= $json_returns ?>,
};

// Warna brand LITERA
const COLOR = {
    navy    : '1E3A5F',  
    blue    : '2563EB',  
    lightBg : 'D6E4F0',  
    headerBg: '1E3A5F',  
    headerFg: 'FFFFFF',
    rowAlt  : 'EEF4FB', 
    rowWhite: 'FFFFFF',  // baris genap
    green   : 'DCFCE7',  
    red     : 'FEE2E2',
    redFg   : '991B1B',
    yellow  : 'FEF9C3',
    yellowFg: '854D0E',
    muted   : '64748B',
};


function hStyle(bgHex, fgHex = 'FFFFFF', sz = 11, bold = true) {
    return {
        font  : { bold, sz, color: { rgb: fgHex } },
        fill  : { fgColor: { rgb: bgHex } },
        alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
        border: {
            top   : { style:'thin', color:{ rgb:'B0C4DE' } },
            bottom: { style:'thin', color:{ rgb:'B0C4DE' } },
            left  : { style:'thin', color:{ rgb:'B0C4DE' } },
            right : { style:'thin', color:{ rgb:'B0C4DE' } },
        }
    };
}


function dStyle(bgHex = 'FFFFFF', fgHex = '1E3A5F', bold = false, align = 'left') {
    return {
        font  : { bold, sz: 10, color: { rgb: fgHex } },
        fill  : { fgColor: { rgb: bgHex } },
        alignment: { horizontal: align, vertical: 'center', wrapText: true },
        border: {
            bottom: { style:'hair', color:{ rgb:'E2E8F0' } },
            right : { style:'hair', color:{ rgb:'E2E8F0' } },
        }
    };
}

function badgeStyle(status) {
    const map = {
        'tersedia'    : [COLOR.green,  COLOR.greenFg],
        'dipinjam'    : [COLOR.lightBg,'2563EB'],
        'terlambat'   : [COLOR.red,    COLOR.redFg],
        'dikembalikan': [COLOR.green,  COLOR.greenFg],
        'lunas'       : [COLOR.green,  COLOR.greenFg],
        'belum_lunas' : [COLOR.red,    COLOR.redFg],
        'aktif'       : [COLOR.green,  COLOR.greenFg],
        'nonaktif'    : [COLOR.red,    COLOR.redFg],
        'baik'        : [COLOR.green,  COLOR.greenFg],
        'rusak'       : [COLOR.red,    COLOR.redFg],
    };
    const [bg, fg] = map[status] ?? [COLOR.rowWhite, COLOR.navy];
    return dStyle(bg, fg, true, 'center');
}


function styleRange(ws, r1, c1, r2, c2, styleFn) {
    for (let r = r1; r <= r2; r++) {
        for (let c = c1; c <= c2; c++) {
            const addr = XLSX.utils.encode_cell({ r, c });
            if (!ws[addr]) ws[addr] = { t: 'z', v: '' };
            ws[addr].s = typeof styleFn === 'function' ? styleFn(r, c) : styleFn;
        }
    }
}

// Buat worksheet dari array header + rows dengan styling otomatis
function buildSheet(headers, rows, colWidths, titleRow = null) {
    const aoa = [];

    // Opsional: judul sheet di baris 0
    if (titleRow) {
        aoa.push([titleRow]);
    }

    // Header kolom
    aoa.push(headers);

    // Data rows
    rows.forEach((row, ri) => {
        aoa.push(row);
    });

    const ws = XLSX.utils.aoa_to_sheet(aoa);
    ws['!cols'] = colWidths.map(w => ({ wch: w }));

    const offset = titleRow ? 1 : 0; // row offset karena ada judul

    // Style judul
    if (titleRow) {
        const titleAddr = XLSX.utils.encode_cell({ r: 0, c: 0 });
        ws[titleAddr].s = {
            font : { bold: true, sz: 14, color: { rgb: COLOR.headerFg } },
            fill : { fgColor: { rgb: COLOR.navy } },
            alignment: { horizontal: 'left', vertical: 'center' },
        };
        // Merge judul ke seluruh lebar
        ws['!merges'] = ws['!merges'] || [];
        ws['!merges'].push({ s: {r:0, c:0}, e: {r:0, c: headers.length - 1} });
        ws['!rows'] = ws['!rows'] || [];
        ws['!rows'][0] = { hpt: 28 };
    }

    // Style header kolom
    styleRange(ws, offset, 0, offset, headers.length - 1, hStyle(COLOR.headerBg));
    ws['!rows'] = ws['!rows'] || [];
    ws['!rows'][offset] = { hpt: 22 };

    // Style baris data + baris row height
    rows.forEach((row, ri) => {
        const r = offset + 1 + ri;
        const bg = ri % 2 === 0 ? COLOR.rowWhite : COLOR.rowAlt;
        ws['!rows'][r] = { hpt: 18 };
        row.forEach((val, c) => {
            const addr = XLSX.utils.encode_cell({ r, c });
            if (!ws[addr]) ws[addr] = { t: 'z', v: '' };
            ws[addr].s = dStyle(bg, COLOR.navy);
        });
    });

    return ws;
}

// ── Format rupiah ──
function rp(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

//export
function exportExcel() {
    const wb = XLSX.utils.book_new();
    const tgl = new Date().toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' });

    // ── Sheet 1: RINGKASAN ────────────────────────────────────
    {
        const s = DB.stats;
        // Layout horizontal: metrik jadi kolom, nilai di baris bawah
        const headers = ['Total Buku','Total Member','Sedang Dipinjam','Sudah Kembali','Total Transaksi','Total Denda','Denda Lunas','Denda Belum Lunas'];
        const values  = [s.total_buku, s.total_pengguna, s.sedang_dipinjam, s.dikembalikan, s.total_pinjam, rp(s.total_denda), rp(s.denda_lunas), rp(s.denda_belum)];

        const aoa = [
            [`LAPORAN PERPUSTAKAAN LITERA  ${tgl}`],
            headers,
            values,
        ];
        const ws = XLSX.utils.aoa_to_sheet(aoa);
        ws['!cols'] = [22,18,20,18,18,22,20,22].map(w => ({ wch:w }));

        // Merge judul
        ws['!merges'] = [{ s:{r:0,c:0}, e:{r:0,c:7} }];
        ws['!rows']   = [{ hpt:30 }, { hpt:22 }, { hpt:36 }];

        // Style judul
        const titleCell = ws[XLSX.utils.encode_cell({r:0,c:0})];
        titleCell.s = { font:{bold:true,sz:16,color:{rgb:'FFFFFF'}}, fill:{fgColor:{rgb:COLOR.navy}}, alignment:{horizontal:'center',vertical:'center'} };

        // Style header kolom
        headers.forEach((_, c) => {
            const a = XLSX.utils.encode_cell({r:1,c});
            if (ws[a]) ws[a].s = hStyle(COLOR.navy);
        });

        // Style nilai warna berbeda per kolom
        const valueBg = [COLOR.lightBg,COLOR.lightBg,'FEF3C7',COLOR.green,COLOR.lightBg,COLOR.red,COLOR.green,COLOR.red];
        const valueFg = [COLOR.navy,COLOR.navy,'92400E',COLOR.greenFg,COLOR.navy,COLOR.redFg,COLOR.greenFg,COLOR.redFg];
        values.forEach((_, c) => {
            const a = XLSX.utils.encode_cell({r:2,c});
            if (ws[a]) ws[a].s = { font:{bold:true,sz:13,color:{rgb:valueFg[c]}}, fill:{fgColor:{rgb:valueBg[c]}}, alignment:{horizontal:'center',vertical:'center'} };
        });

        XLSX.utils.book_append_sheet(wb, ws, ' Ringkasan');
    }

    // ── Sheet 2: DAFTAR BUKU ─────────────────────────────────
    {
        const headers = ['No','ID','Judul Buku','Penulis','Penerbit','ISBN','Thn Terbit','Kategori','Kode Kat.','Kode Rak','Nama Rak','Lokasi Rak','Stok Total','Stok Tersedia','Halaman','Bahasa','Status','Tgl Input'];
        const rows = DB.books.map((b, i) => [
            i+1, b.id, b.judul, b.penulis, b.penerbit||'-', b.isbn||'-',
            b.tahun_terbit||'-', b.kategori, b.kode_kategori,
            b.kode_rak, b.nama_rak, b.lokasi||'-',
            b.stok, b.stok_tersedia, b.halaman||'-', b.bahasa||'-',
            b.status, b.created_at?.slice(0,10)||'-'
        ]);
        const ws = buildSheet(headers, rows,
            [4,5,32,22,20,16,10,16,10,10,18,12,10,12,8,10,12,12],
            `DAFTAR BUKU  ${tgl}`
        );
        // Style kolom status per baris
        rows.forEach((row, ri) => {
            const statusIdx = 16; // kolom "Status"
            const addr = XLSX.utils.encode_cell({ r: ri + 2, c: statusIdx }); // +2: judul + header
            if (ws[addr]) ws[addr].s = badgeStyle(row[statusIdx]);
        });
        XLSX.utils.book_append_sheet(wb, ws, ' Daftar Buku');
    }

    // ── Sheet 3: KATEGORI ─────────────────────────────────────
    {
        const headers = ['No','ID','Kode','Nama Kategori','Jumlah Buku','Deskripsi','Tgl Dibuat'];
        const rows = DB.categories.map((c, i) => [
            i+1, c.id, c.kode, c.nama, c.jumlah_buku, c.deskripsi||'-', c.created_at?.slice(0,10)||'-'
        ]);
        const ws = buildSheet(headers, rows, [4,5,8,24,12,36,12], `KATEGORI BUKU  ${tgl}`);
        XLSX.utils.book_append_sheet(wb, ws, ' Kategori');
    }

    // ── Sheet 4: RAK ──────────────────────────────────────────
    {
        const headers = ['No','ID','Kode Rak','Nama Rak','Lokasi','Kapasitas','Jml Buku','% Terisi','Deskripsi','Tgl Dibuat'];
        const rows = DB.racks.map((r, i) => {
            const pct = r.kapasitas > 0 ? ((r.jumlah_buku / r.kapasitas) * 100).toFixed(1) + '%' : '0%';
            return [i+1, r.id, r.kode_rak, r.nama, r.lokasi||'-', r.kapasitas, r.jumlah_buku, pct, r.deskripsi||'-', r.created_at?.slice(0,10)||'-'];
        });
        const ws = buildSheet(headers, rows, [4,5,10,22,14,10,10,10,28,12], `DATA RAK  ${tgl}`);
        // Warnai % Terisi
        rows.forEach((row, ri) => {
            const pctVal = parseFloat(row[7]);
            const bg = pctVal >= 90 ? COLOR.red : pctVal >= 60 ? COLOR.yellow : COLOR.green;
            const fg = pctVal >= 90 ? COLOR.redFg : pctVal >= 60 ? COLOR.yellowFg : COLOR.greenFg;
            const addr = XLSX.utils.encode_cell({ r: ri + 2, c: 7 });
            if (ws[addr]) ws[addr].s = dStyle(bg, fg, true, 'center');
        });
        XLSX.utils.book_append_sheet(wb, ws, 'Rak');
    }

    // ── Sheet 5: MEMBER ───────────────────────────────────────
    {
        const headers = ['No','ID','Nama','Username','Email','No HP','Status','Total Pinjam','Aktif','Sudah Kembali','Terlambat','Tgl Daftar'];
        const rows = DB.members.map((m, i) => [
            i+1, m.id, m.nama, m.username, m.email, m.no_hp||'-',
            m.status, m.total_pinjam, m.sedang_pinjam, m.sudah_kembali, m.terlambat,
            m.created_at?.slice(0,10)||'-'
        ]);
        const ws = buildSheet(headers, rows,
            [4,5,22,16,28,14,10,12,8,14,10,12],
            `DAFTAR MEMBER  ${tgl}`
        );
        rows.forEach((row, ri) => {
            const addr = XLSX.utils.encode_cell({ r: ri + 2, c: 6 });
            if (ws[addr]) ws[addr].s = badgeStyle(row[6]);
        });
        XLSX.utils.book_append_sheet(wb, ws, 'Member');
    }

    // ── Sheet 6: RIWAYAT PEMINJAMAN ──────────────────────────
    {
        const headers = ['No','Kode','Peminjam','Username','Buku Dipinjam','Tgl Pinjam','Tgl Kembali','Status','Hari Terlambat','Catatan'];
        const rows = DB.borrowings.map((b, i) => [
            i+1, b.kode_pinjam, b.nama_peminjam, b.username,
            b.buku_dipinjam||'-', b.tanggal_pinjam, b.tanggal_kembali,
            b.status, b.hari_terlambat > 0 ? b.hari_terlambat + ' hari' : '-',
            b.catatan||'-'
        ]);
        const ws = buildSheet(headers, rows,
            [4,10,20,14,36,13,13,14,14,24],
            `RIWAYAT PEMINJAMAN  ${tgl}`
        );
        rows.forEach((row, ri) => {
            const addr = XLSX.utils.encode_cell({ r: ri + 2, c: 7 });
            if (ws[addr]) ws[addr].s = badgeStyle(row[7]);
            // Warnai hari terlambat jika > 0
            if (row[8] !== '-') {
                const addrL = XLSX.utils.encode_cell({ r: ri + 2, c: 8 });
                if (ws[addrL]) ws[addrL].s = dStyle(COLOR.red, COLOR.redFg, true, 'center');
            }
        });
        XLSX.utils.book_append_sheet(wb, ws, 'Peminjaman');
    }

    // ── Sheet 7: DENDA ────────────────────────────────────────
    {
        const headers = ['No','Kode Pinjam','Peminjam','Username','Buku','Hari Terlambat','Denda/Hari','Total Denda','Status','Tgl Bayar','Keterangan'];
        const rows = DB.fines.map((f, i) => [
            i+1, f.kode_pinjam, f.nama_peminjam, f.username,
            f.buku||'-', f.hari_terlambat,
            rp(f.denda_per_hari), rp(f.total_denda),
            f.status, f.tanggal_bayar||'-', f.keterangan||'-'
        ]);
        const ws = buildSheet(headers, rows,
            [4,12,20,14,32,14,14,16,14,12,28],
            `DATA DENDA ${tgl}`
        );
        rows.forEach((row, ri) => {
            const addr = XLSX.utils.encode_cell({ r: ri + 2, c: 8 });
            if (ws[addr]) ws[addr].s = badgeStyle(row[8]);
        });
        XLSX.utils.book_append_sheet(wb, ws, 'Denda');
    }

    // ── Sheet 8: PENGEMBALIAN ─────────────────────────────────
    {
        const headers = ['No','Kode Pinjam','Peminjam','Buku','Tgl Pinjam','Deadline','Tgl Dikembalikan','Kondisi','Catatan'];
        const rows = DB.returns.map((r, i) => [
            i+1, r.kode_pinjam, r.nama_peminjam,
            r.buku||'-', r.tanggal_pinjam, r.deadline_kembali,
            r.tanggal_aktual_kembali, r.kondisi_buku||'-', r.catatan||'-'
        ]);
        const ws = buildSheet(headers, rows,
            [4,12,20,32,13,13,18,12,28],
            `DATA PENGEMBALIAN ${tgl}`
        );
        rows.forEach((row, ri) => {
            const addr = XLSX.utils.encode_cell({ r: ri + 2, c: 7 });
            if (ws[addr]) ws[addr].s = badgeStyle(row[7]);
        });
        XLSX.utils.book_append_sheet(wb, ws, 'Pengembalian');
    }

    // ── Download ──────────────────────────────────────────────
    const tglFile = new Date().toISOString().slice(0, 10);
    XLSX.writeFile(wb, `Laporan_LITERA_${tglFile}.xlsx`, { cellStyles: true });
}

// ── Chart ──
const ctx = document.getElementById('chartPeminjaman').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels  : <?= $chart_labels_json ?>,
        datasets: [{
            label          : 'Jumlah Peminjaman',
            data           : <?= $chart_data_json ?>,
            backgroundColor: 'rgba(37,99,235,.18)',
            borderColor    : '#2563EB',
            borderWidth    : 2,
            borderRadius   : 8,
            borderSkipped  : false,
        }]
    },
    options: {
        responsive           : true,
        maintainAspectRatio  : false,
        plugins: {
            legend : { display: false },
            tooltip: {
                backgroundColor: '#1E3A5F',
                titleColor     : '#fff',
                bodyColor      : '#CBD5E1',
                padding        : 10,
                cornerRadius   : 8,
            }
        },
        scales: {
            x: {
                grid : { display: false },
                ticks: { color:'#94A3B8', font:{ family:'Nunito', size:11 } }
            },
            y: {
                grid : { color:'#F1F5F9' },
                ticks: { color:'#94A3B8', font:{ family:'Nunito', size:11 }, stepSize:1 },
                beginAtZero: true
            }
        }
    }
});
</script>
</body>
</html>