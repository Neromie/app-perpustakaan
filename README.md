# Perpustakaan Digital Kampus

Aplikasi manajemen perpustakaan kampus berbasis **Laravel 12**, dibangun bertahap sebagai project praktikum mata kuliah Pemrograman Framework (Pertemuan 1-10).

## Fitur

- **Autentikasi** login/logout berbasis session, dengan dua role: `admin` dan `petugas`
- **Otorisasi** lewat middleware `admin` - menu dan halaman Kategori khusus admin
- **CRUD** Buku, Kategori, Anggota, dan Peminjaman (satu transaksi bisa berisi beberapa buku)
- **Pengembalian buku** dengan badge status (`Dipinjam`, `Dikembalikan`, `Terlambat`)
- **Halaman Profil** dengan fitur ganti password
- **REST API** untuk `books`, `members`, `loans`, dan `stats` (dokumentasi lengkap di [`API.md`](API.md))
- **Dashboard** dan **Laporan Peminjaman** yang mengambil data dari REST API aplikasi sendiri lewat Laravel HTTP Client
- **Seeder & Factory** untuk data dummy bergaya Indonesia (20 buku, 15 anggota, 10 transaksi)

## Teknologi

| Komponen | Versi / Keterangan |
|---|---|
| PHP | 8.2 atau lebih baru |
| Laravel | 12 |
| Database | MySQL (lewat XAMPP) |
| Tampilan | Blade + CSS custom (tanpa Tailwind/Bootstrap) |
| Pengujian API | Postman |

## Prasyarat

Pastikan sudah terpasang di komputer:

- [PHP 8.2+](https://www.php.net/) dan [Composer](https://getcomposer.org/)
- [XAMPP](https://www.apachefriends.org/) (dipakai untuk MySQL)
- [Git](https://git-scm.com/)
- [Postman](https://www.postman.com/) (opsional, untuk menguji API)

## Instalasi

### 1. Clone repository

```bash
git clone https://github.com/Neromie/app-perpustakaan.git
cd app-perpustakaan
```

### 2. Install dependency

```bash
composer install
```

### 3. Siapkan file `.env`

Salin `.env.example` menjadi `.env`, lalu buat application key:

```bash
# Windows (CMD)
copy .env.example .env

# Linux / macOS / Git Bash
cp .env.example .env
```

```bash
php artisan key:generate
```

Cek bagian berikut di `.env` (nilai default di `.env.example` sudah disesuaikan untuk XAMPP):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_perpustakaan
DB_USERNAME=root
DB_PASSWORD=

# Server kedua khusus panggilan API internal (wajib port berbeda dari server utama)
INTERNAL_API_URL=http://127.0.0.1:8011

# Data dummy Factory bergaya Indonesia
APP_FAKER_LOCALE=id_ID
```

### 4. Buat database

Nyalakan **MySQL** di XAMPP Control Panel, lalu buat database kosong bernama `db_perpustakaan` (lewat phpMyAdmin di `http://localhost/phpmyadmin`, atau lewat terminal MySQL):

```sql
CREATE DATABASE db_perpustakaan;
```

### 5. Migrate & seed

```bash
php artisan migrate:fresh --seed
```

Perintah ini membuat ulang seluruh tabel lalu mengisinya lewat `DatabaseSeeder` dengan urutan: `UserSeeder` → `CategorySeeder` → `BookSeeder` → `MemberSeeder` → `LoanSeeder`.

> ⚠️ `migrate:fresh` **menghapus semua data** yang ada di database. Jalankan lagi kapan saja untuk mengembalikan aplikasi ke kondisi demo yang bersih.

### 6. Jalankan dua server

Buka **dua terminal** di folder project, lalu jalankan masing-masing:

```bash
# Terminal 1 - server utama, diakses dari browser
php artisan serve --port=8000
```

```bash
# Terminal 2 - server khusus panggilan API internal
php artisan serve --port=8011
```

Buka aplikasi di **http://127.0.0.1:8000**. Biarkan kedua terminal tetap berjalan selama aplikasi dipakai.

#### Kenapa harus dua server?

`php artisan serve` hanya bisa memproses **satu request dalam satu waktu**. Halaman Dashboard dan Laporan memanggil API aplikasinya sendiri lewat `Http::get()`. Kalau API itu dipanggil ke server yang sama (port 8000), server sedang sibuk memproses halaman yang justru menunggu balasan API tersebut, sehingga keduanya saling menunggu tanpa akhir (*deadlock*). Server kedua di port 8011 menangani panggilan API internal itu secara terpisah.

Kalau server port 8011 tidak dijalankan, aplikasi tetap bisa dibuka - Dashboard akan menampilkan angka `0` dan Laporan akan kosong (ditangani lewat `try/catch ConnectionException`).

## Akun Demo

Dibuat otomatis oleh `UserSeeder`:

| Nama | Email | Password | Role |
|---|---|---|---|
| Bambang Sutrisno | `admin@pens.ac.id` | `password` | admin |
| Siti Rahmawati | `petugas1@pens.ac.id` | `password` | petugas |
| Ahmad Fauzi | `petugas2@pens.ac.id` | `password` | petugas |

## Halaman Utama

| URL | Keterangan | Akses |
|---|---|---|
| `/login` | Form login | Publik |
| `/` | Dashboard statistik (dari `GET /api/stats`) | Login |
| `/books` | CRUD buku | Login |
| `/categories` | CRUD kategori | Admin |
| `/members` | CRUD anggota, dengan pencarian nama | Login |
| `/loans` | CRUD peminjaman + tombol Kembalikan | Login |
| `/loans/report` | Laporan peminjaman (dari `GET /api/loans`) | Login |
| `/profil` | Profil akun + ganti password | Login |

## REST API

Base URL: `http://127.0.0.1:8000/api`. Seluruh endpoint saat ini publik (tanpa autentikasi token).

| Method | URL | Keterangan |
|---|---|---|
| GET | `/api/books` | Daftar buku (filter `?judul=`, `?category_id=`) |
| GET | `/api/books/{id}` | Detail buku |
| POST | `/api/books` | Tambah buku |
| PUT | `/api/books/{id}` | Ubah buku |
| DELETE | `/api/books/{id}` | Hapus buku |
| GET | `/api/members` | Daftar anggota |
| GET | `/api/members/{id}` | Detail anggota |
| GET | `/api/loans` | Daftar peminjaman |
| POST | `/api/loans` | Tambah peminjaman |
| PUT | `/api/loans/{id}/kembalikan` | Proses pengembalian |
| GET | `/api/stats` | Statistik ringkas |

Contoh body, response, dan status code tiap endpoint ada di [`API.md`](API.md). Skenario demo lengkap ada di [`DEMO.md`](DEMO.md).

## Troubleshooting

| Gejala | Penyebab & solusi |
|---|---|
| `SQLSTATE[HY000] [2002] ... actively refused it` | MySQL belum menyala. Start MySQL di XAMPP Control Panel. |
| Dashboard menampilkan `0 / 0 / 0`, Laporan kosong | Server port 8011 belum dijalankan. Jalankan `php artisan serve --port=8011`. |
| Halaman Dashboard menggantung lama lalu error | `INTERNAL_API_URL` menunjuk ke port yang sama dengan server utama (8000). Ganti ke 8011. |
| `Unknown database 'db_perpustakaan'` | Database belum dibuat. Lihat langkah 4. |
| Perubahan `.env` tidak terbaca | Matikan lalu jalankan ulang kedua server, atau jalankan `php artisan config:clear`. |

## Riwayat Pengerjaan

| Pertemuan | Materi |
|---|---|
| 1 | Inisialisasi project Laravel 12 |
| 2 | Routing & request lifecycle |
| 3 | Controller, request & validasi |
| 4 | Blade layout |
| 5 | Migration, Eloquent & CRUD |
| 7 | Eloquent relationships & peminjaman |
| 8 | Autentikasi & middleware |
| 9 | REST API dasar |
| 10 | Konsumsi API, seeder & factory |

---

Hilmi Syahputra Wahyudi · D4 Teknik Informatika · PENS
