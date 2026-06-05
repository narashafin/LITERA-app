<?php
// includes/config.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'litera_db');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('<div style="font-family:sans-serif;padding:30px;color:#dc2626;">
        <h2>❌ Koneksi Database Gagal</h2>
        <p>' . mysqli_connect_error() . '</p>
    </div>');
}

mysqli_set_charset($conn, 'utf8mb4');