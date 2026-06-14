<?php
require_once '../includes/config.php';
require_once '../includes/auth_helper.php';
require_admin(); // 🔐 Hanya admin

$msg   = '';
$error = '';
$edit  = null;

// ─── Ambil semua role untuk dropdown ───────────────────────
$roles_res = mysqli_query($conn, "SELECT * FROM roles ORDER BY id");
$roles_arr = [];
while ($r = mysqli_fetch_assoc($roles_res)) {
    $roles_arr[$r['id']] = $r['nama_role'];
}

// ─── CREATE ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $nama      = trim($_POST['nama']     ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email']    ?? '');
    $password  = $_POST['password']      ?? '';
    $no_hp     = trim($_POST['no_hp']    ?? '');
    $role_id   = (int)($_POST['role_id'] ?? 2);
    $status    = in_array($_POST['status'] ?? '', ['aktif','nonaktif']) ? $_POST['status'] : 'aktif';

    if (empty($nama) || empty($username) || empty($email) || empty($password)) {
        $error = 'Nama, username, email, dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($username) < 4 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username minimal 4 karakter, hanya huruf/angka/underscore.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif (!array_key_exists($role_id, $roles_arr)) {
        $error = 'Role tidak valid.';
    } else {
        $u = mysqli_real_escape_string($conn, $username);
        $e = mysqli_real_escape_string($conn, $email);
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$u' OR email='$e'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Username atau email sudah digunakan.';
        } else {
            $n   = mysqli_real_escape_string($conn, $nama);
            $hp  = mysqli_real_escape_string($conn, $no_hp);
            $pw  = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_BCRYPT));
            $st  = mysqli_real_escape_string($conn, $status);
            mysqli_query($conn,
                "INSERT INTO users (role_id, nama, username, email, password, no_hp, status)
                 VALUES ($role_id, '$n', '$u', '$e', '$pw', '$hp', '$st')"
            );
            $msg = 'User berhasil ditambahkan.';
        }
    }
}

// ─── UPDATE ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id       = (int)($_POST['id']       ?? 0);
    $nama     = trim($_POST['nama']      ?? '');
    $username = trim($_POST['username']  ?? '');
    $email    = trim($_POST['email']     ?? '');
    $no_hp    = trim($_POST['no_hp']     ?? '');
    $role_id  = (int)($_POST['role_id']  ?? 2);
    $status   = in_array($_POST['status'] ?? '', ['aktif','nonaktif']) ? $_POST['status'] : 'aktif';
    $password = $_POST['password'] ?? '';

    if (empty($nama) || empty($username) || empty($email)) {
        $error = 'Nama, username, dan email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (!array_key_exists($role_id, $roles_arr)) {
        $error = 'Role tidak valid.';
    } else {
        $n  = mysqli_real_escape_string($conn, $nama);
        $u  = mysqli_real_escape_string($conn, $username);
        $e  = mysqli_real_escape_string($conn, $email);
        $hp = mysqli_real_escape_string($conn, $no_hp);
        $st = mysqli_real_escape_string($conn, $status);

        $check = mysqli_query($conn,
            "SELECT id FROM users WHERE (username='$u' OR email='$e') AND id!=$id");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Username atau email sudah dipakai user lain.';
        } else {
            $pw_clause = '';
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $error = 'Password baru minimal 6 karakter.';
                    goto selesai;
                }
                $ph = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_BCRYPT));
                $pw_clause = ", password='$ph'";
            }
            mysqli_query($conn,
                "UPDATE users
                 SET role_id=$role_id, nama='$n', username='$u',
                     email='$e', no_hp='$hp', status='$st'$pw_clause
                 WHERE id=$id"
            );
            $msg = 'Data user berhasil diperbarui.';
        }
    }
    selesai:
}

// ─── DELETE ─────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id === (int)$_SESSION['user_id']) {
        $error = 'Tidak bisa menghapus akun yang sedang login.';
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id=$del_id");
        $msg = 'User berhasil dihapus.';
    }
}

// ─── EDIT FORM ───────────────────────────────────────────────
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id=$eid LIMIT 1");
    if ($res && mysqli_num_rows($res)) {
        $edit = mysqli_fetch_assoc($res);
    }
}

// ─── READ + SEARCH ───────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$sq     = mysqli_real_escape_string($conn, $search);
$where  = $search
    ? "WHERE u.nama LIKE '%$sq%' OR u.username LIKE '%$sq%' OR u.email LIKE '%$sq%'"
    : '';

$users_res = mysqli_query($conn,
    "SELECT u.*, r.nama_role
     FROM users u
     JOIN roles r ON u.role_id = r.id
     $where
     ORDER BY u.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola User Litera</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap');
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --sb:#C9D8E8;--sb-w:245px;
  --sidebar-bg : #C9D8E8;
  --sidebar-w  : 240px;
  --blue-dark  : #2563EB;
  --navy:#1E3A5F;--blue:#2563EB;--sky:#3B82F6;
  --pale:#EEF3FF;--muted:#64748B;--red:#EF4444;
  --bg:#E8EEF4;--white:#fff;
}
body{font-family:'Nunito',sans-serif;background:var(--bg);min-height:100vh;display:flex}

/* ════ SIDEBAR ════ */
.sidebar {
    width: var(--sidebar-w);
    height: 100vh;
    background: var(--sidebar-bg);
    border-radius: 0 24px 24px 0;
    display: flex;
    flex-direction: column;
    padding-bottom: 24px;
    position: fixed;
    top: 0; left: 0;
    z-index: 100;
    box-shadow: 2px 0 16px rgba(30,58,95,.08);
    overflow-y: auto;
}

.sidebar-logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 28px 16px 20px;
    border-bottom: 1px solid rgba(30,58,95,.12);
}

.sidebar-logo img {
    width: 90px;
    height: 90px;
    object-fit: contain;
}

.sidebar-logo .logo-text {
    font-size: 1.25rem;
    font-weight: 800;
    letter-spacing: 4px;
    background: linear-gradient(90deg,#4ecdc4,#45b7d1,#96c93d,#f7971e,#f9d62e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-top: 4px;
}

.sidebar-nav {
    flex: 1;
    padding: 16px 0;
    overflow-y: auto;
}

.nav-group-label {
    font-size: .68rem;
    font-weight: 800;
    color: var(--navy);
    letter-spacing: 1.4px;
    text-transform: uppercase;
    padding: 14px 24px 6px;
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
    position: relative;
}

.nav-item:hover {
    background: rgba(37,99,235,.1);
    color: var(--blue-dark);
    font-weight: 600;
}

.nav-item.active {
    background: #ffffff;
    color: var(--blue-dark);
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(37,99,235,.12);
}

.nav-item.active::before {
    content: '';
    position: absolute;
    left: 0; top: 6px; bottom: 6px;
    width: 3px;
    background: var(--blue-dark);
    border-radius: 0 3px 3px 0;
}

.sidebar-footer { padding: 0 16px; margin-top: 8px; }

.btn-logout {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 10px 16px;
    background: rgba(239,68,68,.12);
    color: #DC2626;
    border: none;
    border-radius: 12px;
    font-family: 'Nunito', sans-serif;
    font-size: .85rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: background .2s;
}
.btn-logout:hover { background: rgba(239,68,68,.22); }

/* ── MAIN ── */
.main{margin-left:var(--sidebar-w);flex:1;padding:32px 36px;min-height:100vh}
.page{} /* keep class for compat */
h1{font-size:1.75rem;font-weight:700;color:var(--navy);margin-bottom:4px}
.sub{color:var(--muted);font-size:.875rem;margin-bottom:28px}

/* Alert */
.alert{padding:12px 18px;border-radius:10px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.ok {background:#F0FDF4;border:1px solid #BBF7D0;color:#16A34A}
.err{background:#FEF2F2;border:1px solid #FECACA;color:var(--red)}

/* Form card */
.form-card{background:#fff;border-radius:16px;padding:28px 32px;
  border:1px solid #E2ECF8;box-shadow:0 2px 12px rgba(11,45,110,.06);margin-bottom:28px}
.form-card h2{color:var(--navy);font-size:1.05rem;font-weight:600;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.fg{display:flex;flex-direction:column;gap:5px}
.fg.full{grid-column:span 2}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-top:16px}
label{font-size:.73rem;font-weight:600;color:var(--navy);text-transform:uppercase;letter-spacing:.4px}
input[type=text],input[type=email],input[type=password],input[type=tel],select{
  padding:11px 14px;border:1.5px solid #C7D8F8;border-radius:10px;
  font-family:'Nunito',sans-serif;font-size:.875rem;color:var(--navy);
  background:#fff;outline:none;transition:border-color .2s,box-shadow .2s}
input:focus,select:focus{border-color:var(--sky);box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.form-actions{display:flex;gap:12px;margin-top:20px}
.btn-primary{padding:11px 28px;background:linear-gradient(135deg,var(--blue),var(--sky));
  color:#fff;border:none;border-radius:10px;font-family:'Nunito',sans-serif;
  font-size:.875rem;font-weight:600;cursor:pointer;transition:opacity .2s,transform .15s}
.btn-primary:hover{opacity:.9;transform:translateY(-1px)}
.btn-cancel{padding:11px 22px;background:#F1F5F9;color:var(--navy);border:1px solid #D1DCF8;
  border-radius:10px;font-family:'Nunito',sans-serif;font-size:.875rem;
  font-weight:500;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}

/* Table card */
.table-card{background:#fff;border-radius:16px;border:1px solid #E2ECF8;
  box-shadow:0 2px 12px rgba(11,45,110,.06);overflow:hidden}
.table-head{display:flex;align-items:center;justify-content:space-between;
  padding:20px 28px;border-bottom:1px solid #F1F5F9;flex-wrap:wrap;gap:12px}
.table-head h2{color:var(--navy);font-size:1.05rem;font-weight:600}
.search-form{display:flex;gap:8px}
.search-form input{padding:9px 14px;border:1.5px solid #C7D8F8;border-radius:9px;
  font-family:'Nunito',sans-serif;font-size:.85rem;color:var(--navy);outline:none;width:210px}
.search-form input:focus{border-color:var(--sky)}
.search-form button{padding:9px 18px;background:var(--blue);color:#fff;border:none;
  border-radius:9px;cursor:pointer;font-size:.85rem;font-weight:500}
table{width:100%;border-collapse:collapse}
th{padding:12px 18px;text-align:left;font-size:.74rem;font-weight:700;color:var(--muted);
  text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #F1F5F9;background:#FAFBFF}
td{padding:13px 18px;font-size:.86rem;color:var(--navy);border-bottom:1px solid #F8FAFC;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#FAFBFF}

/* Badges */
.badge{display:inline-block;padding:3px 11px;border-radius:20px;font-size:.7rem;font-weight:700}
.badge.admin  {background:#FCD34D;color:#78350F}
.badge.anggota{background:#DBEAFE;color:#1E40AF}
.badge.aktif  {background:#D1FAE5;color:#065F46}
.badge.nonaktif{background:#FEE2E2;color:#991B1B}
.badge.you    {background:#E9D5FF;color:#6B21A8;margin-left:4px}

/* Action buttons */
.actions{display:flex;gap:7px}
.btn-edit{padding:5px 14px;background:var(--pale);color:var(--blue);border:1px solid #C7D8F8;
  border-radius:7px;font-size:.78rem;font-weight:600;text-decoration:none;transition:background .2s}
.btn-edit:hover{background:#D1DCF8}
.btn-del{padding:5px 12px;background:#FEF2F2;color:var(--red);border:1px solid #FECACA;
  border-radius:7px;font-size:.78rem;font-weight:600;text-decoration:none;transition:background .2s}
.btn-del:hover{background:#FEE2E2}

.empty{text-align:center;padding:40px;color:var(--muted);font-size:.9rem}

@media(max-width:768px){
  .grid2,.grid3{grid-template-columns:1fr}
  .fg.full{grid-column:1}
  .sidebar{display:none}
  .main{margin-left:0;padding:20px 16px}
}
</style>
</head>
<body>

<?php
$logo_b64 = '';
$cur = basename($_SERVER['PHP_SELF']);
?>

<!-- ════ SIDEBAR ════ -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/LITERA.png" alt="LITERA Logo">
        <span class="logo-text">LITERA</span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group-label">Main</div>
        <a href="/LITERA-app/dashboard.php" class="nav-item">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
        </a>

        <div class="nav-group-label">Koleksi</div>
        <a href="books.php" class="nav-item <?= $cur==='books.php'?'active':'' ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            Buku
        </a>
        <a href="/LITERA-app/modules/categories-racks/category/index.php" class="nav-item">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            Kategori
        </a>
        <a href="/LITERA-app/modules/categories-racks/rack/index.php" class="nav-item">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="5" rx="1"/><rect x="2" y="10" width="20" height="5" rx="1"/><rect x="2" y="17" width="20" height="5" rx="1"/></svg>
            Rak Buku
        </a>

        <div class="nav-group-label">Transaksi</div>
        <a href="borrowings.php" class="nav-item <?= $cur==='borrowings.php'?'active':'' ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Peminjaman
        </a>
        <a href="returns.php" class="nav-item <?= $cur==='returns.php'?'active':'' ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
            Pengembalian
        </a>
        <a href="fines.php" class="nav-item <?= $cur==='fines.php'?'active':'' ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Denda
        </a>

        <?php if (is_admin()): ?>
        <div class="nav-group-label">Manajemen</div>
        <a href="users.php" class="nav-item <?= $cur==='users.php'?'active':'' ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Pengguna
        </a>
        <a href="reports.php" class="nav-item <?= $cur==='reports.php'?'active':'' ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Laporan
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="btn-logout">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </div>
</aside>

<!-- ════ MAIN ════ -->
<main class="main">
<div class="page">
  <h1>Kelola Pengguna</h1>
  <p class="sub">Tambah, edit, dan hapus data pengguna sistem Litera.</p>

  <?php if ($msg):  ?><div class="alert ok" ><?= htmlspecialchars($msg)   ?></div><?php endif; ?>
  <?php if ($error):?><div class="alert err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- ── FORM TAMBAH / EDIT ── -->
  <div class="form-card">
    <h2><?= $edit ? 'Edit User' : 'Tambah User Baru' ?></h2>
    <form method="POST">
      <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
      <?php if ($edit): ?>
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
      <?php endif; ?>

      <div class="grid2">
        <div class="fg">
          <label>Nama Lengkap</label>
          <input type="text" name="nama" placeholder="Nama lengkap"
                 value="<?= htmlspecialchars($edit ? $edit['nama'] : ($_POST['nama'] ?? '')) ?>">
        </div>
        <div class="fg">
          <label>Username</label>
          <input type="text" name="username" placeholder="username (min. 4 karakter)"
                 value="<?= htmlspecialchars($edit ? $edit['username'] : ($_POST['username'] ?? '')) ?>">
        </div>
        <div class="fg">
          <label>Email</label>
          <input type="email" name="email" placeholder="email@contoh.com"
                 value="<?= htmlspecialchars($edit ? $edit['email'] : ($_POST['email'] ?? '')) ?>">
        </div>
        <div class="fg">
          <label>No. HP</label>
          <input type="tel" name="no_hp" placeholder="08xx-xxxx-xxxx"
                 value="<?= htmlspecialchars($edit ? $edit['no_hp'] : ($_POST['no_hp'] ?? '')) ?>">
        </div>
      </div>

      <div class="grid3">
        <div class="fg">
          <label>Role</label>
          <select name="role_id">
            <?php foreach ($roles_arr as $rid => $rname): ?>
              <option value="<?= $rid ?>"
                <?= ($edit && (int)$edit['role_id'] === $rid) ? 'selected' : '' ?>>
                <?= htmlspecialchars($rname) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label>Status</label>
          <select name="status">
            <option value="aktif"    <?= ($edit && $edit['status']==='aktif')    ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= ($edit && $edit['status']==='nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>
        <div class="fg">
          <label>Password <?= $edit ? '(kosongkan jika tidak diubah)' : '' ?></label>
          <input type="password" name="password"
                 placeholder="<?= $edit ? 'Password baru (opsional)' : 'Min. 6 karakter' ?>">
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-primary">
          <?= $edit ? 'Simpan Perubahan' : 'Tambah User' ?>
        </button>
        <?php if ($edit): ?>
          <a href="users.php" class="btn-cancel">Batal</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- ── TABEL USER ── -->
  <div class="table-card">
    <div class="table-head">
      <h2>Daftar Pengguna (<?= mysqli_num_rows($users_res) ?>)</h2>
      <form method="GET" class="search-form">
        <input type="text" name="q" placeholder="Cari nama / username / email…"
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Cari</button>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nama</th>
          <th>Username</th>
          <th>Email</th>
          <th>No. HP</th>
          <th>Role</th>
          <th>Status</th>
          <th>Dibuat</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($users_res) === 0): ?>
          <tr><td colspan="9" class="empty">Tidak ada pengguna ditemukan.</td></tr>
        <?php else:
          $no = 1;
          while ($u = mysqli_fetch_assoc($users_res)):
            $is_me = $u['id'] == $_SESSION['user_id'];
            $role_slug = $u['role_id'] == 1 ? 'admin' : 'anggota';
        ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($u['nama']) ?></td>
            <td>
              @<?= htmlspecialchars($u['username']) ?>
              <?php if ($is_me): ?><span class="badge you">Anda</span><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['no_hp'] ?: '-') ?></td>
            <td><span class="badge <?= $role_slug ?>"><?= htmlspecialchars($u['nama_role']) ?></span></td>
            <td><span class="badge <?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td>
              <div class="actions">
                <a href="users.php?edit=<?= $u['id'] ?>" class="btn-edit">Edit</a>
                <?php if (!$is_me): ?>
                  <a href="users.php?delete=<?= $u['id'] ?>"
                     class="btn-del"
                     onclick="return confirm('Hapus user &quot;<?= htmlspecialchars($u['username']) ?>&quot;?')">
                    Hapus
                  </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
  </div>
</main>
</body>
</html>