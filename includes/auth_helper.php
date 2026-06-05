<?php
// includes/auth_helper.php

/**
 * Cek apakah user sudah login
 */
function is_logged_in(): bool {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Redirect ke login jika belum login
 * Panggil di baris paling atas setiap halaman yang butuh autentikasi
 */
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ../auth/login.php');
        exit();
    }
}

/**
 * Hanya admin (role_id = 1) yang boleh akses
 */
function require_admin(): void {
    require_login();
    if ((int)$_SESSION['role_id'] !== 1) {
        header('Location: ../pages/dashboard.php?error=forbidden');
        exit();
    }
}

/**
 * Ambil data user dari session sebagai array
 */
function current_user(): array {
    return [
        'id'         => $_SESSION['user_id']   ?? null,
        'nama'       => $_SESSION['nama']       ?? '',
        'username'   => $_SESSION['username']   ?? '',
        'email'      => $_SESSION['email']      ?? '',
        'role_id'    => $_SESSION['role_id']    ?? 2,
        'nama_role'  => $_SESSION['nama_role']  ?? 'anggota',
        'status'     => $_SESSION['status']     ?? 'aktif',
    ];
}

/**
 * Cek apakah user adalah admin (role_id = 1)
 */
function is_admin(): bool {
    return is_logged_in() && (int)$_SESSION['role_id'] === 1;
}