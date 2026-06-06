<?php
require_once 'return_helper.php';

header('Content-Type: application/json');

try {
    // Koneksi database
    $db = new PDO(
        'mysql:host=localhost;dbname=litera_db;charset=utf8mb4',
        'root',
        ''
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Validasi input
    if (empty($_POST['borrowing_id']) || empty($_POST['tanggal_kembali']) || empty($_POST['kondisi_buku'])) {
        throw new Exception("Data tidak lengkap. Harap isi semua field yang wajib.");
    }

    $borrowing_id = intval($_POST['borrowing_id']);
    $tanggal_kembali = $_POST['tanggal_kembali'];
    $kondisi_buku = $_POST['kondisi_buku'];
    $catatan = $_POST['catatan'] ?? '';

    // Inisialisasi helper
    $helper = new ReturnHelper($db);

    // Validasi status peminjaman (pastikan belum dikembalikan sebelumnya)
    $helper->validateBorrowingStatus($borrowing_id);

    // Validasi sudah ada return record
    $helper->checkReturnExists($borrowing_id);

    // Ambil data peminjaman
    $borrowing = $helper->getBorrowingData($borrowing_id);
    if (!$borrowing) {
        throw new Exception("Data peminjaman tidak ditemukan.");
    }

    // Ambil semua detail peminjaman (bisa lebih dari 1 buku)
    $borrowing_details = $helper->getAllBorrowingDetails($borrowing_id);
    if (empty($borrowing_details)) {
        throw new Exception("Detail peminjaman tidak ditemukan.");
    }

    // Hitung denda
    $denda_info = $helper->hitungDenda($borrowing['tanggal_kembali'], $tanggal_kembali);

    // Mulai transaksi
    $db->beginTransaction();

    // Simpan data pengembalian ke tabel returns
    $return_id = $helper->saveReturnData(
        $borrowing_id, 
        $tanggal_kembali, 
        $kondisi_buku, 
        $catatan,
        null
    );

    // Simpan denda ke tabel fines (HANYA jika ada denda)
    $fine_id = $helper->saveFineData($borrowing_id, $borrowing['user_id'], $denda_info);

    // Update status pengembalian di tabel borrowings
    $helper->updateBorrowingStatus($borrowing_id);

    // Update stok buku untuk semua buku yang dipinjam
    $helper->updateAllStok($borrowing_details);

    // Commit transaksi
    $db->commit();

    // Response sukses
    echo json_encode([
        'status' => 'success',
        'message' => 'Pengembalian berhasil dicatat',
        'data' => [
            'return_id' => $return_id,
            'borrowing_id' => $borrowing_id,
            'member_name' => $borrowing['nama'],
            'book_title' => $borrowing['judul'],
            'jumlah_buku' => count($borrowing_details),
            'tanggal_kembali' => $tanggal_kembali,
            'kondisi_buku' => $kondisi_buku,
            'denda' => [
                'terhitung_denda' => $denda_info['terhitung_denda'],
                'jumlah_hari_terlambat' => $denda_info['jumlah_hari_terlambat'],
                'total_denda' => $denda_info['total_denda'],
                'denda_per_hari' => $denda_info['denda_per_hari'],
                'fine_id' => $fine_id
            ]
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    if (isset($db)) {
        $db->rollBack();
    }

    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

?>