# LITERA App | Manajemen Perpustakaan

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%207.4-777bb4?style=flat-square&logo=php)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-5.7%2B-4479a1?style=flat-square&logo=mysql)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Open%20Source-success?style=flat-square)](#hak-cipta-dan-lisensi)

**LITERA App** adalah platform manajemen perpustakaan digital berbasis web yang dirancang untuk merampingkan seluruh operasional administrasi pustaka. Sistem ini mengintegrasikan pengelolaan katalog buku, penataan ruang (rak), pelacakan sirkulasi peminjaman/pengembalian, kalkulasi denda otomatis, hingga penyajian laporan statistik yang intuitif bagi pengambil keputusan.

---

## ✨ Fitur Utama Sistem

### 1. Autentikasi & Keamanan Sesi

- **Enkripsi Data Sensitif:** Keamanan kata sandi menggunakan algoritma _hashing_ bawaan PHP `password_hash()` berbasis BCRYPT.
- **Kontrol Akses Berbasis Peran (RBAC):** Pembatasan hak akses halaman yang ketat antara **Admin** (Manajemen penuh) dan **Anggota** (Akses terbatas) menggunakan modul `auth_helper.php`.

### 2. Manajemen Master Data (CRUD)

- **Manajemen Pengguna:** Pengelolaan profil admin dan anggota lengkap dengan validasi keunikan _username_/_email_ serta proteksi relasi data.
- **Katalog Buku:** Pencatatan komprehensif meliputi judul, penulis, penerbit, tahun terbit, hingga alokasi penempatan fisik buku.
- **Kategori & Rak Dinamis:** Struktur klasifikasi buku berlapis untuk mempermudah pustakawan dalam menyusun materi fisik di perpustakaan.

### 3. Modul Sirkulasi & Transaksi

- **Peminjaman Terintegrasi:** Pencatatan otomatis yang menghubungkan data anggota, buku yang tersedia, tanggal pinjam, dan batas waktu pengembalian.
- **Pengembalian & Kalkulasi Denda:** Deteksi keterlambatan otomatis yang langsung menghitung akumulasi nilai denda berdasarkan selisih hari.

### 4. Dasbor Analitik & Laporan

- **Visualisasi Tren:** Grafik batang interaktif yang menampilkan volume transaksi peminjaman selama 6 bulan terakhir.
- **Statistik Cepat:** Panel metrik untuk memantau total buku, jumlah member aktif, denda tertunggak, dan rasio buku yang sedang dipinjam secara _real-time_.

---

## 🏗️ Arsitektur & Struktur Direktori

Proyek ini mengadopsi pendekatan modular untuk memisahkan logika bisnis, aset visual, dan komponen global demi kemudahan pemeliharaan kode (_maintainability_).

```text
LITERA-app/
├── assets/                 # Aset statis aplikasi
│   ├── css/                # Lembar gaya global (style.css)
│   ├── js/                 # Skrip fungsionalitas visual & interaksi
│   └── images/             # Komponen visual, logo, dan ikon (LITERA.png)
├── includes/               # Komponen inti sistem
│   ├── config.php          # Inisialisasi koneksi database MySQLi
│   ├── auth_helper.php     # Fungsi validasi sesi dan gerbang keamanan role
│   └── sidebar.php         # Komponen navigasi menu vertikal global
├── modules/                # Modul operasional spesifik aplikasi
│   ├── books/              # Pengelolaan backend katalog buku
│   ├── categories-racks/   # Modul klasifikasi kategori dan nomor rak
│   ├── borrowings/         # Manajemen alur sirkulasi peminjaman
│   ├── returns-fines/      # Pemrosesan pengembalian dan rekam denda
│   └── users-auth/         # Sistem autentikasi (login.php, logout.php)
├── pages/                  # Antarmuka administrasi utama
│   ├── users.php           # Panel manajemen pengguna & hak akses
│   └── reports.php         # Panel laporan analitik & chart peminjaman
├── index.php               # Gateway pengarah utama aplikasi
└── README.md               # Dokumentasi teknis proyek
```
