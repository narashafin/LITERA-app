<?php

class ReturnHelper {
    private $db;
    private $denda_per_hari = 5000;
    private $max_buku_per_peminjaman = 15;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Validasi status peminjaman
     */
    public function validateBorrowingStatus($borrowing_id) {
        $query = $this->db->prepare("
            SELECT status FROM borrowings WHERE id = ?
        ");
        $query->execute([$borrowing_id]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            throw new Exception("Peminjaman tidak ditemukan.");
        }
        
        if ($result['status'] !== 'dipinjam') {
            throw new Exception("Status peminjaman bukan 'dipinjam'. Buku sudah dikembalikan sebelumnya atau status tidak valid.");
        }
        
        return true;
    }

    /**
     * Validasi sudah ada return record
     */
    public function checkReturnExists($borrowing_id) {
        $query = $this->db->prepare("
            SELECT id FROM returns WHERE borrowing_id = ?
        ");
        $query->execute([$borrowing_id]);
        
        if ($query->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("Buku dari peminjaman ini sudah dikembalikan sebelumnya. Tidak boleh dikembalikan 2x.");
        }
        
        return true;
    }

    /**
     * Menghitung denda keterlambatan
     */
    public function hitungDenda($tanggal_kembali, $tanggal_kembali_sebenarnya) {
        $due = new DateTime($tanggal_kembali);
        $return = new DateTime($tanggal_kembali_sebenarnya);

        $diff = $return->diff($due);
        $hari_terlambat = $diff->days;

        $terhitung_denda = $return > $due;
        $total_denda = $terhitung_denda ? $hari_terlambat * $this->denda_per_hari : 0;

        return [
            'terhitung_denda' => $terhitung_denda,
            'jumlah_hari_terlambat' => $terhitung_denda ? $hari_terlambat : 0,
            'total_denda' => $total_denda,
            'denda_per_hari' => $this->denda_per_hari
        ];
    }

    /**
     * Mengambil data peminjaman dari borrowing_id
     */
    public function getBorrowingData($borrowing_id) {
        $query = $this->db->prepare("
            SELECT b.id, b.kode_pinjam, b.user_id, b.tanggal_pinjam, b.tanggal_kembali, 
                   u.nama, bk.judul
            FROM borrowings b
            JOIN users u ON b.user_id = u.id
            JOIN borrowing_details bd ON b.id = bd.borrowing_id
            JOIN books bk ON bd.book_id = bk.id
            WHERE b.id = ?
            LIMIT 1
        ");
        $query->execute([$borrowing_id]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil semua detail peminjaman (multiple books)
     */
    public function getAllBorrowingDetails($borrowing_id) {
    $query = $this->db->prepare("
        SELECT bd.id, bd.borrowing_id, bd.book_id, bd.jumlah
        FROM borrowing_details bd
        WHERE borrowing_id = ?
    ");
        $query->execute([$borrowing_id]);
        $details = $query->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($details) > $this->max_buku_per_peminjaman) {
            throw new Exception("Peminjaman melebihi maksimal " . $this->max_buku_per_peminjaman . " buku.");
        }
        
        return $details;
    }

    /**
     * Mengambil detail peminjaman pertama (untuk response)
     */
    public function getBorrowingDetails($borrowing_id) {
        $query = $this->db->prepare("
            SELECT bd.id, bd.borrowing_id, bd.book_id, bd.jumlah
            FROM borrowing_details 
            WHERE borrowing_id = ?
            LIMIT 1
        ");
        $query->execute([$borrowing_id]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update stok buku di inventory setelah pengembalian
     */
    public function updateStokBuku($book_id, $jumlah) {
        try {
            $query = $this->db->prepare("
                UPDATE books 
                SET stok = stok + ? 
                WHERE id = ?
            ");
            return $query->execute([$jumlah, $book_id]);
        } catch (Exception $e) {
            throw new Exception("Gagal update stok: " . $e->getMessage());
        }
    }

    /**
     * Update stok untuk semua buku dalam peminjaman
     */
    public function updateAllStok($borrowing_details) {
        try {
            foreach ($borrowing_details as $detail) {
                $this->updateStokBuku($detail['book_id'], $detail['jumlah']);
            }
            return true;
        } catch (Exception $e) {
            throw new Exception("Gagal update stok buku: " . $e->getMessage());
        }
    }

    /**
     * Update status pengembalian di borrowings
     */
    public function updateBorrowingStatus($borrowing_id) {
        try {
            $query = $this->db->prepare("
                UPDATE borrowings 
                SET status = 'dikembalikan'
                WHERE id = ?
            ");
            return $query->execute([$borrowing_id]);
        } catch (Exception $e) {
            throw new Exception("Gagal update borrowing: " . $e->getMessage());
        }
    }

    /**
     * Menyimpan data pengembalian ke tabel returns
     */
    public function saveReturnData($borrowing_id, $tanggal_kembali, $kondisi_buku, $catatan, $admin_id = null) {
        try {
            $query = $this->db->prepare("
                INSERT INTO returns (borrowing_id, admin_id, tanggal_kembali, kondisi_buku, catatan)
                VALUES (?, ?, ?, ?, ?)
            ");
            $query->execute([
                $borrowing_id,
                $admin_id,
                $tanggal_kembali,
                $kondisi_buku,
                $catatan ?? ''
            ]);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Gagal menyimpan return: " . $e->getMessage());
        }
    }

    /**
     * Menyimpan denda ke tabel fines (HANYA yang ada denda)
     */
    public function saveFineData($borrowing_id, $user_id, $denda_info) {
        try {
            // Hanya simpan jika ada denda (terlambat)
            if ($denda_info['terhitung_denda']) {
                $query = $this->db->prepare("
                    INSERT INTO fines (borrowing_id, user_id, hari_terlambat, denda_per_hari, total_denda, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $query->execute([
                    $borrowing_id,
                    $user_id,
                    $denda_info['jumlah_hari_terlambat'],
                    $denda_info['denda_per_hari'],
                    $denda_info['total_denda'],
                    'belum_lunas'
                ]);
                return $this->db->lastInsertId();
            }
            
            return null; // Tidak ada denda
        } catch (Exception $e) {
            throw new Exception("Gagal menyimpan fine: " . $e->getMessage());
        }
    }
}

?>