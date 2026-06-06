<?php

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth_helper.php';

require_admin();

$nama = trim($_POST['nama']);
$kode = trim($_POST['kode']);
$deskripsi = trim($_POST['deskripsi']);

$sql = "INSERT INTO categories
(nama,kode,deskripsi)
VALUES
('$nama','$kode','$deskripsi')";

mysqli_query($conn,$sql);

header("Location: index.php");
exit;