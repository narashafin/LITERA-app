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
<title>Kelola User — Litera</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0B2D6E;--blue:#1A4DB5;--sky:#3B82F6;--pale:#EEF3FF;--muted:#64748B;--red:#EF4444}
body{font-family:'DM Sans',sans-serif;background:#F1F5F9;min-height:100vh}
.page{padding:32px 40px}
h1{font-family:'Playfair Display',serif;font-size:1.85rem;color:var(--navy);margin-bottom:4px}
.sub{color:var(--muted);font-size:.875rem;margin-bottom:28px}

/* Alert */
.alert{padding:12px 18px;border-radius:10px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.ok {background:#F0FDF4;border:1px solid #BBF7D0;color:#16A34A}
.err{background:#FEF2F2;border:1px solid #FECACA;color:var(--red)}

/* Form card */
.form-card{background:#fff;border-radius:16px;padding:28px 32px;
  border:1px solid #E2ECF8;box-shadow:0 2px 12px rgba(11,45,110,.06);margin-bottom:28px}
.form-card h2{font-family:'Playfair Display',serif;color:var(--navy);font-size:1.15rem;margin-bottom:20px}
.fg{display:flex;flex-direction:column;gap:5px}
.fg.full{grid-column:span 2}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-top:16px}
label{font-size:.76rem;font-weight:600;color:var(--navy);text-transform:uppercase;letter-spacing:.4px}
input[type=text],input[type=email],input[type=password],input[type=tel],select{
  padding:11px 14px;border:2px solid #C7D8F8;border-radius:10px;
  font-family:'DM Sans',sans-serif;font-size:.88rem;color:var(--navy);
  background:#fff;outline:none;transition:border-color .2s,box-shadow .2s}
input:focus,select:focus{border-color:var(--sky);box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.form-actions{display:flex;gap:12px;margin-top:20px}
.btn-primary{padding:11px 28px;background:linear-gradient(135deg,var(--blue),var(--sky));
  color:#fff;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;
  font-size:.9rem;font-weight:600;cursor:pointer;transition:opacity .2s,transform .15s}
.btn-primary:hover{opacity:.9;transform:translateY(-1px)}
.btn-cancel{padding:11px 22px;background:#F1F5F9;color:var(--navy);border:1px solid #D1DCF8;
  border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.88rem;
  font-weight:500;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}

/* Table card */
.table-card{background:#fff;border-radius:16px;border:1px solid #E2ECF8;
  box-shadow:0 2px 12px rgba(11,45,110,.06);overflow:hidden}
.table-head{display:flex;align-items:center;justify-content:space-between;
  padding:20px 28px;border-bottom:1px solid #F1F5F9;flex-wrap:wrap;gap:12px}
.table-head h2{font-family:'Playfair Display',serif;color:var(--navy);font-size:1.1rem}
.search-form{display:flex;gap:8px}
.search-form input{padding:9px 14px;border:2px solid #C7D8F8;border-radius:9px;
  font-family:'DM Sans',sans-serif;font-size:.85rem;color:var(--navy);outline:none;width:210px}
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
  .page{padding:20px 16px}
}
</style>
</head>
<body>
<?php require_once '../includes/navbar.php'; ?>

<div class="page">
  <h1>Kelola Pengguna</h1>
  <p class="sub">Tambah, edit, dan hapus data pengguna sistem Litera.</p>

  <?php if ($msg):  ?><div class="alert ok" >✅ <?= htmlspecialchars($msg)   ?></div><?php endif; ?>
  <?php if ($error):?><div class="alert err">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- ── FORM TAMBAH / EDIT ── -->
  <div class="form-card">
    <h2><?= $edit ? '✏️ Edit User' : '➕ Tambah User Baru' ?></h2>
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
          <?= $edit ? '💾 Simpan Perubahan' : '➕ Tambah User' ?>
        </button>
        <?php if ($edit): ?>
          <a href="users.php" class="btn-cancel">✕ Batal</a>
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
        <button type="submit">🔍 Cari</button>
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
                <a href="users.php?edit=<?= $u['id'] ?>" class="btn-edit">✏️ Edit</a>
                <?php if (!$is_me): ?>
                  <a href="users.php?delete=<?= $u['id'] ?>"
                     class="btn-del"
                     onclick="return confirm('Hapus user &quot;<?= htmlspecialchars($u['username']) ?>&quot;?')">
                    🗑️ Hapus
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
</body>
</html>