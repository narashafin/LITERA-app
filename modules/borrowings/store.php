<?php
require_once '../../includes/config.php';
require_once '../../includes/auth_helper.php';
require_login();

if (($_SESSION['role_id'] ?? 0) != 1) {
    header("Location: index.php?error=Unauthorized");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id        = intval($_POST['user_id']);
    $tanggal_pinjam = mysqli_real_escape_string($conn, $_POST['tanggal_pinjam']);
    $catatan        = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');
    $book_ids       = $_POST['book_ids'] ?? [];

    if (empty($book_ids) || $user_id <= 0) {
        header("Location: create.php?error=Data tidak lengkap");
        exit();
    }

    // Generate kode pinjam dengan format PJM001, PJM002, dst.
    $last = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT kode_pinjam FROM borrowings WHERE kode_pinjam LIKE 'PJM%' ORDER BY id DESC LIMIT 1"));
    $last_num = $last ? intval(substr($last['kode_pinjam'], 3)) : 0;
    $kode_pinjam = 'PJM' . str_pad($last_num + 1, 3, '0', STR_PAD_LEFT);

    $query = "INSERT INTO borrowings (kode_pinjam, user_id, admin_id, tanggal_pinjam, tanggal_kembali, status, catatan) 
              VALUES ('$kode_pinjam', $user_id, {$_SESSION['user_id']}, '$tanggal_pinjam', 
                      DATE_ADD('$tanggal_pinjam', INTERVAL 7 DAY), 'dipinjam', '$catatan')";

    if (mysqli_query($conn, $query)) {
        $borrowing_id = mysqli_insert_id($conn);

        foreach ($book_ids as $book_id) {
            $book_id = intval($book_id);
            mysqli_query($conn, "INSERT INTO borrowing_details (borrowing_id, book_id, jumlah) VALUES ($borrowing_id, $book_id, 1)");
            
            // Kurangi stok tersedia
            mysqli_query($conn, "UPDATE books SET stok_tersedia = stok_tersedia - 1 WHERE id = $book_id");
        }

        header("Location: index.php?msg=Peminjaman berhasil dibuat! Kode: $kode_pinjam");
    } else {
        header("Location: create.php?error=Gagal menyimpan data");
    }
    exit();
}