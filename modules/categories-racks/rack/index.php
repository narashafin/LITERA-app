<?php

require_once '../../includes/config.php';
require_once '../../includes/auth_helper.php';

require_login();

$search = $_GET['search'] ?? '';

$query = "
SELECT
    r.*,
    COUNT(b.id) AS total_buku
FROM racks r
LEFT JOIN books b
ON r.id = b.rack_id
WHERE
    r.nama LIKE '%$search%'
    OR r.kode_rak LIKE '%$search%'
GROUP BY r.id
ORDER BY r.kode_rak ASC
";

$data = mysqli_query($conn, $query);

?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Manajemen Rak Buku</title>

    <script src="https://cdn.tailwindcss.com"></script>
  </head>

  <body class="bg-gray-100">
    <?php include '../../includes/sidebar.php'; ?> <?php include
    '../../includes/header.php'; ?>

    <div class="ml-80 mt-32 p-8">
      <div class="grid grid-cols-3 gap-8">
        <?php if(is_admin()): ?>

        <!-- FORM TAMBAH -->

        <div class="bg-white rounded-2xl shadow-lg p-6">
          <h2 class="text-2xl font-bold mb-6">Tambah Rak Buku</h2>

          <form action="create.php" method="POST" class="space-y-4">
            <div>
              <label class="block mb-2"> Kode Rak </label>

              <input
                type="text"
                name="kode_rak"
                required
                class="w-full border rounded-xl p-3"
              />
            </div>

            <div>
              <label class="block mb-2"> Nama Rak </label>

              <input
                type="text"
                name="nama"
                required
                class="w-full border rounded-xl p-3"
              />
            </div>

            <div>
              <label class="block mb-2"> Lokasi </label>

              <input
                type="text"
                name="lokasi"
                class="w-full border rounded-xl p-3"
              />
            </div>

            <div>
              <label class="block mb-2"> Kapasitas </label>

              <input
                type="number"
                name="kapasitas"
                value="50"
                class="w-full border rounded-xl p-3"
              />
            </div>

            <div>
              <label class="block mb-2"> Deskripsi </label>

              <textarea
                name="deskripsi"
                rows="4"
                class="w-full border rounded-xl p-3"
              ></textarea>
            </div>

            <button
              type="submit"
              class="w-full bg-blue-500 text-white py-3 rounded-xl hover:bg-blue-600"
            >
              Tambah Rak
            </button>
          </form>
        </div>

        <?php endif; ?>

        <!-- TABEL -->

        <div
          class="<?= is_admin() ? 'col-span-2' : 'col-span-3'; ?> bg-white rounded-2xl shadow-lg p-6"
        >
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Data Rak Buku</h2>

            <form>
              <input
                type="text"
                name="search"
                placeholder="Cari rak..."
                value="<?= htmlspecialchars($search) ?>"
                class="border rounded-xl p-2"
              />
            </form>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="bg-blue-100">
                  <th class="p-3">ID</th>
                  <th>Kode</th>
                  <th>Nama</th>
                  <th>Lokasi</th>
                  <th>Kapasitas</th>
                  <th>Total Buku</th>

                  <?php if(is_admin()): ?>
                  <th>Aksi</th>
                  <?php endif; ?>
                </tr>
              </thead>

              <tbody>
                <?php while($row = mysqli_fetch_assoc($data)): ?>

                <tr class="border-b text-center">
                  <td class="p-3"><?= $row['id'] ?></td>

                  <td><?= htmlspecialchars($row['kode_rak']) ?></td>

                  <td><?= htmlspecialchars($row['nama']) ?></td>

                  <td><?= htmlspecialchars($row['lokasi']) ?></td>

                  <td><?= $row['kapasitas'] ?></td>

                  <td><?= $row['total_buku'] ?></td>

                  <?php if(is_admin()): ?>

                  <td class="space-x-2">
                    <a
                      href="edit.php?id=<?= $row['id'] ?>"
                      class="bg-yellow-400 px-3 py-1 rounded"
                    >
                      Edit
                    </a>

                    <a
                      href="delete.php?id=<?= $row['id'] ?>"
                      onclick="return confirm('Hapus rak ini?');"
                      class="bg-red-500 text-white px-3 py-1 rounded"
                    >
                      Hapus
                    </a>
                  </td>

                  <?php endif; ?>
                </tr>

                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
