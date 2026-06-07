<?php
require_once '../../../includes/config.php';
require_once '../../../includes/auth_helper.php';
require_login();

$id = intval($_GET['id'] ?? 0);
if ($id == 0) {
    header("Location: index.php?error=Data tidak ditemukan");
    exit();
}

$borrowing = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT b.*, u.nama as nama_user, a.nama as nama_admin 
    FROM borrowings b 
    JOIN users u ON b.user_id = u.id 
    LEFT JOIN users a ON b.admin_id = a.id 
    WHERE b.id = $id
"));

$details = mysqli_query($conn, "
    SELECT bd.*, bk.judul, bk.penulis 
    FROM borrowing_details bd 
    JOIN books bk ON bd.book_id = bk.id 
    WHERE bd.borrowing_id = $id
");
?>


<div class="content">
    <div class="table-card">
        <div class="table-head">
            <h2>Detail Peminjaman #<?= htmlspecialchars($borrowing['kode_pinjam']) ?></h2>
        </div>
        <!-- Tampilkan informasi peminjaman -->
    </div>
</div>