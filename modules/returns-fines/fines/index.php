<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth_helper.php';
require_login();

$msg   = $_GET['msg']   ?? '';
$error = $_GET['error'] ?? '';
$is_admin = is_admin();
$user_id  = (int)$_SESSION['user_id'];

// Tandai lunas
if ($is_admin && isset($_GET['lunas'])) {
    $fine_id = intval($_GET['lunas']);
    mysqli_query($conn, "UPDATE fines SET status='lunas' WHERE id=$fine_id");
    header('Location: index.php?msg=Denda berhasil ditandai lunas');
    exit();
}

$filter_status = $_GET['status'] ?? '';
$sq_status = in_array($filter_status, ['belum_lunas','lunas']) ? $filter_status : '';

if ($is_admin) {
    $where = $sq_status ? "WHERE f.status = '$sq_status'" : "";
} else {
    $where = $sq_status
        ? "WHERE b.user_id = $user_id AND f.status = '$sq_status'"
        : "WHERE b.user_id = $user_id";
}

$data = mysqli_query($conn, "
    SELECT f.id, f.borrowing_id, f.hari_terlambat, f.denda_per_hari, f.total_denda, f.status,
           f.created_at,
           b.kode_pinjam, b.tanggal_kembali,
           u.nama as nama_user,
           GROUP_CONCAT(bk.judul SEPARATOR ', ') as buku_list
    FROM fines f
    JOIN borrowings b ON f.borrowing_id = b.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN borrowing_details bd ON b.id = bd.borrowing_id
    LEFT JOIN books bk ON bd.book_id = bk.id
    $where
    GROUP BY f.id
    ORDER BY f.id DESC
");
$total = mysqli_num_rows($data);

// Summary
$total_denda_belum = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COALESCE(SUM(total_denda),0) FROM fines WHERE status='belum_lunas'" .
    ($is_admin ? "" : " AND borrowing_id IN (SELECT id FROM borrowings WHERE user_id=$user_id)")))[0] ?? 0;
$count_belum = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM fines WHERE status='belum_lunas'" .
    ($is_admin ? "" : " AND borrowing_id IN (SELECT id FROM borrowings WHERE user_id=$user_id)")))[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Denda LITERA</title>
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
.greeting{font-size:.85rem;color:var(--muted);font-weight:500}
.page-header h1{font-size:1.35rem;font-weight:800;color:var(--navy)}
.page-header p{font-size:.85rem;color:var(--muted);margin-top:3px}
.content{padding:28px 32px;flex:1}
.alert{padding:12px 18px;border-radius:10px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.ok{background:#F0FDF4;border:1px solid #BBF7D0;color:#16A34A}
.err{background:#FEF2F2;border:1px solid #FECACA;color:var(--red)}
/* Summary cards */
.summary-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:24px}
.sum-card{background:#fff;border-radius:14px;padding:22px;border:1px solid #E2ECF8;display:flex;align-items:center;gap:14px}
.sum-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sum-num{font-size:1.75rem;font-weight:800;color:var(--navy);line-height:1}
.sum-label{font-size:.78rem;color:var(--muted);font-weight:600;margin-top:4px}
/* Filter bar */
.filter-bar{display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap}
.filter-bar select{padding:9px 14px;border:2px solid #C7D8F8;border-radius:9px;font-family:'Nunito',sans-serif;font-size:.85rem;color:var(--navy);outline:none;background:#fff;cursor:pointer}
.filter-bar select:focus{border-color:var(--blue-dark)}
/* Table card */
.table-card{background:#fff;border-radius:16px;border:1px solid #E2ECF8;box-shadow:0 2px 12px rgba(30,58,95,.06);overflow:hidden}
.table-head{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #F1F5F9}
.table-head h2{font-size:1rem;font-weight:800;color:var(--navy)}
table{width:100%;border-collapse:collapse}
th{padding:12px 18px;text-align:left;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #F1F5F9;background:#FAFBFF}
td{padding:12px 18px;font-size:.86rem;color:var(--navy);border-bottom:1px solid #F8FAFC;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#FAFBFF}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700}
.badge-lunas{background:#DCFCE7;color:#166534}
.badge-belum{background:#FEF3C7;color:#92400E}
.btn-lunas{padding:5px 14px;background:#F0FDF4;color:#16A34A;border:none;border-radius:8px;font-family:'Nunito',sans-serif;font-size:.78rem;font-weight:700;cursor:pointer;text-decoration:none;transition:background .2s}
.btn-lunas:hover{background:#DCFCE7}
.empty-state{text-align:center;padding:48px 20px;color:#94A3B8;font-size:.875rem;font-weight:500}
@media(max-width:640px){.sidebar{display:none}.main{margin-left:0}.content{padding:16px}.summary-grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<?php
$active_page = 'fines';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>
<main class="main">
    <div class="page-header">
        <h1>Kelola Denda</h1>
        <p>Kelola dan pantau status denda keterlambatan pengembalian buku.</p>
    </div>

    <div class="content">
        <?php if ($msg): ?><div class="alert ok">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert err">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Summary -->
        <div class="summary-grid">
            <div class="sum-card">
                <div class="sum-icon" style="background:#FEF3C7">
                    <svg width="22" height="22" fill="none" stroke="#92400E" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div>
                    <div class="sum-num"><?= $count_belum ?></div>
                    <div class="sum-label">Denda Belum Lunas</div>
                </div>
            </div>
            <div class="sum-card">
                <div class="sum-icon" style="background:#FEE2E2">
                    <svg width="22" height="22" fill="none" stroke="#DC2626" stroke-width="1.8" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <div class="sum-num" style="font-size:1.3rem">Rp <?= number_format($total_denda_belum, 0, ',', '.') ?></div>
                    <div class="sum-label">Total Denda Tertunggak</div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-bar">
            <form method="GET">
                <select name="status" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="belum_lunas" <?= $filter_status === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                    <option value="lunas" <?= $filter_status === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                </select>
            </form>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="table-head">
                <h2>Daftar Denda</h2>
                <span style="font-size:.78rem;color:var(--muted);font-weight:600"><?= $total ?> data</span>
            </div>
            <?php if ($total > 0): ?>
            <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Kode Pinjam</th>
                        <th>Peminjam</th>
                        <th>Buku</th>
                        <th>Hari Terlambat</th>
                        <th>Denda/Hari</th>
                        <th>Total Denda</th>
                        <th>Status</th>
                        <?php if ($is_admin): ?><th>Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php while ($r = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><code style="background:#EFF6FF;color:#2563EB;padding:2px 8px;border-radius:6px;font-size:.78rem;font-weight:700"><?= htmlspecialchars($r['kode_pinjam']) ?></code></td>
                    <td style="font-weight:600"><?= htmlspecialchars($r['nama_user']) ?></td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($r['buku_list']) ?></td>
                    <td><span style="font-weight:700;color:#DC2626"><?= $r['hari_terlambat'] ?> hari</span></td>
                    <td>Rp <?= number_format($r['denda_per_hari'], 0, ',', '.') ?></td>
                    <td style="font-weight:800;color:#DC2626">Rp <?= number_format($r['total_denda'], 0, ',', '.') ?></td>
                    <td>
                        <?php if ($r['status'] === 'belum_lunas'): ?>
                            <span class="badge badge-belum">Belum Lunas</span>
                        <?php else: ?>
                            <span class="badge badge-lunas">Lunas</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($is_admin): ?>
                    <td>
                        <?php if ($r['status'] === 'belum_lunas'): ?>
                        <a href="index.php?lunas=<?= $r['id'] ?>" class="btn-lunas"
                           onclick="return confirm('Tandai denda ini sebagai lunas?')">
                           Tandai Lunas
                        </a>
                        <?php else: ?>
                        <span style="color:#94A3B8;font-size:.82rem">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="margin-bottom:12px;opacity:.4"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <p>Tidak ada data denda<?= $filter_status ? ' dengan status ini' : '' ?>.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>
</main>

</body>
</html>