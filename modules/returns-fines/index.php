<?php
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=litera_db;charset=utf8mb4',
        'root',
        ''
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = $db->prepare("
        SELECT r.id, r.borrowing_id, r.tanggal_kembali
        FROM returns r
        ORDER BY r.id DESC
    ");
    $query->execute();
    $returns = $query->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $returns = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>List Pengembalian Buku</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        h1 {
            margin: 0;
            color: #333;
        }
        
        .btn-tambah {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-tambah:hover {
            background-color: #45a049;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table thead {
            background-color: #4CAF50;
            color: white;
        }
        
        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #ddd;
        }
        
        table td {
            padding: 12px;
            border: 1px solid #ddd;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        table tbody tr:hover {
            background-color: #f0f0f0;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: #666;
        }
        
        .icon-plus {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>List Pengembalian Buku</h1>
            <a href="create.php" class="btn-tambah">
                <span class="icon-plus">+</span>
                Tambah Pengembalian
            </a>
        </div>
        
        <?php if (empty($returns)): ?>
            <div class="no-data">
                <p>Tidak ada data pengembalian</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Borrowing ID</th>
                        <th>Tanggal Kembali</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($returns as $return): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($return['id']); ?></td>
                            <td><?php echo htmlspecialchars($return['borrowing_id']); ?></td>
                            <td><?php echo htmlspecialchars($return['tanggal_kembali']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>