<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_login();

$msg   = $_GET['msg']   ?? '';
$error = $_GET['error'] ?? '';

$search = trim($_GET['q']    ?? '');
$cat_f  = (int)($_GET['cat'] ?? 0);
$sq     = mysqli_real_escape_string($conn, $search);

$conditions = [];
if ($search) $conditions[] = "(b.judul LIKE '%$sq%' OR b.penulis LIKE '%$sq%' OR b.isbn LIKE '%$sq%')";
if ($cat_f)  $conditions[] = "b.category_id = $cat_f";
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$books_res = mysqli_query($conn,
    "SELECT b.*, c.nama AS nama_kategori, r.nama AS nama_rak
     FROM books b
     LEFT JOIN categories c ON b.category_id = c.id
     LEFT JOIN racks r ON b.rack_id = r.id
     $where
     ORDER BY b.created_at DESC"
);
$total_buku = mysqli_num_rows($books_res);

$cats_res = mysqli_query($conn, "SELECT id, nama FROM categories ORDER BY nama ASC");
$cats = [];
while ($r = mysqli_fetch_assoc($cats_res)) $cats[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= is_admin() ? 'Manajemen Buku' : 'Katalog Buku' ?> LITERA</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/app.css">
<style>
.table-head-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.filter-form select{padding:9px 14px;border:2px solid #C7D8F8;border-radius:9px;font-family:'Nunito',sans-serif;font-size:.85rem;color:var(--navy);outline:none;cursor:pointer;background:#fff}
.filter-form select:focus{border-color:var(--blue-dark)}
.btn-tambah{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:linear-gradient(135deg,var(--navy),var(--blue-dark));color:#fff;text-decoration:none;border-radius:10px;font-size:.85rem;font-weight:700;font-family:'Nunito',sans-serif;transition:opacity .2s}
.btn-tambah:hover{opacity:.9}
.book-cover-thumb{width:44px;height:60px;object-fit:cover;border-radius:6px;background:#D6E4F0;display:block}
.cover-placeholder{width:44px;height:60px;border-radius:6px;background:linear-gradient(135deg,#C9D8E8,#A8C3DB);display:flex;align-items:center;justify-content:center;color:#7A9ABB;flex-shrink:0}
.badge-stok{display:inline-block;padding:3px 11px;border-radius:20px;font-size:.72rem;font-weight:700}
.stok-ok{background:#DCFCE7;color:#166534;border:1px solid #BBF7D0}
.stok-low{background:#FEF3C7;color:#92400E;border:1px solid #FDE68A}
.stok-habis{background:#FEE2E2;color:#991B1B;border:1px solid #FECACA}
.badge-cat{display:inline-block;padding:3px 11px;border-radius:20px;font-size:.72rem;font-weight:700;background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE}
.view-toggle{display:flex;gap:6px}
.btn-view{padding:7px 12px;border:2px solid #C7D8F8;border-radius:8px;background:#fff;cursor:pointer;color:var(--muted);transition:all .2s;display:flex;align-items:center;gap:4px;font-size:.78rem;font-weight:700;font-family:'Nunito',sans-serif}
.btn-view.active,.btn-view:hover{border-color:var(--blue-dark);color:var(--blue-dark);background:#EFF6FF}
.books-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:20px;padding:24px}
.book-card-grid{background:#fff;border:1px solid #E2ECF8;border-radius:14px;overflow:hidden;transition:transform .2s,box-shadow .2s}
.book-card-grid:hover{transform:translateY(-4px);box-shadow:0 12px 30px rgba(30,58,95,.12)}
.book-card-grid .cover{width:100%;height:200px;object-fit:cover;display:block;background:#D6E4F0}
.book-card-grid .cover-ph{width:100%;height:200px;background:linear-gradient(135deg,#C9D8E8,#A8C3DB);display:flex;align-items:center;justify-content:center;color:#7A9ABB}
.book-card-grid .info{padding:14px}
.book-card-grid .title{font-size:.88rem;font-weight:700;color:var(--navy);margin-bottom:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.book-card-grid .author{font-size:.75rem;color:var(--muted);font-weight:500}
.book-card-grid .footer{padding:0 14px 12px;display:flex;align-items:center;justify-content:space-between}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(2px);z-index:999;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal-box{background:#fff;border-radius:20px;padding:32px 28px;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(30,58,95,.18);text-align:center}
.modal-icon{width:52px;height:52px;background:#FEF2F2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
.modal-box h3{font-size:1.05rem;font-weight:800;color:var(--navy);margin-bottom:8px}
.modal-box p{font-size:.875rem;color:var(--muted);line-height:1.5;margin-bottom:24px}
.modal-actions{display:flex;gap:10px;justify-content:center}
.modal-btn-cancel{padding:10px 24px;background:#F1F5F9;color:var(--muted);border:none;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.875rem;font-weight:700;cursor:pointer}
.modal-btn-cancel:hover{background:#E2E8F0}
.modal-btn-del{padding:10px 24px;background:#EF4444;color:#fff;border:none;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.875rem;font-weight:700;cursor:pointer;transition:opacity .2s}
.modal-btn-del:hover{opacity:.88}
@media(max-width:640px){.books-grid{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>

<?php
$active_page = 'books';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="modal-overlay" id="modalHapus">
    <div class="modal-box">
        <div class="modal-icon">
            <svg width="22" height="22" fill="none" stroke="#EF4444" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
        </div>
        <h3>Hapus Buku?</h3>
        <p>Tindakan ini dapat membuat buku dihapus secara permanen dari perpustakaan.</p>
        <div class="modal-actions">
            <button class="modal-btn-cancel" onclick="tutupModal()">Batal</button>
            <a href="#" id="modalHapusLink" class="modal-btn-del">Ya, Hapus</a>
        </div>
    </div>
</div>

<main class="main">
    <div class="page-header">
        <h1><?= is_admin() ? 'Manajemen Buku' : 'Katalog Buku' ?></h1>
        <p><?= is_admin() ? 'Tambah, edit, kelola stok, dan hapus data buku perpustakaan.' : 'Jelajahi koleksi buku yang tersedia di perpustakaan LITERA.' ?></p>
    </div>

    <div class="content">
        <?php if ($msg): ?><div class="alert ok">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert err">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="table-card">
            <div class="table-head">
                <h2>
                    <?= is_admin() ? 'Daftar Buku' : 'Koleksi Buku' ?>
                    <span style="font-size:.78rem;font-weight:600;color:var(--muted);margin-left:8px">(<?= $total_buku ?>)</span>
                </h2>
                <div class="table-head-right">
                    <form method="GET" class="filter-form" id="filterForm">
                        <select name="cat" onchange="this.form.submit()">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $cat_f == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nama']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($search): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                    </form>
                    <form method="GET" class="search-form">
                        <input type="text" name="q" placeholder="Cari judul / penulis..." value="<?= htmlspecialchars($search) ?>">
                        <?php if ($cat_f): ?><input type="hidden" name="cat" value="<?= $cat_f ?>"><?php endif; ?>
                        <button type="submit">Cari</button>
                    </form>
                    <?php if (is_admin()): ?>
                    <a href="create.php" class="btn-tambah">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Tambah Buku
                    </a>
                    <?php endif; ?>
                    <?php if (!is_admin()): ?>
                    <div class="view-toggle">
                        <button class="btn-view active" id="btnTable" onclick="setView('table')">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="4"/><rect x="3" y="9" width="18" height="4"/><rect x="3" y="15" width="18" height="4"/></svg>
                            List
                        </button>
                        <button class="btn-view" id="btnGrid" onclick="setView('grid')">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                            Grid
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="viewTable">
            <?php if ($total_buku > 0): ?>
            <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Judul & Penulis</th>
                        <th>Kategori</th>
                        <th>Rak</th>
                        <th>Tahun</th>
                        <th>Stok</th>
                        <?php if (is_admin()): ?><th>Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php while ($b = mysqli_fetch_assoc($books_res)):
                    $stok = (int)$b['stok'];
                    $stok_class = $stok > 3 ? 'stok-ok' : ($stok > 0 ? 'stok-low' : 'stok-habis');
                    $stok_label = $stok > 0 ? "$stok tersedia" : 'Habis';
                ?>
                <tr>
                    <td>
                        <?php if (!empty($b['cover'])): ?>
                            <img src="/LITERA-app/uploads/covers/<?= htmlspecialchars($b['cover']) ?>"
                                 alt="" class="book-cover-thumb"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <div class="cover-placeholder" style="display:none">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            </div>
                        <?php else: ?>
                            <div class="cover-placeholder">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:700;color:var(--navy)"><?= htmlspecialchars($b['judul']) ?></div>
                        <div style="font-size:.78rem;color:var(--muted);margin-top:2px"><?= htmlspecialchars($b['penulis']) ?></div>
                        <?php if (!empty($b['isbn'])): ?>
                        <div style="font-size:.72rem;color:#94A3B8;margin-top:2px">ISBN: <?= htmlspecialchars($b['isbn']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= $b['nama_kategori'] ? '<span class="badge-cat">'.htmlspecialchars($b['nama_kategori']).'</span>' : '<span style="color:#94A3B8">—</span>' ?></td>
                    <td style="color:var(--muted);font-size:.83rem"><?= htmlspecialchars($b['nama_rak'] ?? '—') ?></td>
                    <td style="color:var(--muted);font-size:.83rem"><?= $b['tahun_terbit'] ?: '—' ?></td>
                    <td><span class="badge-stok <?= $stok_class ?>"><?= $stok_label ?></span></td>
                    <?php if (is_admin()): ?>
                    <td>
                        <div class="actions">
                            <a href="edit.php?id=<?= $b['id'] ?>" class="btn-edit">Edit</a>
                            <button class="btn-del" onclick="bukaModal('delete.php?id=<?= $b['id'] ?>')">Hapus</button>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="margin-bottom:12px;opacity:.4"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                <p><?= ($search || $cat_f) ? 'Tidak ada buku yang cocok dengan filter.' : 'Belum ada buku di perpustakaan.' ?></p>
            </div>
            <?php endif; ?>
            </div>

            <?php if (!is_admin()): ?>
            <div id="viewGrid" style="display:none">
            <?php
            $books_grid = mysqli_query($conn,
                "SELECT b.*, c.nama AS nama_kategori FROM books b
                 LEFT JOIN categories c ON b.category_id = c.id $where ORDER BY b.created_at DESC");
            if ($books_grid && mysqli_num_rows($books_grid) > 0):
            ?>
            <div class="books-grid">
            <?php while ($b = mysqli_fetch_assoc($books_grid)):
                $stok = (int)$b['stok'];
                $stok_class = $stok > 3 ? 'stok-ok' : ($stok > 0 ? 'stok-low' : 'stok-habis');
                $stok_label = $stok > 0 ? "$stok tersedia" : 'Habis';
            ?>
            <div class="book-card-grid">
                <?php if (!empty($b['cover'])): ?>
                    <img src="/LITERA-app/uploads/covers/<?= htmlspecialchars($b['cover']) ?>" alt="" class="cover"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="cover-ph" style="display:none"><svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
                <?php else: ?>
                    <div class="cover-ph"><svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
                <?php endif; ?>
                <div class="info">
                    <div class="title"><?= htmlspecialchars($b['judul']) ?></div>
                    <div class="author"><?= htmlspecialchars($b['penulis']) ?></div>
                </div>
                <div class="footer">
                    <?php if ($b['nama_kategori']): ?>
                        <span class="badge-cat" style="font-size:.68rem"><?= htmlspecialchars($b['nama_kategori']) ?></span>
                    <?php else: ?><span></span><?php endif; ?>
                    <span class="badge-stok <?= $stok_class ?>" style="font-size:.68rem"><?= $stok_label ?></span>
                </div>
            </div>
            <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="empty-state"><p>Tidak ada buku yang cocok dengan filter.</p></div>
            <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<script>
function bukaModal(url) {
    document.getElementById('modalHapusLink').href = url;
    document.getElementById('modalHapus').classList.add('active');
}
function tutupModal() {
    document.getElementById('modalHapus').classList.remove('active');
}
document.getElementById('modalHapus').addEventListener('click', function(e) {
    if (e.target === this) tutupModal();
});
function setView(v) {
    document.getElementById('viewTable').style.display = v === 'table' ? 'block' : 'none';
    document.getElementById('viewGrid').style.display  = v === 'grid'  ? 'block' : 'none';
    document.getElementById('btnTable').classList.toggle('active', v === 'table');
    document.getElementById('btnGrid').classList.toggle('active',  v === 'grid');
    localStorage.setItem('bookView', v);
}
(function(){ const v = localStorage.getItem('bookView'); if (v === 'grid') setView('grid'); })();
</script>
</body>
</html>