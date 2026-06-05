<?php

require_once '../../includes/config.php';
require_once '../../includes/auth_helper.php';

require_admin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

$getData = mysqli_query(
    $conn,
    "SELECT * FROM racks WHERE id=$id"
);

$rack = mysqli_fetch_assoc($getData);

if (!$rack) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $kode_rak = mysqli_real_escape_string(
        $conn,
        trim($_POST['kode_rak'])
    );

    $nama = mysqli_real_escape_string(
        $conn,
        trim($_POST['nama'])
    );

    $lokasi = mysqli_real_escape_string(
        $conn,
        trim($_POST['lokasi'])
    );

    $kapasitas = (int)$_POST['kapasitas'];

    $deskripsi = mysqli_real_escape_string(
        $conn,
        trim($_POST['deskripsi'])
    );

    $update = "
    UPDATE racks
    SET
        kode_rak='$kode_rak',
        nama='$nama',
        lokasi='$lokasi',
        kapasitas=$kapasitas,
        deskripsi='$deskripsi'
    WHERE id=$id
    ";

    mysqli_query($conn, $update);

    header("Location: index.php");
    exit;
}

?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Edit Rak Buku</title>

    <script src="https://cdn.tailwindcss.com"></script>
  </head>

  <body class="bg-gray-100">
    <?php include '../../includes/sidebar.php'; ?> <?php include
    '../../includes/header.php'; ?>

    <div class="ml-80 mt-32 p-8">
      <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-6">Edit Rak Buku</h1>

        <form method="POST">
          <div class="mb-4">
            <label class="block mb-2"> Kode Rak </label>

            <input
              type="text"
              name="kode_rak"
              value="<?= htmlspecialchars($rack['kode_rak']) ?>"
              required
              class="w-full border rounded-xl p-3"
            />
          </div>

          <div class="mb-4">
            <label class="block mb-2"> Nama Rak </label>

            <input
              type="text"
              name="nama"
              value="<?= htmlspecialchars($rack['nama']) ?>"
              required
              class="w-full border rounded-xl p-3"
            />
          </div>

          <div class="mb-4">
            <label class="block mb-2"> Lokasi </label>

            <input
              type="text"
              name="lokasi"
              value="<?= htmlspecialchars($rack['lokasi']) ?>"
              class="w-full border rounded-xl p-3"
            />
          </div>

          <div class="mb-4">
            <label class="block mb-2"> Kapasitas </label>

            <input
              type="number"
              name="kapasitas"
              value="<?= $rack['kapasitas'] ?>"
              class="w-full border rounded-xl p-3"
            />
          </div>

          <div class="mb-6">
            <label class="block mb-2"> Deskripsi </label>

            <textarea
              name="deskripsi"
              rows="4"
              class="w-full border rounded-xl p-3"
            >
<?= htmlspecialchars($rack['deskripsi']) ?></textarea
            >
          </div>

          <div class="flex gap-3">
            <a
              href="index.php"
              class="bg-gray-400 text-white px-6 py-3 rounded-xl"
            >
              Batal
            </a>

            <button
              type="submit"
              class="bg-blue-500 text-white px-6 py-3 rounded-xl"
            >
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </body>
</html>
