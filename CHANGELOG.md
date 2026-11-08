# Changelog

## [0.3.0] - 2026-11-09

### Keamanan
- PDF edisi khusus anggota dipindahkan ke direktori penyimpanan terproteksi dan disajikan melalui endpoint dengan pemeriksaan akses.
- PDF publik tetap menggunakan URL Media Library seperti biasa.
- Menambahkan aturan proteksi Apache/IIS, dokumentasi konfigurasi Nginx, dan pembatasan satu lampiran per edisi.
- Menambahkan pemulihan lokasi berkas saat edisi anggota diubah menjadi publik.


Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan [Semantic Versioning](https://semver.org/lang/id/).

## [0.2.0] - 2026-11-08

Rilis publik pertama.

### Ditambahkan
- Tipe konten `newspaper_issue`, taksonomi `newspaper_title`, dan metadata edisi (tanggal, edisi, halaman, PDF, hak akses).
- Shortcode `[newspaper_archive]` dengan pencarian, filter judul/tahun, dan penomoran halaman.
- Endpoint PHP untuk edisi khusus anggota.
- Meta tag Dublin Core, kolom meta di REST API, kolom tanggal yang dapat diurutkan di admin.
- Terjemahan: sumber Inggris dan `id_ID`.
- `uninstall.php` yang aman (data tidak dihapus kecuali `NEWSPAPER_ARCHIVE_REMOVE_DATA` bernilai `true`).
