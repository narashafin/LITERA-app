<?php
require_once '../../../includes/config.php';
require_once '../../../includes/auth_helper.php';
require_login();

$msg   = $_GET['msg']   ?? '';
$error = $_GET['error'] ?? '';

$search = trim($_GET['q'] ?? '');
$sq     = mysqli_real_escape_string($conn, $search);
$where  = $search ? "WHERE c.nama LIKE '%$sq%' OR c.kode LIKE '%$sq%'" : '';

$data = mysqli_query($conn,
    "SELECT c.*, COUNT(b.id) AS total_buku
     FROM categories c
     LEFT JOIN books b ON c.id = b.category_id
     $where
     GROUP BY c.id
     ORDER BY c.nama ASC"
);
$total = mysqli_num_rows($data);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori Litera</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0
    }

    :root {
        --sidebar-bg: #C9D8E8;
        --sidebar-w: 240px;
        --blue-dark: #2563EB;
        --navy: #1E3A5F;
        --bg: #EDF2F7;
        --muted: #64748B;
        --red: #EF4444
    }

    body {
        font-family: 'Nunito', sans-serif;
        background: var(--bg);
        min-height: 100vh;
        display: flex
    }

    .sidebar {
        width: var(--sidebar-w);
        height: 100vh;
        background: var(--sidebar-bg);
        border-radius: 0 24px 24px 0;
        display: flex;
        flex-direction: column;
        padding-bottom: 24px;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 100;
        box-shadow: 2px 0 16px rgba(30, 58, 95, .08);
        overflow: hidden
    }

    .sidebar-logo {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 28px 16px 20px;
        border-bottom: 1px solid rgba(30, 58, 95, .12)
    }

    .sidebar-logo img {
        width: 90px;
        height: 90px;
        object-fit: contain
    }

    .sidebar-logo .logo-text {
        font-size: 1.25rem;
        font-weight: 800;
        letter-spacing: 4px;
        background: linear-gradient(90deg, #4ecdc4, #45b7d1, #96c93d, #f7971e, #f9d62e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-top: 4px
    }

    .sidebar-nav {
        flex: 1;
        padding: 16px 0;
        overflow-y: auto
    }

    .nav-group-label {
        font-size: .68rem;
        font-weight: 800;
        color: var(--navy);
        letter-spacing: 1.4px;
        text-transform: uppercase;
        padding: 14px 24px 6px
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 24px 9px 32px;
        color: #374151;
        text-decoration: none;
        font-size: .875rem;
        font-weight: 500;
        border-radius: 0 20px 20px 0;
        margin-right: 16px;
        transition: all .2s;
        position: relative
    }

    .nav-item:hover {
        background: rgba(37, 99, 235, .1);
        color: var(--blue-dark);
        font-weight: 600
    }

    .nav-item.active {
        background: #fff;
        color: var(--blue-dark);
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(37, 99, 235, .12)
    }

    .nav-item.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 6px;
        bottom: 6px;
        width: 3px;
        background: var(--blue-dark);
        border-radius: 0 3px 3px 0
    }

    .sidebar-footer {
        padding: 0 16px;
        margin-top: 8px
    }

    .btn-logout {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 10px 16px;
        background: rgba(239, 68, 68, .12);
        color: #DC2626;
        border: none;
        border-radius: 12px;
        font-family: 'Nunito', sans-serif;
        font-size: .85rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: background .2s
    }

    .btn-logout:hover {
        background: rgba(239, 68, 68, .22)
    }

    .main {
        margin-left: var(--sidebar-w);
        flex: 1;
        min-height: 100vh;
        display: flex;
        flex-direction: column
    }

    .page-header {
        padding: 20px 32px 18px;
        background: #fff;
        border-bottom: 1px solid #E2E8F0
    }

    .page-header h1 {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--navy)
    }

    .page-header p {
        font-size: .85rem;
        color: var(--muted);
        margin-top: 3px
    }

    .content {
        padding: 28px 32px;
        flex: 1
    }

    .alert {
        padding: 12px 18px;
        border-radius: 10px;
        font-size: .875rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px
    }

    .ok {
        background: #F0FDF4;
        border: 1px solid #BBF7D0;
        color: #16A34A
    }

    .err {
        background: #FEF2F2;
        border: 1px solid #FECACA;
        color: var(--red)
    }

    .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 28px 32px;
        border: 1px solid #E2ECF8;
        box-shadow: 0 2px 12px rgba(30, 58, 95, .06);
        margin-bottom: 28px
    }

    .form-card h2 {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--navy);
        margin-bottom: 20px
    }

    .grid2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px
    }

    .fg {
        display: flex;
        flex-direction: column;
        gap: 5px
    }

    .fg.full {
        grid-column: span 2
    }

    label {
        font-size: .76rem;
        font-weight: 700;
        color: var(--navy);
        text-transform: uppercase;
        letter-spacing: .4px
    }

    input[type=text],
    textarea {
        padding: 11px 14px;
        border: 2px solid #C7D8F8;
        border-radius: 10px;
        font-family: 'Nunito', sans-serif;
        font-size: .88rem;
        color: var(--navy);
        background: #fff;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        width: 100%
    }

    input:focus,
    textarea:focus {
        border-color: var(--blue-dark);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .1)
    }

    textarea {
        resize: vertical;
        min-height: 80px
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
        transition: opacity .2s, transform .15s
    }

    .btn-primary:hover {
        opacity: .9;
        transform: translateY(-1px)
    }

    .table-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #E2ECF8;
        box-shadow: 0 2px 12px rgba(30, 58, 95, .06);
        overflow: hidden
    }

    .table-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 28px;
        border-bottom: 1px solid #F1F5F9;
        flex-wrap: wrap;
        gap: 12px
    }

    .table-head h2 {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--navy)
    }

    .search-form {
        display: flex;
        gap: 8px
    }

    .search-form input {
        padding: 9px 14px;
        border: 2px solid #C7D8F8;
        border-radius: 9px;
        font-family: 'Nunito', sans-serif;
        font-size: .85rem;
        color: var(--navy);
        outline: none;
        width: 220px
    }

    .search-form input:focus {
        border-color: var(--blue-dark)
    }

    .search-form button {
        padding: 9px 18px;
        background: var(--blue-dark);
        color: #fff;
        border: none;
        border-radius: 9px;
        cursor: pointer;
        font-size: .85rem;
        font-weight: 700;
        font-family: 'Nunito', sans-serif
    }

    table {
        width: 100%;
        border-collapse: collapse
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
        background: #FAFBFF
    }

    td {
        padding: 13px 18px;
        font-size: .86rem;
        color: var(--navy);
        border-bottom: 1px solid #F8FAFC;
        vertical-align: middle
    }

    tr:last-child td {
        border-bottom: none
    }

    tr:hover td {
        background: #FAFBFF
    }

    .badge-buku {
        display: inline-block;
        padding: 3px 11px;
        border-radius: 20px;
        font-size: .72rem;
        font-weight: 700;
        background: #DBEAFE;
        color: #1E40AF
    }

    .actions {
        display: flex;
        gap: 7px
    }

    .btn-edit {
        padding: 5px 14px;
        background: #EFF6FF;
        color: var(--blue-dark);
        border: 1px solid #BFDBFE;
        border-radius: 7px;
        font-size: .78rem;
        font-weight: 700;
        text-decoration: none;
        transition: background .2s
    }

    .btn-edit:hover {
        background: #DBEAFE
    }

    .btn-del {
        padding: 5px 12px;
        background: #FEF2F2;
        color: var(--red);
        border: 1px solid #FECACA;
        border-radius: 7px;
        font-size: .78rem;
        font-weight: 700;
        text-decoration: none;
        transition: background .2s
    }

    .btn-del:hover {
        background: #FEE2E2
    }

    .empty {
        text-align: center;
        padding: 40px;
        color: var(--muted);
        font-size: .9rem
    }
    </style>
</head>

<body>
    
    <?php
$active_page = 'categories';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

    <main class="main">
        <div class="page-header">
            <h1><?= is_admin() ? 'Kelola Kategori' : 'Kategori Buku' ?></h1>
            <p><?= is_admin() ? 'Tambah, edit, dan hapus kategori buku perpustakaan.' : 'Daftar kategori buku yang tersedia di perpustakaan.' ?>
            </p>
        </div>
        <div class="content">
            <?php if ($msg):  ?><div class="alert ok">✅ <?= htmlspecialchars($msg)   ?></div><?php endif; ?>
            <?php if ($error):?><div class="alert err">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

            <?php if (is_admin()): ?>
            <div class="form-card">
                <h2>+ Tambah Kategori Baru</h2>
                <form method="POST" action="create.php">
                    <div class="grid2">
                        <div class="fg">
                            <label>Kode Kategori</label>
                            <input type="text" name="kode" placeholder="Contoh: FIK, SCI, HIS" required>
                        </div>
                        <div class="fg">
                            <label>Nama Kategori</label>
                            <input type="text" name="nama" placeholder="Contoh: Fiksi, Sains, Sejarah" required>
                        </div>
                        <div class="fg full">
                            <label>Deskripsi</label>
                            <textarea name="deskripsi" placeholder="Deskripsi singkat kategori (opsional)"></textarea>
                        </div>
                    </div>
                    <div style="margin-top:20px">
                        <button type="submit" class="btn-primary">+ Tambah Kategori</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="table-card">
                <div class="table-head">
                    <h2>Daftar Kategori (<?= $total ?>)</h2>
                    <form method="GET" class="search-form">
                        <input type="text" name="q" placeholder="Cari kode / nama…"
                            value="<?= htmlspecialchars($search) ?>">
                        <button type="submit">Cari</button>
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Kode</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th>Total Buku</th>
                            <?php if (is_admin()): ?><th>Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total === 0): ?>
                        <tr>
                            <td colspan="6" class="empty">Tidak ada kategori ditemukan.</td>
                        </tr>
                        <?php else:
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($data)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><code
                                    style="background:#EFF6FF;color:var(--blue-dark);padding:2px 8px;border-radius:6px;font-size:.78rem;font-weight:700"><?= htmlspecialchars($row['kode']) ?></code>
                            </td>
                            <td style="font-weight:600"><?= htmlspecialchars($row['nama']) ?></td>
                            <td style="color:var(--muted)"><?= htmlspecialchars($row['deskripsi'] ?: '-') ?></td>
                            <td><span class="badge-buku"><?= $row['total_buku'] ?> buku</span></td>
                            <?php if (is_admin()): ?>
                            <td>
                                <div class="actions">
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn-edit">Edit</a>
                                    <a href="delete.php?id=<?= $row['id'] ?>" class="btn-del"
                                        onclick="return confirm('Hapus kategori &quot;<?= htmlspecialchars($row['nama']) ?>&quot;?')">
                                        Hapus
                                    </a>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script src="/LITERA-app/assets/sidebar-drag.js"></script>
</body>

</html>