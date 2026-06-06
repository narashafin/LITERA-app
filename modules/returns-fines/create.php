<?php
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=litera_db;charset=utf8mb4',
        'root',
        ''
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = $db->prepare("
        SELECT b.id, u.nama, bk.judul, b.tanggal_pinjam, b.tanggal_kembali, b.status
        FROM borrowings b
        JOIN users u ON b.user_id = u.id
        JOIN borrowing_details bd ON b.id = bd.borrowing_id
        JOIN books bk ON bd.book_id = bk.id
        WHERE b.status = 'dipinjam'
        GROUP BY b.id
        ORDER BY u.nama ASC
    ");
    $query->execute();
    $borrowings = $query->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $borrowings = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Form Pengembalian Buku</title>
</head>
<body>
    <h1>Form Pengembalian Buku</h1>
    
    <form method="POST" action="store.php">
        <div>
            <label>Pilih Peminjam:</label><br>
            <select name="borrowing_id" required>
                <option value="">-- Pilih Peminjam --</option>
                <?php foreach ($borrowings as $borrowing): ?>
                    <option value="<?php echo htmlspecialchars($borrowing['id']); ?>">
                        <?php echo htmlspecialchars($borrowing['nama']) . ' - ' . htmlspecialchars($borrowing['judul']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <br>

        <div>
            <label>Tanggal Kembali:</label><br>
            <input type="date" name="tanggal_kembali" required value="<?php echo date('Y-m-d'); ?>">
        </div>
        <br>

        <div>
            <label>Kondisi Buku:</label><br>
            <input type="radio" name="kondisi_buku" value="Baik Sekali" required> Baik Sekali
            <input type="radio" name="kondisi_buku" value="Baik"> Baik
            <input type="radio" name="kondisi_buku" value="Rusak Ringan"> Rusak Ringan
            <input type="radio" name="kondisi_buku" value="Rusak Berat"> Rusak Berat
        </div>
        <br>

        <div>
            <label>Catatan:</label><br>
            <textarea name="catatan" rows="5" cols="50"></textarea>
        </div>
        <br>

        <button type="submit">Simpan Pengembalian</button>
        <button type="reset">Batal</button>
    </form>
</body>
</html>