# Sistem Informasi PEMIRA Digital 2025 - Kelompok 9

Aplikasi Pemilihan Umum Raya (PEMIRA) berbasis web native yang mendigitalisasi proses pemungutan suara mahasiswa Institut Teknologi PLN menjadi sistem e-voting terintegrasi

## Spesifikasi
- Front-end: HTML5, CSS3, JavaScript (Vanilla JS), Bootstrap 5
- Back-end: PHP 8.x (Native)
- Database: MySQL (MariaDB)
- Framework Pengujian: PHPUnit 9.6 (White-Box / Unit Testing)

## Struktur Direktori Utama
- `admin/` : Modul manajemen data dan kontrol bilik oleh panitia.
- `superadmin/` : Panel administratif tertinggi dan grafik hasil real-time.
- `pemilih/` : Antarmuka bilik suara digital bagi mahasiswa.
- `test/` : Berkas skrip otomasi pengujian unit testing (`validator_test.php`).
- `config/` : Berkas konfigurasi koneksi basis data PDO.

## Cara Menjalankan Aplikasi di Lokal Server
1. Downlaod source code nya
2. Pindahkan folder proyek ke dalam direktori lokal server (`C:/xampp/htdocs/pemira`)
3. Jalankan XAMPP Control Panel dan aktifkan modul Apache serta MySQL
4. Buka `localhost/phpmyadmin` di browser, buat database baru dengan nama `pemira_2025`
5. Lakukan Import berkas basis data `pemira_2025.sql` yang tersedia di proyek ini
6. Akses aplikasi melalui URL: `http://localhost/pemira/`