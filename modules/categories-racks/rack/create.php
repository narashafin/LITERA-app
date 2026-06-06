<?php

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth_helper.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php");
    exit;
}

$kode_rak = mysqli_real_escape_string(
    $conn,
    trim($_POST['kode_rak'])
);

$nama = mysqli_real_escape_string(
    $conn,
    trim($_POST['nama'])
);

$lokasi = mysqli_real_escape_string(
    $conn,
    trim($_POST['lokasi'])
);

$kapasitas = (int)$_POST['kapasitas'];

$deskripsi = mysqli_real_escape_string(
    $conn,
    trim($_POST['deskripsi'])
);

$query = "
INSERT INTO racks
(
    kode_rak,
    nama,
    lokasi,
    kapasitas,
    deskripsi
)
VALUES
(
    '$kode_rak',
    '$nama',
    '$lokasi',
    $kapasitas,
    '$deskripsi'
)
";

mysqli_query($conn, $query);

header("Location: index.php");
exit;