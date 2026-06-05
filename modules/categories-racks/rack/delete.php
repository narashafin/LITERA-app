<?php

require_once '../../includes/config.php';
require_once '../../includes/auth_helper.php';

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
     WHERE rack_id = $id"
);

$data = mysqli_fetch_assoc($cek);

if ($data['total'] > 0) {

    echo "
    <script>
        alert('Rak tidak dapat dihapus karena masih berisi {$data['total']} buku.');
        window.location='index.php';
    </script>
    ";

    exit;
}

$hapus = mysqli_query(
    $conn,
    "DELETE FROM racks
     WHERE id = $id"
);

if ($hapus) {

    echo "
    <script>
        alert('Rak berhasil dihapus.');
        window.location='index.php';
    </script>
    ";

} else {

    echo "
    <script>
        alert('Gagal menghapus rak.');
        window.location='index.php';
    </script>
    ";

}