# Berkontribusi

Terima kasih sudah tertarik! Kontribusi berupa laporan bug, usulan fitur, terjemahan, dokumentasi, dan kode sangat disambut.

## Etika bersama

Bersikaplah sopan dan konstruktif. Kritik ditujukan pada kode dan gagasan, bukan pada orangnya. Perilaku melecehkan tidak diterima.

## Melapor bug atau mengusulkan fitur

Buka [issue](../../issues) dengan templat yang tersedia. Sertakan versi WordPress, versi PHP, tema yang dipakai, langkah mereproduksi, dan hasil yang diharapkan. **Celah keamanan jangan dilaporkan lewat issue publik**; lihat [SECURITY.md](SECURITY.md).

## Menyiapkan lingkungan pengembangan

```bash
git clone https://github.com/alamthelibrarian/newspaper-archive.git
cd newspaper-archive
composer install        # memasang PHP_CodeSniffer + WordPress Coding Standards
composer lint           # php -l pada semua berkas
composer cs             # cek gaya kode
```

Letakkan folder di `wp-content/plugins/` pada WordPress lokal (mis. wp-env, LocalWP, atau Docker).

## Pedoman kode

- Ikuti [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
- Semua fungsi, konstanta, dan hook global memakai prefix `newspaper_archive_` / `NEWSPAPER_ARCHIVE_`.
- Escape semua keluaran (`esc_html`, `esc_attr`, `esc_url`) dan sanitasi semua masukan; periksa nonce dan kapabilitas.
- String antarmuka ditulis dalam bahasa Inggris dengan text domain `newspaper-archive`, lalu diterjemahkan di `languages/`.
- Perbarui `CHANGELOG.md` untuk setiap perubahan yang terlihat pengguna.

## Terjemahan

Berkas `languages/newspaper-archive.pot` adalah templatnya. Salin menjadi `newspaper-archive-<locale>.po`, terjemahkan (mis. dengan Poedit), lalu kompilasi ke `.mo`:

```bash
msgfmt -o languages/newspaper-archive-id_ID.mo languages/newspaper-archive-id_ID.po
```

## Pull request

1. Fork, lalu buat cabang dari `main` (`fix/judul-singkat` atau `feat/judul-singkat`).
2. Buat perubahan kecil dan terfokus; satu PR untuk satu tujuan.
3. Pastikan CI hijau, lalu jelaskan apa dan mengapa pada deskripsi PR.

Dengan berkontribusi, Anda setuju kontribusi Anda dilisensikan di bawah GPL-2.0-or-later.
