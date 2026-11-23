# Skenario Demo - Perpustakaan Digital Kampus

Urutan demo aplikasi untuk UAS: login → dashboard → tambah kategori → tambah buku → tambah anggota → buat peminjaman → lihat peminjaman aktif → proses pengembalian → laporan → demo API di Postman.

Setiap langkah berisi **aksi** yang dilakukan, **hasil** yang harus terlihat, dan **poin penjelasan** (konsep yang bisa disampaikan saat demo).

---

## 0. Persiapan (sebelum demo dimulai)

1. Nyalakan **MySQL** di XAMPP Control Panel.
2. Reset database ke kondisi demo yang bersih:
   ```bash
   php artisan migrate:fresh --seed
   ```
3. Jalankan dua server di dua terminal terpisah:
   ```bash
   php artisan serve --port=8000
   ```
   ```bash
   php artisan serve --port=8011
   ```
4. Buka Postman, siapkan collection `app-perpustakaan` dengan header `Accept: application/json`.
5. Buka browser di `http://127.0.0.1:8000/login`.

**Data awal setelah seeding:** 3 user, 5 kategori, 20 buku, 15 anggota, 10 transaksi peminjaman (status acak).

---

## 1. Login

| | |
|---|---|
| **Aksi** | Isi email `admin@pens.ac.id`, password `password`, klik **Login**. |
| **Hasil** | Diarahkan ke Dashboard (`/`), muncul flash hijau "Login berhasil, selamat datang Bambang Sutrisno." Navbar kanan menampilkan **Bambang Sutrisno (Admin)** dan tombol Logout. |
| **Poin penjelasan** | Autentikasi berbasis session lewat `Auth::attempt()`. Setelah login, `session()->regenerate()` dipanggil untuk mencegah *session fixation*. Semua halaman selain `/login` dilindungi middleware `auth`. |

*Opsional:* coba login dengan password salah dulu → muncul pesan "Email atau password salah." dan email tetap terisi (`onlyInput('email')`).

---

## 2. Tampilkan Dashboard

| | |
|---|---|
| **Aksi** | Tunjukkan halaman Dashboard. |
| **Hasil** | Tiga kartu: **Total Buku 20**, **Total Anggota 15**, **Peminjaman Aktif** (jumlah transaksi berstatus `dipinjam`, angkanya acak dari seeder). |
| **Poin penjelasan** | Angka ini **tidak di-query langsung** dari database. `DashboardController` memanggil `GET /api/stats` lewat Laravel HTTP Client (`Http::get()`) ke server kedua di port 8011, lalu menampilkan JSON hasilnya. Port terpisah diperlukan karena `php artisan serve` hanya melayani satu request sekaligus (menghindari *deadlock*). |

**Catat angka Peminjaman Aktif** - akan dibandingkan di langkah 7 dan 8.

---

## 3. Tambah Kategori

| | |
|---|---|
| **Aksi** | Klik **Kategori** → **+ Tambah Kategori**. Isi Nama Kategori `Filsafat`, Deskripsi `Buku seputar pemikiran, logika, dan etika.` → **Simpan**. |
| **Hasil** | Kembali ke daftar kategori dengan flash **Kategori "Filsafat" berhasil ditambahkan.**, kategori **Filsafat** muncul di tabel (total 6 kategori). |
| **Poin penjelasan** | Menu Kategori hanya muncul untuk admin, dan route `categories` dibungkus middleware custom `admin` (`CheckAdminRole`). Validasi lewat Form Request `StoreCategoryRequest`. |

---

## 4. Tambah Buku

| | |
|---|---|
| **Aksi** | Klik **Buku** → **+ Tambah Buku**. Isi: Judul `Filosofi Teras`, Penulis `Henry Manampiring`, Penerbit `Kompas Media Nusantara`, Tahun Terbit `2018`, ISBN dikosongkan, Stok `5`, Kategori **Filsafat** → **Simpan**. |
| **Hasil** | Buku baru tersimpan dengan kategori Filsafat. Karena daftar buku memakai pagination 10 per halaman, buku baru berada di **halaman 3**. |
| **Poin penjelasan** | Dropdown kategori diisi dari tabel `categories` - relasi `Book belongsTo Category`. Coba juga klik **Simpan** dengan form kosong untuk menunjukkan pesan validasi per field. |

---

## 5. Tambah Anggota

| | |
|---|---|
| **Aksi** | Klik **Anggota** → **+ Tambah Anggota**. Isi: Nama `Rizky Pratama`, NIM `3125600099`, Email `rizky.pratama@pens.ac.id`, Nomor Telepon `081234567890`, Alamat `Jl. Raya ITS, Keputih, Sukolilo, Surabaya`, Status **Aktif** → **Simpan**. |
| **Hasil** | Anggota baru tersimpan (total 16 anggota). |
| **Poin penjelasan** | Ketik `Rizky` di kolom pencarian untuk menunjukkan fitur cari nama anggota - query string `?search=` tetap terbawa saat pindah halaman pagination. |

---

## 6. Buat Peminjaman

| | |
|---|---|
| **Aksi** | Klik **Peminjaman** → **+ Tambah Peminjaman**. Pilih Anggota **Rizky Pratama**, Tanggal Pinjam = hari ini, Tanggal Kembali = 7 hari lagi, centang buku **Filosofi Teras** dan satu buku lain → **Simpan**. |
| **Hasil** | Transaksi baru tersimpan dengan status **Dipinjam** (badge kuning), berisi 2 buku. Kolom Petugas otomatis terisi **Bambang Sutrisno**. |
| **Poin penjelasan** | Form tidak punya pilihan petugas - `user_id` diisi otomatis dari `auth()->id()`, sehingga petugas tidak bisa mencatat atas nama orang lain. Satu transaksi bisa berisi banyak buku lewat tabel `loan_items` (`Loan hasMany LoanItem`). |

---

## 7. Lihat Daftar Peminjaman Aktif

| | |
|---|---|
| **Aksi** | Buka **Peminjaman**, pindah ke halaman 2 untuk melihat transaksi baru. Kembali ke **Dashboard**. |
| **Hasil** | Transaksi berstatus **Dipinjam** ditandai badge kuning dan punya tombol **Kembalikan**; transaksi lain berbadge hijau (Dikembalikan) atau merah (Terlambat). Di Dashboard, **Peminjaman Aktif bertambah 1** dari angka di langkah 2. |
| **Poin penjelasan** | Data peminjaman memuat anggota, petugas, dan buku sekaligus lewat eager loading `Loan::with(['member', 'user', 'loanItems.book'])` untuk menghindari *N+1 query problem*. |

---

## 8. Proses Pengembalian

| | |
|---|---|
| **Aksi** | Di daftar Peminjaman, klik **Kembalikan** pada transaksi Rizky Pratama. |
| **Hasil** | Flash "Buku berhasil dikembalikan.", badge berubah jadi hijau **Dikembalikan**, tombol Kembalikan hilang. Di Dashboard, **Peminjaman Aktif kembali** ke angka langkah 2. |
| **Poin penjelasan** | Tombol Kembalikan adalah form `PUT /loans/{id}/kembalikan` (method spoofing `@method('PUT')`) yang mengisi `status = dikembalikan` dan `tanggal_dikembalikan = hari ini`. Tombol hanya muncul untuk transaksi berstatus `dipinjam`. |

---

## 9. Tampilkan Laporan

| | |
|---|---|
| **Aksi** | Klik **Laporan** di navbar (atau tombol **Lihat Laporan Peminjaman** di Dashboard). |
| **Hasil** | Tabel seluruh transaksi lengkap dengan anggota, petugas, daftar buku, tanggal, dan status - termasuk transaksi Rizky Pratama yang sudah Dikembalikan. |
| **Poin penjelasan** | Isinya sama dengan halaman Peminjaman, tapi sumbernya berbeda: Laporan mengambil data dari `GET /api/loans` (JSON), bukan query Eloquent langsung - karena itu diakses dengan notasi array (`$loan['member']['nama']`) dan pagination-nya dibangun manual dari `meta.current_page` / `meta.last_page`. |

*Opsional (ketahanan):* matikan server port 8011 (Ctrl+C), refresh Dashboard dan Laporan → halaman tetap tampil (angka 0, tabel kosong), bukan error 500, berkat `try/catch ConnectionException`. Nyalakan lagi server 8011 setelahnya.

---

## 10. Demo API di Postman

Base URL `http://127.0.0.1:8000/api`, header `Accept: application/json`.

| No | Request | Hasil yang ditunjukkan |
|---|---|---|
| 1 | `GET /api/stats` | `200` - angka sama persis dengan kartu Dashboard |
| 2 | `GET /api/books?judul=Filosofi` | `200` - hanya buku Filosofi Teras, lengkap dengan `category.nama_kategori: "Filsafat"` |
| 3 | `POST /api/books` dengan body JSON lengkap (lihat [`API.md`](API.md)) | `201 Created` - buku baru langsung muncul di halaman `/books` |
| 4 | `POST /api/books` dengan body `{}` | `422` - `errors` berisi pesan validasi per field (memakai `StoreBookRequest` yang sama dengan form web) |
| 5 | `GET /api/books/999` | `404` - response tetap JSON |
| 6 | `GET /api/loans` | `200` - tiap transaksi berisi `member`, `petugas`, dan `books` dalam struktur nested |

**Poin penjelasan:** REST API memakai URL berbasis resource (`/books`, `/loans`) dan HTTP method sebagai aksi (GET/POST/PUT/DELETE). Format response konsisten berkat API Resource (`BookResource`, `LoanResource`), yang juga memastikan hanya field yang diizinkan yang keluar ke klien. Status code memberi tahu hasil dalam satu angka: `201` berhasil membuat, `422` gagal validasi, `404` data tidak ada.

---

## 11. (Opsional) Demo Hak Akses Petugas

| | |
|---|---|
| **Aksi** | **Logout**, lalu login sebagai `petugas1@pens.ac.id` / `password`. Ketik manual `http://127.0.0.1:8000/categories` di address bar. |
| **Hasil** | Navbar menampilkan **Siti Rahmawati (Petugas)** tanpa menu Kategori. Membuka `/categories` langsung menghasilkan **403 - Akses ditolak. Halaman ini khusus admin.** |
| **Poin penjelasan** | Menyembunyikan menu hanyalah tampilan; yang benar-benar mengamankan halaman adalah middleware `admin` di sisi server. |

---

## Setelah Demo

Untuk mengembalikan database ke kondisi awal:

```bash
php artisan migrate:fresh --seed
```
