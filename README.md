# Newspaper Archive

Plugin WordPress untuk menerbitkan **koleksi koran digital (PDF)** milik perpustakaan atau arsip: metadata per edisi, pencarian, filter judul dan tahun, serta pembaca PDF bawaan.

*English: a WordPress plugin for libraries and archives to publish digital newspaper issues (PDF) with metadata, search, title/year filters and a built-in PDF reader. The interface is translation-ready (English source, Indonesian included).*

[![CI](https://github.com/alamthelibrarian/newspaper-archive/actions/workflows/ci.yml/badge.svg)](https://github.com/alamthelibrarian/newspaper-archive/actions/workflows/ci.yml)
[![License: GPL v2+](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)

## Fitur

- Tipe konten **Edisi Koran** dan taksonomi **Judul Koran** (mis. Suara Merdeka, Kedaulatan Rakyat).
- Metadata per edisi: tanggal terbit, edisi/nomor, jumlah halaman, berkas PDF, dan hak akses (publik atau khusus anggota).
- Shortcode `[newspaper_archive]` dengan pencarian, filter judul dan tahun, kartu sampul, serta penomoran halaman.
- Halaman edisi dengan pembaca PDF tertanam dan tombol unduh.
- Edisi khusus anggota dilayani lewat endpoint PHP yang memeriksa status login.
- Meta tag **Dublin Core** pada halaman edisi; seluruh kolom tersedia lewat REST API.
- Antarmuka dapat diterjemahkan (Inggris dan Indonesia sudah disertakan).

## Instalasi

1. Unduh `newspaper-archive-x.y.z.zip` dari halaman [Releases](../../releases).
2. Di WordPress: **Plugins → Add New → Upload Plugin**, pilih zip, lalu **Activate**.
3. Buka menu **Newspaper Archive → Tambah Edisi** (tampil sebagai **Arsip Koran** bila bahasa admin Indonesia), isi metadata dan pilih berkas PDF. Gambar unggulan dipakai sebagai sampul.
4. Tambahkan nama koran lewat **Newspaper Archive → Judul Koran**.
5. Buat halaman dan isi dengan shortcode `[newspaper_archive]`.

Kebutuhan: WordPress 6.0+ dan PHP 7.4+.

## Shortcode

```
[newspaper_archive]
[newspaper_archive per_page="20" title="suara-merdeka" show_filter="no"]
```

| Atribut | Bawaan | Keterangan |
|---|---|---|
| `per_page` | `12` | Jumlah edisi per halaman |
| `title` | kosong | Slug judul koran untuk membatasi daftar |
| `show_filter` | `yes` | `no` menyembunyikan formulir pencarian |

Arsip otomatis: `/newspaper-issues/` dan `/newspaper-title/{slug}/`. Slug dapat diubah lewat filter `newspaper_archive_collection_slug`, `newspaper_archive_issue_slug`, dan `newspaper_archive_title_slug`.

## Catatan keamanan: edisi khusus anggota

Edisi "khusus anggota" dilayani lewat pemeriksaan login di plugin, sehingga tombol dan pembaca tidak pernah menampilkan URL langsung. Namun berkas PDF tetap berada di folder Media Library biasa, jadi **siapa pun yang mengetahui URL persis berkas itu masih bisa membukanya**. Untuk materi yang benar-benar rahasia, batasi akses langsung ke berkas PDF di level server web (Nginx/Apache). Developer dapat mengubah aturan akses lewat filter `newspaper_archive_can_access`.

## Menghapus plugin

Secara bawaan **tidak ada data yang dihapus** saat plugin dihapus. Untuk ikut menghapus edisi dan judul koran, tambahkan ini ke `wp-config.php` sebelum menghapus plugin:

```php
define( 'NEWSPAPER_ARCHIVE_REMOVE_DATA', true );
```

Berkas PDF di Media Library tidak pernah dihapus.

## Rencana pengembangan

- Pencarian teks penuh dari hasil OCR.
- Impor massal (CSV/folder PDF) dan pembuatan sampul otomatis.
- Navigasi kalender per tanggal.
- Ekspor metadata Dublin Core/MARC dan pembaca PDF.js per halaman.

## Berkontribusi

Lihat [CONTRIBUTING.md](CONTRIBUTING.md). Laporan celah keamanan: [SECURITY.md](SECURITY.md).

## Lisensi

[GPL-2.0-or-later](LICENSE).
