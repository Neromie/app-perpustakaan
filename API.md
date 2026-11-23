# Dokumentasi REST API - App Perpustakaan

Dokumentasi seluruh endpoint REST API yang dibangun di Pertemuan 9. Semua endpoint mengembalikan JSON lewat API Resource (`BookResource`, `MemberResource`, `LoanResource`), kecuali `/api/stats` yang memakai `response()->json()` langsung.

## Informasi Umum

| Item | Keterangan |
|---|---|
| Base URL | `http://127.0.0.1:8000/api` |
| Header request | `Accept: application/json` (dan `Content-Type: application/json` untuk request dengan body) |
| Autentikasi | Belum ada - seluruh endpoint publik (autentikasi token di luar cakupan Pertemuan 9) |
| Pagination | Endpoint list mengembalikan 10 item per halaman, navigasi lewat `?page=N` |
| Format error | Semua error di `/api/*` selalu dibalas JSON (diatur lewat `shouldRenderJsonWhen` di `bootstrap/app.php`) |

Contoh response di bawah diambil dari request sungguhan ke server lokal setelah `php artisan migrate:fresh --seed` (20 buku, 15 anggota, 10 transaksi), bukan ditulis manual. Supaya ringkas:

- Response list hanya menampilkan **item pertama** dari array `data`; item lainnya ditandai `// ...`.
- Response `404` hanya menampilkan `message` dan `exception`. Karena `APP_DEBUG=true` di lingkungan lokal, Laravel juga menyertakan field `file`, `line`, dan `trace` (berisi path folder di komputer) - field tersebut dihapus dari dokumentasi ini dan tidak akan muncul di produksi (`APP_DEBUG=false`).

## Ringkasan Endpoint

| No | Method | URL | Body (jika ada) | Contoh Response | Status Code |
|---|---|---|---|---|---|
| 1 | GET | `/api/books` | - (query opsional `?judul=`, `?category_id=`, `?page=`) | `{"data": [ {buku}, ... ], "links": {...}, "meta": {...}}` | `200` |
| 2 | GET | `/api/books/{id}` | - | `{"data": {buku}}` | `200`, `404` |
| 3 | POST | `/api/books` | `judul`, `penulis`, `penerbit`, `tahun_terbit`, `isbn`, `stok`, `category_id` | `{"data": {buku baru}}` | `201`, `422` |
| 4 | PUT | `/api/books/{id}` | sama seperti POST | `{"data": {buku setelah diubah}}` | `200`, `404`, `422` |
| 5 | DELETE | `/api/books/{id}` | - | `{"message": "Buku berhasil dihapus."}` | `200`, `404` |
| 6 | GET | `/api/members` | - (query opsional `?page=`) | `{"data": [ {anggota}, ... ], "links": {...}, "meta": {...}}` | `200` |
| 7 | GET | `/api/members/{id}` | - | `{"data": {anggota}}` | `200`, `404` |
| 8 | GET | `/api/loans` | - (query opsional `?page=`) | `{"data": [ {peminjaman}, ... ], "links": {...}, "meta": {...}}` | `200` |
| 9 | POST | `/api/loans` | `member_id`, `user_id`, `tanggal_pinjam`, `tanggal_kembali`, `book_ids[]` | `{"data": {peminjaman baru, status "dipinjam"}}` | `201`, `422` |
| 10 | PUT | `/api/loans/{id}/kembalikan` | - | `{"data": {peminjaman, status "dikembalikan"}}` | `200`, `404` |
| 11 | GET | `/api/stats` | - | `{"total_buku": 20, "total_anggota": 15, "peminjaman_aktif": 4}` | `200` |

Detail request dan response lengkap tiap endpoint ada di bawah.

---

## Books

### 1. GET `/api/books`

Daftar buku beserta kategorinya, dengan pagination. Mendukung filter opsional:

| Query | Keterangan |
|---|---|
| `judul` | Pencarian sebagian judul (`LIKE %...%`) |
| `category_id` | Pencocokan persis ID kategori |
| `page` | Nomor halaman |

**Response `200 OK`** - `GET /api/books`

```jsonc
{
  "data": [
    {
      "id": 1,
      "judul": "Si Juki: Kuliah Kerja Nyata",
      "penulis": "Saadat Mansur",
      "penerbit": "Bentang Pustaka",
      "tahun_terbit": "2013",
      "isbn": "9786717233245",
      "stok": 6,
      "sampul": null,
      "category": {
        "id": 2,
        "nama_kategori": "Komik"
      },
      "created_at": "2026-11-23T13:54:00.000000Z",
      "updated_at": "2026-11-23T13:54:00.000000Z"
    }
    // ... 9 item lainnya
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/books?page=1",
    "last": "http://127.0.0.1:8000/api/books?page=2",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/books?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 2,
    "links": [
      { "url": null, "label": "&laquo; Previous", "page": null, "active": false },
      { "url": "http://127.0.0.1:8000/api/books?page=1", "label": "1", "page": 1, "active": true },
      { "url": "http://127.0.0.1:8000/api/books?page=2", "label": "2", "page": 2, "active": false },
      { "url": "http://127.0.0.1:8000/api/books?page=2", "label": "Next &raquo;", "page": 2, "active": false }
    ],
    "path": "http://127.0.0.1:8000/api/books",
    "per_page": 10,
    "to": 10,
    "total": 20
  }
}
```

**Response `200 OK`** - `GET /api/books?judul=Si` (filter judul, 6 buku cocok)

```jsonc
{
  "data": [
    {
      "id": 1,
      "judul": "Si Juki: Kuliah Kerja Nyata",
      "penulis": "Saadat Mansur",
      "penerbit": "Bentang Pustaka",
      "tahun_terbit": "2013",
      "isbn": "9786717233245",
      "stok": 6,
      "sampul": null,
      "category": { "id": 2, "nama_kategori": "Komik" },
      "created_at": "2026-11-23T13:54:00.000000Z",
      "updated_at": "2026-11-23T13:54:00.000000Z"
    }
    // ... 5 item lainnya
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/books?page=1",
    "last": "http://127.0.0.1:8000/api/books?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [ /* ... */ ],
    "path": "http://127.0.0.1:8000/api/books",
    "per_page": 10,
    "to": 6,
    "total": 6
  }
}
```

**Response `200 OK`** - `GET /api/books?category_id=2` (filter kategori Komik, 4 buku cocok)

```jsonc
{
  "data": [
    {
      "id": 1,
      "judul": "Si Juki: Kuliah Kerja Nyata",
      // ... field lain sama seperti di atas
      "category": { "id": 2, "nama_kategori": "Komik" }
    }
    // ... 3 item lainnya
  ],
  "links": { /* ... */ },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 10, "to": 4, "total": 4 /* ... */ }
}
```

Filter yang tidak cocok dengan data apa pun tetap mengembalikan `200 OK` dengan `data` kosong (contoh `GET /api/books?judul=Laskar%20Pelangi` → `"data": []`, `"total": 0`), bukan `404`.

---

### 2. GET `/api/books/{id}`

Detail satu buku beserta kategorinya.

**Response `200 OK`** - `GET /api/books/21`

```json
{
  "data": {
    "id": 21,
    "judul": "Bumi Manusia",
    "penulis": "Pramoedya Ananta Toer",
    "penerbit": "Hasta Mitra",
    "tahun_terbit": "1980",
    "isbn": "9789799731234",
    "stok": 3,
    "sampul": null,
    "category": {
      "id": 2,
      "nama_kategori": "Komik"
    },
    "created_at": "2026-11-23T14:07:09.000000Z",
    "updated_at": "2026-11-23T14:07:09.000000Z"
  }
}
```

**Response `404 Not Found`** - `GET /api/books/21` (setelah buku dihapus)

```json
{
  "message": "No query results for model [App\\Models\\Book] 21",
  "exception": "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException"
}
```

---

### 3. POST `/api/books`

Menambah buku baru. Validasi memakai `StoreBookRequest` yang sama dengan form web (Pertemuan 3).

**Body**

```json
{
  "judul": "Bumi Manusia",
  "penulis": "Pramoedya Ananta Toer",
  "penerbit": "Hasta Mitra",
  "tahun_terbit": 1980,
  "isbn": "9789799731234",
  "stok": 3,
  "category_id": 2
}
```

**Response `201 Created`**

```json
{
  "data": {
    "id": 21,
    "judul": "Bumi Manusia",
    "penulis": "Pramoedya Ananta Toer",
    "penerbit": "Hasta Mitra",
    "tahun_terbit": 1980,
    "isbn": "9789799731234",
    "stok": 3,
    "sampul": null,
    "category": {
      "id": 2,
      "nama_kategori": "Komik"
    },
    "created_at": "2026-11-23T14:07:09.000000Z",
    "updated_at": "2026-11-23T14:07:09.000000Z"
  }
}
```

> Catatan: di response `POST`/`PUT`, `tahun_terbit` bernilai angka (`1980`) karena diambil dari input yang baru disimpan. Saat dibaca ulang dari database (`GET`), nilainya string (`"1980"`) karena kolom bertipe `YEAR`.

**Body tidak valid** - `{}`

**Response `422 Unprocessable Content`**

```json
{
  "message": "Judul buku wajib diisi. (and 5 more errors)",
  "errors": {
    "judul": ["Judul buku wajib diisi."],
    "penulis": ["Nama penulis wajib diisi."],
    "penerbit": ["Nama penerbit wajib diisi."],
    "tahun_terbit": ["Tahun terbit wajib diisi."],
    "stok": ["Stok wajib diisi."],
    "category_id": ["Kategori wajib dipilih."]
  }
}
```

---

### 4. PUT `/api/books/{id}`

Memperbarui data buku. Validasi inline di Controller (tanpa pesan custom, sehingga pesan error memakai bahasa Inggris bawaan Laravel).

**Body** - `PUT /api/books/21`

```json
{
  "judul": "Bumi Manusia",
  "penulis": "Pramoedya Ananta Toer",
  "penerbit": "Hasta Mitra",
  "tahun_terbit": 1980,
  "isbn": "9789799731234",
  "stok": 10,
  "category_id": 2
}
```

**Response `200 OK`** (stok berubah dari 3 menjadi 10)

```json
{
  "data": {
    "id": 21,
    "judul": "Bumi Manusia",
    "penulis": "Pramoedya Ananta Toer",
    "penerbit": "Hasta Mitra",
    "tahun_terbit": 1980,
    "isbn": "9789799731234",
    "stok": 10,
    "sampul": null,
    "category": {
      "id": 2,
      "nama_kategori": "Komik"
    },
    "created_at": "2026-11-23T14:07:09.000000Z",
    "updated_at": "2026-11-23T14:07:10.000000Z"
  }
}
```

**Body tidak valid** - `{"judul": ""}`

**Response `422 Unprocessable Content`**

```json
{
  "message": "The judul field is required. (and 5 more errors)",
  "errors": {
    "judul": ["The judul field is required."],
    "penulis": ["The penulis field is required."],
    "penerbit": ["The penerbit field is required."],
    "tahun_terbit": ["The tahun terbit field is required."],
    "stok": ["The stok field is required."],
    "category_id": ["The category id field is required."]
  }
}
```

**Response `404 Not Found`** - ID buku tidak ada

```json
{
  "message": "No query results for model [App\\Models\\Book] 21",
  "exception": "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException"
}
```

---

### 5. DELETE `/api/books/{id}`

Menghapus buku.

**Response `200 OK`** - `DELETE /api/books/21`

```json
{
  "message": "Buku berhasil dihapus."
}
```

**Response `404 Not Found`** - `DELETE /api/books/21` dikirim lagi (buku sudah terhapus)

```json
{
  "message": "No query results for model [App\\Models\\Book] 21",
  "exception": "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException"
}
```

---

## Members

### 6. GET `/api/members`

Daftar anggota dengan pagination (read-only).

**Response `200 OK`**

```jsonc
{
  "data": [
    {
      "id": 1,
      "nama": "Hamima Ramadan",
      "nim": "1599400105",
      "email": "hamima.ramadan141@pens.ac.id",
      "nomor_telepon": "087110698419",
      "alamat": "Dk. Setia Budi No. 462, Pangkal Pinang 96639, Babel",
      "status": "aktif",
      "created_at": "2026-11-23T13:54:00.000000Z",
      "updated_at": "2026-11-23T13:54:00.000000Z"
    }
    // ... 9 item lainnya
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/members?page=1",
    "last": "http://127.0.0.1:8000/api/members?page=2",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/members?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 2,
    "links": [ /* ... */ ],
    "path": "http://127.0.0.1:8000/api/members",
    "per_page": 10,
    "to": 10,
    "total": 15
  }
}
```

---

### 7. GET `/api/members/{id}`

Detail satu anggota.

**Response `200 OK`** - `GET /api/members/1`

```json
{
  "data": {
    "id": 1,
    "nama": "Hamima Ramadan",
    "nim": "1599400105",
    "email": "hamima.ramadan141@pens.ac.id",
    "nomor_telepon": "087110698419",
    "alamat": "Dk. Setia Budi No. 462, Pangkal Pinang 96639, Babel",
    "status": "aktif",
    "created_at": "2026-11-23T13:54:00.000000Z",
    "updated_at": "2026-11-23T13:54:00.000000Z"
  }
}
```

**Response `404 Not Found`** - `GET /api/members/999`

```json
{
  "message": "No query results for model [App\\Models\\Member] 999",
  "exception": "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException"
}
```

---

## Loans

### 8. GET `/api/loans`

Daftar transaksi peminjaman beserta anggota, petugas, dan buku yang dipinjam. Relasi `member`, `user`, dan `loanItems.book` di-eager load untuk menghindari N+1 query. Field `user` ditampilkan dengan nama `petugas`, dan `loanItems` diratakan menjadi `books`.

**Response `200 OK`**

```jsonc
{
  "data": [
    {
      "id": 1,
      "member": {
        "id": 4,
        "nama": "Wulan Suwarno"
      },
      "petugas": {
        "id": 1,
        "name": "Bambang Sutrisno"
      },
      "tanggal_pinjam": "2026-11-09",
      "tanggal_kembali": "2026-11-16",
      "tanggal_dikembalikan": null,
      "status": "terlambat",
      "books": [
        { "id": 2, "judul": "Biologi Sel dan Molekuler" }
      ]
    }
    // ... 9 item lainnya
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/loans?page=1",
    "last": "http://127.0.0.1:8000/api/loans?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [ /* ... */ ],
    "path": "http://127.0.0.1:8000/api/loans",
    "per_page": 10,
    "to": 10,
    "total": 10
  }
}
```

---

### 9. POST `/api/loans`

Membuat transaksi peminjaman baru (satu transaksi bisa berisi beberapa buku). Karena API ini stateless dan tidak memakai session login, `user_id` (petugas pencatat) wajib dikirim di body - berbeda dengan form web yang mengisinya otomatis dari `auth()->id()`.

**Body**

```json
{
  "member_id": 1,
  "user_id": 1,
  "tanggal_pinjam": "2026-11-23",
  "tanggal_kembali": "2026-11-30",
  "book_ids": [1, 2]
}
```

**Response `201 Created`** (`status` terbaca `"dipinjam"` berkat `$loan->refresh()`)

```json
{
  "data": {
    "id": 11,
    "member": {
      "id": 1,
      "nama": "Hamima Ramadan"
    },
    "petugas": {
      "id": 1,
      "name": "Bambang Sutrisno"
    },
    "tanggal_pinjam": "2026-11-23",
    "tanggal_kembali": "2026-11-30",
    "tanggal_dikembalikan": null,
    "status": "dipinjam",
    "books": [
      { "id": 1, "judul": "Si Juki: Kuliah Kerja Nyata" },
      { "id": 2, "judul": "Biologi Sel dan Molekuler" }
    ]
  }
}
```

**Body tidak valid** - `{}`

**Response `422 Unprocessable Content`**

```json
{
  "message": "Anggota wajib dipilih. (and 4 more errors)",
  "errors": {
    "member_id": ["Anggota wajib dipilih."],
    "user_id": ["Petugas (user_id) wajib diisi."],
    "tanggal_pinjam": ["Tanggal pinjam wajib diisi."],
    "tanggal_kembali": ["Tanggal kembali wajib diisi."],
    "book_ids": ["Pilih minimal satu buku."]
  }
}
```

---

### 10. PUT `/api/loans/{id}/kembalikan`

Memproses pengembalian buku: `status` diubah menjadi `dikembalikan` dan `tanggal_dikembalikan` diisi tanggal hari ini. Tidak membutuhkan body.

**Response `200 OK`** - `PUT /api/loans/11/kembalikan`

```json
{
  "data": {
    "id": 11,
    "member": {
      "id": 1,
      "nama": "Hamima Ramadan"
    },
    "petugas": {
      "id": 1,
      "name": "Bambang Sutrisno"
    },
    "tanggal_pinjam": "2026-11-23",
    "tanggal_kembali": "2026-11-30",
    "tanggal_dikembalikan": "2026-11-23",
    "status": "dikembalikan",
    "books": [
      { "id": 1, "judul": "Si Juki: Kuliah Kerja Nyata" },
      { "id": 2, "judul": "Biologi Sel dan Molekuler" }
    ]
  }
}
```

**Response `404 Not Found`** - `PUT /api/loans/999/kembalikan`

```json
{
  "message": "No query results for model [App\\Models\\Loan] 999",
  "exception": "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException"
}
```

---

## Stats

### 11. GET `/api/stats`

Ringkasan statistik perpustakaan. Tidak memakai API Resource karena isinya angka ringkasan, bukan representasi satu Model. Endpoint ini juga dikonsumsi halaman Dashboard (Pertemuan 10).

**Response `200 OK`**

```json
{
  "total_buku": 20,
  "total_anggota": 15,
  "peminjaman_aktif": 4
}
```

`peminjaman_aktif` menghitung transaksi berstatus `dipinjam` saja (transaksi `terlambat` tidak ikut dihitung).
