<?php
require_once '../../includes/config.php';
require_once '../../includes/auth_helper.php';
require_login();

$msg   = $_GET['msg']   ?? '';
$error = $_GET['error'] ?? '';

$search = trim($_GET['q'] ?? '');
$sq     = mysqli_real_escape_string($conn, $search);
$where  = $search ? "WHERE b.kode_pinjam LIKE '%$sq%' OR u.nama LIKE '%$sq%' OR bk.judul LIKE '%$sq%'" : '';

$data = mysqli_query($conn, "
    SELECT b.*, u.nama as nama_user, u.username,
           COUNT(bd.id) as total_buku,
           GROUP_CONCAT(bk.judul SEPARATOR ', ') as buku_list
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    LEFT JOIN borrowing_details bd ON b.id = bd.borrowing_id
    LEFT JOIN books bk ON bd.book_id = bk.id
    $where
    GROUP BY b.id
    ORDER BY b.tanggal_pinjam DESC, b.id DESC
");

$total = mysqli_num_rows($data);
$isAdmin = ($_SESSION['role_id'] ?? 0) == 1; // Sesuaikan dengan session yang ada
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Peminjaman — Litera</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --sidebar-bg: #E0E7FF;
            --sidebar-w: 260px;
            --blue-dark: #4F46E5;
            --navy: #1E3A8A;
            --bg: #F8FAFC;
            --muted: #64748B;
            --red: #EF4444;
        }
        body {
            font-family: 'Nunito', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
        }
        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            overflow-y: auto;
        }
        .logo {
            padding: 0 24px 20px;
            text-align: center;
            border-bottom: 1px solid #C3D0FF;
        }
        .logo-text {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(90deg, #4F46E5, #22D3EE);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .nav-group {
            margin-top: 25px;
            padding: 0 12px;
        }
        .nav-group-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748B;
            padding: 0 20px 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: #334155;
            text-decoration: none;
            border-radius: 8px;
            margin: 2px 8px;
            font-weight: 500;
        }
        .nav-item:hover, .nav-item.active {
            background: white;
            color: var(--blue-dark);
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(79,70,229,0.1);
        }
        .main-content {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 30px;
        }
        .page-header {
            background: white;
            padding: 20px 28px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .ok { background: #F0FDF4; color: #166534; }
        .err { background: #FEF2F2; color: #B91C1C; }
        table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 14px 20px;
            text-align: left;
        }
        th {
            background: #F1F5F9;
            font-weight: 600;
            color: #475569;
            font-size: 0.85rem;
        }
        .btn-primary {
            background: #4F46E5;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>
<body>

<?php
$active_page = 'borrowings';
require_once __DIR__ . '/../../includes/sidebar.php';
?>


<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="page-header">
        <h1>Manajemen Peminjaman</h1>
        <p>Daftar semua transaksi peminjaman buku</p>
    </div>

    <?php if ($msg): ?><div class="alert ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if ($isAdmin): ?>
    <div style="margin-bottom: 20px;">
        <a href="create.php" class="btn-primary">+ Tambah Peminjaman Baru</a>
    </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Kode Pinjam</th>
                <th>Anggota</th>
                <th>Buku</th>
                <th>Tgl Pinjam</th>
                <th>Tgl Kembali</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($total == 0): ?>
                <tr><td colspan="7" style="text-align:center; padding:40px; color:#64748B;">Belum ada data peminjaman.</td></tr>
            <?php else: while ($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['kode_pinjam']) ?></strong></td>
                    <td><?= htmlspecialchars($row['nama_user']) ?></td>
                    <td><?= htmlspecialchars(substr($row['buku_list'] ?? '', 0, 60)) ?>...</td>
                    <td><?= $row['tanggal_pinjam'] ?></td>
                    <td><?= $row['tanggal_kembali'] ?? '-' ?></td>
                    <td><?= ucfirst($row['status']) ?></td>
                    <td>
                        <a href="detail.php?id=<?= $row['id'] ?>" style="color:#4F46E5; margin-right:10px;">Detail</a>
                        <?php if ($row['status'] == 'dipinjam' && $isAdmin): ?>
                            <a href="return.php?id=<?= $row['id'] ?>" style="color:#10B981;">Kembalikan</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>