# Security Notes LMS-SMK Karya Teladan

Catatan ini menjelaskan keamanan dasar yang sudah diterapkan dan batasan yang perlu diperhatikan.

## Sudah Diterapkan

1. Password disimpan menggunakan `password_hash()`.
2. Login memakai `password_verify()`.
3. Session cookie memakai `HttpOnly` dan `SameSite=Lax`.
4. Session ID diregenerasi setelah login.
5. API dilindungi middleware authentication.
6. Role admin, guru, dan murid diproteksi middleware masing-masing.
7. Query database memakai PDO prepared statement.
8. Data penting memakai soft delete melalui kolom `status`.
9. Upload file dibatasi ekstensi dan ukuran maksimal 5MB.
10. Error database mentah tidak ditampilkan langsung ke user.
11. Security headers dasar diterapkan melalui `.htaccess`, termasuk CSP,
    `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, dan
    `Permissions-Policy`.
12. Fitur lupa password tersedia dengan OTP 6 digit acak, OTP disimpan dalam
    bentuk hash, kedaluwarsa setelah 5 menit, maksimal 5 percobaan, cooldown
    permintaan 60 detik, dan hanya dapat digunakan satu kali.
13. OTP reset password dikirim melalui PHPMailer dan konfigurasi SMTP dibaca
    dari environment variable.
14. Login dilindungi Google reCAPTCHA checkbox. Site Key berada di frontend,
    sedangkan Secret Key hanya berada di environment server.

## Roadmap Saran Jika Project Naik ke Produksi

Roadmap ini berisi saran peningkatan keamanan dan operasional untuk LMS Karya
Teladan. Sebagian poin sudah diterapkan secara bertahap, sedangkan poin lain
masih memerlukan perubahan struktur, database, endpoint, atau flow aplikasi.

### Prioritas Tinggi

1. Wajibkan HTTPS untuk semua akses LMS.
2. Redirect otomatis dari HTTP ke HTTPS pada server production.
3. Pastikan cookie session memakai `Secure`, `HttpOnly`, dan `SameSite`.
4. Tambahkan CSRF token untuk semua request `POST`, termasuk login, upload,
   tambah data, edit data, hapus data, reset password, dan ubah password.
5. Tambahkan rate limit login berdasarkan kombinasi IP address dan email.
6. Terapkan lock sementara jika login gagal berkali-kali, misalnya 5 kali
   dalam 10 menit dengan durasi lock minimal 15 menit.
7. Gunakan pesan error login yang umum, misalnya `Email atau password tidak
   valid.`, agar sistem tidak membocorkan apakah email terdaftar.
8. Pindahkan credential database dan konfigurasi SMTP ke environment variable.
9. Pindahkan Secret Key reCAPTCHA dari konfigurasi PHP ke environment variable.
10. Tambahkan fitur reset password via email dengan OTP acak, OTP hash,
   masa berlaku terbatas, dan hanya dapat digunakan satu kali. Implementasi
   dasar sudah tersedia; konfigurasi SMTP production tetap perlu disiapkan agar
   email benar-benar terkirim di server hosting.

### Prioritas Menengah

1. Tambahkan audit log detail untuk aktivitas penting seperti login berhasil,
   login gagal, logout, CRUD data guru, CRUD data murid, input nilai, input
   presensi, upload file, download file penting, perubahan password, perubahan
   role, dan aktivitas reset password.
2. Audit log sebaiknya mencatat user, role, aktivitas, modul, deskripsi singkat,
   IP address, user agent, status sukses/gagal, dan waktu aktivitas.
3. Jangan menyimpan password, token reset asli, atau data sensitif berlebihan di
   audit log.
4. Simpan file upload di luar `public` jika server memungkinkan.
5. Akses file sebaiknya melewati download handler yang memvalidasi login dan
   permission user.
6. Validasi file upload berdasarkan ekstensi, MIME type, ukuran file, dan nama
   server acak atau UUID.
7. Tambahkan Content Security Policy untuk membatasi sumber script, style,
   gambar, font, koneksi, form action, base URI, dan frame ancestor. CSP dasar
   sudah aktif melalui `.htaccess`, tetapi tetap perlu diuji lagi saat ada CDN
   atau script eksternal baru.

### Prioritas Lanjutan

1. Tambahkan backup database otomatis minimal 1 kali sehari.
2. Terapkan retention policy untuk menyimpan backup 7 sampai 30 hari terakhir.
3. Tambahkan backup mingguan untuk file upload jika memungkinkan.
4. Catat status backup berhasil atau gagal ke audit log.
5. Tambahkan dashboard admin untuk melihat status backup terakhir.
6. Tambahkan export audit log jika dibutuhkan untuk pemeriksaan rutin.
7. Tambahkan notifikasi email jika backup otomatis gagal.
8. Jalankan penetration testing dasar sebelum digunakan secara luas.

## Perubahan yang Perlu Persetujuan Terlebih Dahulu

Beberapa saran di atas tidak bisa diterapkan hanya dengan perubahan kecil,
karena akan menyentuh struktur aplikasi, database, atau konfigurasi server.
Sebelum implementasi, perubahan berikut perlu disetujui terlebih dahulu:

1. Menambah tabel database seperti `audit_logs`, `login_attempts`,
   `password_resets`, atau `backups`.
2. Menambah atau mengubah endpoint seperti forgot password, reset password,
   audit log admin, backup admin, dan download file terproteksi.
3. Memindahkan lokasi upload dari folder yang sekarang ke folder non-public.
4. Menambahkan file konfigurasi baru seperti `.env`, `.env.example`, atau
   konfigurasi mail/security terpisah.
5. Mengubah cara koneksi database membaca credential.
6. Mengubah flow login, logout, session, reset password, atau upload file.
7. Mengaktifkan HTTPS, HSTS, dan header security di server production.

## Acceptance Criteria Umum untuk Enhancement

1. Semua fitur lama Admin, Guru, dan Murid tetap berjalan normal.
2. Semua request `POST` terlindungi CSRF token.
3. Login memiliki proteksi rate limit dan lock sementara.
4. Password dan token rahasia tersimpan secara aman.
5. Credential database tidak hardcoded di source code.
6. Reset password via email berjalan dengan pesan yang tidak membocorkan status
   email.
7. Audit log mencatat aktivitas penting tanpa menyimpan rahasia.
8. Upload file divalidasi dan hanya dapat diakses oleh user yang berhak.
9. Backup database otomatis berhasil dibuat dan statusnya tercatat.
10. Content Security Policy aktif tanpa merusak tampilan atau fungsi LMS.
