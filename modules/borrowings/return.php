<?php
require_once '../../includes/config.php';
require_once '../../includes/auth_helper.php';
require_login();

if (!is_admin()) exit();

$id = intval($_GET['id'] ?? 0);
if ($id == 0) {
    header("Location: index.php?error=Invalid ID");
    exit();
}

// Logic pengembalian + hitung denda
header("Location: index.php?msg=Fitur pengembalian sedang dikembangkan");