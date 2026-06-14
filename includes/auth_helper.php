<?php
// includes/auth_helper.php

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /LITERA-app/modules/users-auth/login.php');
        exit();
    }
}

function require_admin(): void {
    require_login();

    if ((int)$_SESSION['role_id'] !== 1) {
        header('Location: /LITERA-app/dashboard.php?error=forbidden');
        exit();
    }
}

function current_user(): array {
    return [
        'id'         => $_SESSION['user_id'] ?? null,
        'nama'       => $_SESSION['nama'] ?? '',
        'username'   => $_SESSION['username'] ?? '',
        'email'      => $_SESSION['email'] ?? '',
        'role_id'    => $_SESSION['role_id'] ?? 2,
        'nama_role'  => $_SESSION['nama_role'] ?? 'anggota',
        'status'     => $_SESSION['status'] ?? 'aktif',
    ];
}

function is_admin(): bool {
    return is_logged_in() && (int)$_SESSION['role_id'] === 1;
}