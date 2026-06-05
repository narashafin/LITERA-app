<?php

require_once '../../includes/config.php';
require_once '../../includes/auth_helper.php';

require_login();

$search = $_GET['search'] ?? '';

$query = "
SELECT
c.*,
COUNT(b.id) as total_buku

FROM categories c

LEFT JOIN books b
ON c.id = b.category_id

WHERE c.nama LIKE '%$search%'
OR c.kode LIKE '%$search%'

GROUP BY c.id
ORDER BY c.nama
";

$data = mysqli_query($conn,$query);
?>

<!doctype html>
<html>
  <head>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>

  <body class="bg-gray-100">
    <?php include '../../includes/sidebar.php'; ?> <?php include
    '../../includes/header.php'; ?>

    <div class="ml-80 mt-32 p-8">
      <div class="grid grid-cols-3 gap-8">
        <?php if(is_admin()): ?>

        <div class="bg-white shadow-lg rounded-xl p-6">
          <h2 class="text-2xl font-bold mb-5">Tambah Kategori</h2>

          <form action="create.php" method="POST">
            <div class="mb-4">
              <label>Kode</label>

              <input
                type="text"
                name="kode"
                required
                class="w-full border rounded-lg p-3"
              />
            </div>

            <div class="mb-4">
              <label>Nama</label>

              <input
                type="text"
                name="nama"
                required
                class="w-full border rounded-lg p-3"
              />
            </div>

            <div class="mb-4">
              <label>Deskripsi</label>

              <textarea
                name="deskripsi"
                class="w-full border rounded-lg p-3"
              ></textarea>
            </div>

            <button class="bg-blue-500 text-white px-6 py-3 rounded-lg w-full">
              Tambah Kategori
            </button>
          </form>
        </div>

        <?php endif; ?>

        <div
          class="<?= is_admin() ? 'col-span-2' : 'col-span-3'; ?> bg-white rounded-xl shadow-lg p-6"
        >
          <div class="flex justify-between mb-5">
            <h2 class="text-2xl font-bold">Data Kategori</h2>

            <form>
              <input
                type="text"
                name="search"
                placeholder="Cari kategori..."
                value="<?= htmlspecialchars($search) ?>"
                class="border p-2 rounded-lg"
              />
            </form>
          </div>

          <table class="w-full">
            <thead>
              <tr class="bg-blue-100">
                <th class="p-3">ID</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Total Buku</th>

                <?php if(is_admin()): ?>
                <th>Aksi</th>
                <?php endif; ?>
              </tr>
            </thead>

            <tbody>
              <?php while($row=mysqli_fetch_assoc($data)): ?>

              <tr class="border-b">
                <td class="p-3"><?= $row['id']; ?></td>

                <td><?= $row['kode']; ?></td>

                <td><?= $row['nama']; ?></td>

                <td><?= $row['total_buku']; ?></td>

                <?php if(is_admin()): ?>

                <td>
                  <a
                    href="edit.php?id=<?= $row['id']; ?>"
                    class="bg-yellow-400 px-3 py-1 rounded"
                  >
                    Edit
                  </a>

                  <a
                    href="delete.php?id=<?= $row['id']; ?>"
                    onclick="return confirm('Hapus kategori?');"
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
  </body>
</html>
