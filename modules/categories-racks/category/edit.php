<?php

require_once '../../includes/config.php';
require_once '../../includes/auth_helper.php';

require_admin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

$query = mysqli_query(
    $conn,
    "SELECT * FROM categories WHERE id = $id"
);

$category = mysqli_fetch_assoc($query);

if (!$category) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $kode = mysqli_real_escape_string(
        $conn,
        trim($_POST['kode'])
    );

    $nama = mysqli_real_escape_string(
        $conn,
        trim($_POST['nama'])
    );

    $deskripsi = mysqli_real_escape_string(
        $conn,
        trim($_POST['deskripsi'])
    );

    $update = mysqli_query(
        $conn,
        "UPDATE categories
         SET
            kode='$kode',
            nama='$nama',
            deskripsi='$deskripsi'
         WHERE id=$id"
    );

    if ($update) {
        header("Location: index.php?success=update");
        exit;
    }

    $error = "Gagal memperbarui kategori";
}

?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Edit Kategori</title>

    <script src="https://cdn.tailwindcss.com"></script>
  </head>

  <body class="bg-gray-100">
    <?php include '../../includes/sidebar.php'; ?> <?php include
    '../../includes/header.php'; ?>

    <div class="ml-80 mt-32 p-8">
      <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-lg p-8">
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-800">Edit Kategori</h1>

          <p class="text-gray-500 mt-2">Perbarui data kategori buku.</p>
        </div>

        <?php if(isset($error)): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6">
          <?= $error ?>
        </div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-5">
            <label class="block mb-2 font-medium text-gray-700">
              Kode Kategori
            </label>

            <input
              type="text"
              name="kode"
              value="<?= htmlspecialchars($category['kode']) ?>"
              required
              class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-400"
            />
          </div>

          <div class="mb-5">
            <label class="block mb-2 font-medium text-gray-700">
              Nama Kategori
            </label>

            <input
              type="text"
              name="nama"
              value="<?= htmlspecialchars($category['nama']) ?>"
              required
              class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-400"
            />
          </div>

          <div class="mb-6">
            <label class="block mb-2 font-medium text-gray-700">
              Deskripsi
            </label>

            <textarea
              name="deskripsi"
              rows="5"
              class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-400"
            >
<?= htmlspecialchars($category['deskripsi']) ?></textarea
            >
          </div>

          <div class="flex gap-4">
            <a
              href="index.php"
              class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition"
            >
              Batal
            </a>

            <button
              type="submit"
              class="px-6 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition"
            >
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </body>
</html>
