# Kebijakan keamanan

## Versi yang didukung

Hanya rilis stabil terbaru yang menerima perbaikan keamanan.

## Perlindungan berkas PDF khusus anggota

Plugin menyimpan PDF edisi anggota di direktori `newspaper-archive-private` di dalam direktori uploads WordPress. Plugin membuat berkas penolakan akses untuk Apache (`.htaccess`) dan IIS (`web.config`). PDF publik tidak dipindahkan dan tetap menggunakan URL Media Library WordPress.

**Nginx tidak membaca `.htaccess`.** Pada server Nginx, administrator harus menambahkan aturan pada konfigurasi virtual host/server. Sesuaikan prefix jika lokasi uploads WordPress Anda berbeda:

```nginx
location ^~ /wp-content/uploads/newspaper-archive-private/ {
    deny all;
    return 403;
}
```

Muat ulang konfigurasi Nginx setelah perubahan. Jika WordPress memakai custom uploads path, multisite, alias, atau CDN, sesuaikan aturan dengan URL publik direktori uploads yang sebenarnya. Uji URL langsung salah satu PDF anggota dalam sesi logout; hasilnya harus 403 atau 404, bukan isi PDF.

Untuk instalasi Apache, pastikan `AllowOverride` mengizinkan aturan akses pada direktori uploads. Jika aturan `.htaccess` tidak diterapkan oleh server, PDF anggota dapat tetap diakses langsung. Untuk lingkungan dengan kebutuhan kerahasiaan tinggi, lokasi penyimpanan di luar document root adalah pilihan yang lebih kuat dan harus disiapkan oleh administrator server.

Setelah memperbarui plugin, buka dan simpan ulang setiap edisi anggota lama agar PDF lama dipindahkan dari lokasi Media Library publik. Buat backup sebelum migrasi. Periksa juga cache/CDN dan hapus salinan publik lama jika pernah disimpan di sana.

## Melaporkan celah keamanan

Mohon **jangan** membuka issue publik. Gunakan salah satu cara berikut:

1. Fitur **Report a vulnerability** pada tab *Security* repositori GitHub ini (private vulnerability reporting), atau
2. Kirim surel ke saiful@alam.web.id dengan subjek `[SECURITY] newspaper-archive`.

Sertakan deskripsi, langkah reproduksi, dan dampak yang Anda perkirakan. Kami berusaha menanggapi dalam 7 hari dan mengumumkan perbaikan setelah rilis tersedia.
