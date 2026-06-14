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

// FIXED: nama kolom yang benar adalah total_denda, bukan jumlah_denda
$total_denda = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_denda),0) FROM fines"))[0] ?? 0;
$denda_lunas = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_denda),0) FROM fines WHERE status='lunas'"))[0] ?? 0;
$denda_belum = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_denda),0) FROM fines WHERE status='belum_lunas'"))[0] ?? 0;

// ─── Buku terpopuler ─────────────────────────────────────────
$buku_populer = mysqli_query($conn, "
    SELECT bk.judul, bk.penulis, COUNT(bd.id) AS total_pinjam
    FROM books bk
    LEFT JOIN borrowing_details bd ON bd.book_id = bk.id
    GROUP BY bk.id
    ORDER BY total_pinjam DESC
    LIMIT 5
");

// ─── Member aktif (terbanyak pinjam) ─────────────────────────
$member_aktif = mysqli_query($conn, "
    SELECT u.nama, u.username, COUNT(b.id) AS total_pinjam
    FROM users u
    LEFT JOIN borrowings b ON b.user_id = u.id
    WHERE u.role_id != 1
    GROUP BY u.id
    ORDER BY total_pinjam DESC
    LIMIT 5
");


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

// Tandai halaman aktif untuk sidebar
$active_page = 'reports';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan — LITERA</title>
<link rel="stylesheet" href="/LITERA-app/assets/app.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>

/* ── Styles khusus halaman Laporan ── */

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
.stat-card {
    background   : #D6E4F0;
    border-radius: 14px;
    padding      : 22px;
    border       : 1px solid rgba(37,99,235,.08);
    transition   : transform .2s, box-shadow .2s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(37,99,235,.1); }
.stat-card .num  { font-size:1.8rem; font-weight:800; color:#1E3A5F; line-height:1; }
.stat-card .lbl  { font-size:.78rem; color:#64748B; font-weight:600; margin-top:6px; }

/* Section cards (konsisten dengan dashboard) */
.section-card {
    background   : #fff;
    border-radius: 16px;
    border       : 1px solid #E2E8F0;
    overflow     : hidden;
    margin-bottom: 24px;
}
.section-header {
    display      : flex;
    align-items  : center;
    gap          : 10px;
    padding      : 18px 24px 14px;
    border-bottom: 1px solid #F1F5F9;
    background   : #FAFBFC;
}
.section-header h3 { font-size:1rem; font-weight:800; color:#1E3A5F; }

/* Grid 2 kolom */
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px; }

/* Chart */
.chart-wrap { height:220px; padding:20px 24px; position:relative; }

/* Table di dalam section-card */
.section-card table { width:100%; border-collapse:collapse; }
.section-card thead th {
    padding      : 11px 18px;
    text-align   : left;
    font-size    : .72rem;
    font-weight  : 700;
    color        : #64748B;
    text-transform: uppercase;
    letter-spacing: .8px;
    background   : #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}
.section-card tbody tr { border-bottom:1px solid #F1F5F9; transition:background .15s; }
.section-card tbody tr:last-child { border-bottom:none; }
.section-card tbody tr:hover { background:#F8FBFF; }
.section-card tbody td { padding:12px 18px; font-size:.85rem; color:#374151; font-weight:500; }

/* Badge */
.badge-green { background:#DCFCE7; color:#166534; }
.badge-red   { background:#FEE2E2; color:#991B1B; }

.empty-state { text-align:center; padding:32px 20px; color:#94A3B8; font-size:.875rem; }

/* ── Responsive ── */
@media (max-width: 1100px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .grid-2     { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .stats-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 400px) {
    .stats-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<?php
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- ════ MAIN CONTENT ════ -->
<main class="main">

    <!-- Page Header — konsisten dengan halaman lain -->
    <div class="page-header">
        <h1>Laporan &amp; Statistik</h1>
        <p>Ringkasan data perpustakaan LITERA.</p>
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

        <!-- ── Member Aktif + Ringkasan Denda ── -->
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

    </div><!-- /content -->
</main>

<script>
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