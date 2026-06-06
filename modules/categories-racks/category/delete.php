<?php

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth_helper.php';

require_admin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET['id'];

$cek = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM books
     WHERE category_id = $id"
);

$data = mysqli_fetch_assoc($cek);

if ($data['total'] > 0) {

    echo "
    <script>
        alert('Kategori tidak dapat dihapus karena masih digunakan oleh {$data['total']} buku.');
        window.location='index.php';
    </script>
    ";

    exit;
}

$hapus = mysqli_query(
    $conn,
    "DELETE FROM categories
     WHERE id = $id"
);

if ($hapus) {

    echo "
    <script>
        alert('Kategori berhasil dihapus.');
        window.location='index.php';
    </script>
    ";

} else {

    echo "
    <script>
        alert('Gagal menghapus kategori.');
        window.location='index.php';
    </script>
    ";

}