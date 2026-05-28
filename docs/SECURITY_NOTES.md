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

## Saran Jika Project Naik ke Produksi

1. Gunakan HTTPS wajib.
2. Tambahkan CSRF token untuk semua request POST.
3. Tambahkan rate limit login.
4. Tambahkan audit log lebih detail.
5. Simpan upload di luar public web root jika server memungkinkan.
6. Tambahkan backup database otomatis.
7. Gunakan environment variable untuk credential database.
8. Tambahkan sistem reset password via email.
9. Tambahkan Content Security Policy.
10. Jalankan penetration testing dasar sebelum digunakan secara luas.
