<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth_helper.php';
require_admin();
require_once __DIR__ . '/../return_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

try {
    $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (empty($_POST['borrowing_id']) || empty($_POST['tanggal_kembali']) || empty($_POST['kondisi_buku'])) {
        throw new Exception("Data tidak lengkap.");
    }

    $borrowing_id   = intval($_POST['borrowing_id']);
    $tanggal_kembali = $_POST['tanggal_kembali'];
    $kondisi_buku   = $_POST['kondisi_buku'];
    $catatan        = $_POST['catatan'] ?? '';
    $admin_id       = (int)$_SESSION['user_id'];

    $helper = new ReturnHelper($db);
    $helper->validateBorrowingStatus($borrowing_id);
    $helper->checkReturnExists($borrowing_id);

    $borrowing = $helper->getBorrowingData($borrowing_id);
    if (!$borrowing) throw new Exception("Data peminjaman tidak ditemukan.");

    $borrowing_details = $helper->getAllBorrowingDetails($borrowing_id);
    if (empty($borrowing_details)) throw new Exception("Detail peminjaman tidak ditemukan.");

    $denda_info = $helper->hitungDenda($borrowing['tanggal_kembali'], $tanggal_kembali);

    $db->beginTransaction();
    $helper->saveReturnData($borrowing_id, $tanggal_kembali, $kondisi_buku, $catatan, $admin_id);
    $helper->saveFineData($borrowing_id, $borrowing['user_id'], $denda_info);
    $helper->updateBorrowingStatus($borrowing_id);
    $helper->updateAllStok($borrowing_details);
    $db->commit();

    header('Location: index.php?msg=Pengembalian berhasil dicatat');
    exit();

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit();
}
