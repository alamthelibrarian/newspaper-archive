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

- **Publik:** PDF tetap berada di lokasi Media Library dan menggunakan URL WordPress biasa. Pembaca tertanam dan tautan unduh publik tetap bekerja normal.
- **Khusus anggota:** saat edisi disimpan, plugin memindahkan PDF ke `wp-content/uploads/newspaper-archive-private/` (atau direktori uploads yang dikonfigurasi), membuat aturan penolakan untuk Apache dan IIS, lalu hanya menyajikannya melalui endpoint PHP setelah pemeriksaan login.
- Satu lampiran PDF hanya boleh digunakan oleh satu edisi agar pengaturan publik dan anggota tidak saling bertentangan.
- Jika server tidak mengizinkan pembuatan direktori atau pemindahan berkas, plugin tidak akan menyimpan lampiran tersebut sebagai edisi anggota. Pastikan proses PHP memiliki izin tulis yang sesuai.
- **Nginx memerlukan aturan server tersendiri** karena Nginx tidak membaca `.htaccess`. Tambahkan aturan yang sesuai dengan lokasi uploads pada konfigurasi server (contoh di [SECURITY.md](SECURITY.md)) sebelum mengandalkan penyimpanan anggota.
- Edisi anggota lama yang PDF-nya masih berada di Media Library publik harus dibuka dan disimpan ulang setelah pembaruan agar berkasnya dipindahkan. Buat cadangan terlebih dahulu.
- Filter `newspaper_archive_can_access` tetap tersedia untuk menyesuaikan pemeriksaan izin di tingkat aplikasi.

Perlindungan ini membatasi akses langsung melalui web server yang dikonfigurasi dengan benar. Administrator tetap perlu memastikan backup, cache/CDN, dan salinan lama dari URL publik tidak membuat salinan PDF tetap terbuka.

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
