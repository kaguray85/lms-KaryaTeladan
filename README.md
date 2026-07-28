# LMS-SMK Karya Teladan

LMS-SMK Karya Teladan adalah project **Learning Management System berbasis web** menggunakan **PHP Native, MySQL, REST API sederhana, session authentication, dan role-based access control** untuk tiga jenis user: **admin, guru, dan murid**.

Project ini dibuat modular agar mudah dipahami pemula, tetapi tetap memakai pondasi arsitektur yang rapi: pemisahan config, middleware, helper, model, API, frontend, database, dan storage.

---

## 1. Stack Teknologi

- Frontend: HTML, CSS, JavaScript, Fetch API
- Backend: PHP Native
- Database: MySQL
- Database Access: PDO Prepared Statement
- Authentication: PHP Session
- Email: PHPMailer melalui SMTP
- Server Lokal: XAMPP atau Laragon
- Database Manager: phpMyAdmin

Framework besar seperti Laravel, React, Vue, dan Node.js tidak digunakan agar project mudah dipelajari.

---

## 2. Struktur Folder

```txt
lms-smk-karya-teladan/
├── api/
│   ├── auth/
│   ├── admin/
│   ├── guru/
│   ├── murid/
│   └── uploads/
├── app/
│   ├── config/
│   ├── middleware/
│   ├── helpers/
│   └── models/
├── public/
│   ├── admin/
│   ├── guru/
│   ├── murid/
│   └── assets/
├── database/
│   ├── lms_smk_karya_teladan.sql
│   └── seed.sql
├── docs/
│   ├── TESTING_CHECKLIST.md
│   └── SECURITY_NOTES.md
├── storage/
│   ├── materi/
│   ├── tugas/
│   └── profile/
├── README.md
└── .htaccess
```

---

## 3. Cara Install di XAMPP

1. Ekstrak folder project.
2. Copy folder `lms-smk-karya-teladan` ke:

```txt
C:/xampp/htdocs/
```

3. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
4. Buka phpMyAdmin:

```txt
http://localhost/phpmyadmin
```

5. Import database:

```txt
database/lms_smk_karya_teladan.sql
```

6. Import seed data:

```txt
database/seed.sql
```

7. Pasang PHPMailer dari folder project:

```txt
composer install
```

8. Salin `.env.example` menjadi `.env`, lalu isi konfigurasi database dan SMTP.

9. Untuk database yang sudah pernah di-import, jalankan migrasi OTP:

```txt
database/password_reset_otps.sql
```

10. Buka project:

```txt
http://localhost/lms-smk-karya-teladan/public/
```

---

## 4. Akun Login Default

### Admin

```txt
Email: admin@smkkaryateladan.sch.id
Password: admin123
```

### Guru

```txt
Email: guru@smkkaryateladan.sch.id
Password: guru123
```

### Murid

```txt
Email: murid@smkkaryateladan.sch.id
Password: murid123
```

---

## 5. Fitur Utama

### Admin

- Dashboard admin
- CRUD guru
- CRUD murid
- CRUD kelas
- CRUD mata pelajaran
- CRUD jadwal pelajaran
- Melihat presensi
- Melihat nilai
- Melihat tugas
- Melihat materi
- Membuat dan menghapus pengumuman
- Mengelola profil pribadi
- Logout

### Guru

- Dashboard guru
- Melihat jadwal mengajar
- Mengisi presensi murid
- Memberi nilai
- Membuat tugas
- Melihat dan menilai pengumpulan tugas
- Upload materi
- Melihat pengumuman
- Mengelola profil pribadi
- Logout

### Murid

- Dashboard murid
- Melihat jadwal kelas
- Melihat presensi pribadi
- Melihat nilai pribadi
- Melihat tugas
- Mengumpulkan tugas
- Melihat materi
- Melihat pengumuman
- Mengelola profil pribadi
- Logout

---

## 6. Endpoint API Utama

### Authentication

```txt
POST /api/auth/login.php
POST /api/auth/logout.php
GET  /api/auth/me.php
```

### Admin

```txt
GET  /api/admin/dashboard.php
GET  /api/admin/guru.php
POST /api/admin/guru.php
GET  /api/admin/murid.php
POST /api/admin/murid.php
GET  /api/admin/kelas.php
POST /api/admin/kelas.php
GET  /api/admin/mapel.php
POST /api/admin/mapel.php
GET  /api/admin/jadwal.php
POST /api/admin/jadwal.php
GET  /api/admin/presensi.php
GET  /api/admin/nilai.php
GET  /api/admin/tugas.php
GET  /api/admin/materi.php
GET  /api/admin/pengumuman.php
POST /api/admin/pengumuman.php
GET  /api/admin/profil.php
POST /api/admin/profil.php
```

### Guru

```txt
GET  /api/guru/dashboard.php
GET  /api/guru/jadwal.php
GET  /api/guru/presensi.php
POST /api/guru/presensi.php
GET  /api/guru/nilai.php
POST /api/guru/nilai.php
GET  /api/guru/tugas.php
POST /api/guru/tugas.php
GET  /api/guru/materi.php
POST /api/guru/materi.php
GET  /api/guru/pengumuman.php
GET  /api/guru/profil.php
POST /api/guru/profil.php
```

### Murid

```txt
GET  /api/murid/dashboard.php
GET  /api/murid/jadwal.php
GET  /api/murid/presensi.php
GET  /api/murid/nilai.php
GET  /api/murid/tugas.php
POST /api/murid/tugas.php
GET  /api/murid/materi.php
GET  /api/murid/pengumuman.php
GET  /api/murid/profil.php
POST /api/murid/profil.php
```

### Upload

```txt
POST /api/uploads/upload-materi.php
POST /api/uploads/upload-tugas.php
POST /api/uploads/upload-profile.php
```

---

## 7. Format Response API

### Berhasil

```json
{
  "status": true,
  "message": "Data berhasil diproses",
  "data": [],
  "errors": []
}
```

### Gagal

```json
{
  "status": false,
  "message": "Pesan error",
  "data": [],
  "errors": []
}
```

---

## 8. Cara Testing Login dan Role

1. Buka halaman login.
2. Login sebagai admin.
3. Pastikan diarahkan ke `/public/admin/dashboard.html`.
4. Coba buka `/public/guru/dashboard.html` saat masih login admin.
5. Sistem akan mengarahkan user ke dashboard sesuai role.
6. Logout.
7. Ulangi untuk guru dan murid.

---

## 9. Cara Testing CRUD

1. Login sebagai admin.
2. Buka menu **Data Guru**.
3. Tambah guru baru.
4. Coba pakai email atau NIP yang sama, sistem harus menolak.
5. Edit guru tanpa isi password, password lama harus tetap aman.
6. Nonaktifkan guru.
7. Ulangi pola yang sama untuk murid, kelas, mapel, dan jadwal.

---

## 10. Upload File

Batas upload maksimal adalah **5MB**.

### Materi

```txt
pdf, doc, docx, ppt, pptx
```

### Tugas Murid

```txt
pdf, doc, docx, zip, rar
```

### Foto Profil

```txt
jpg, jpeg, png, webp
```

---

## 11. Cara Menambahkan Fitur Baru

Pola pengembangan yang disarankan:

1. Buat atau update tabel database.
2. Buat model di `app/models/`.
3. Buat API di `api/{role}/`.
4. Tambahkan validasi input.
5. Lindungi endpoint dengan middleware role.
6. Buat halaman HTML di `public/{role}/`.
7. Buat JavaScript di `public/assets/js/`.
8. Tambahkan link menu di sidebar.
9. Test endpoint melalui browser/devtools.
10. Catat perubahan di README.

---

## 12. Catatan Keamanan

Keamanan dasar yang sudah diterapkan:

- Password memakai `password_hash()`.
- Login memakai `password_verify()`.
- Session memakai cookie `HttpOnly` dan `SameSite=Lax`.
- Session ID diregenerasi setelah login.
- API dilindungi middleware auth.
- Role diproteksi middleware admin/guru/murid.
- Database memakai PDO prepared statement.
- Upload file dibatasi ekstensi dan ukuran.
- Error database mentah tidak ditampilkan ke user.
- Data penting memakai soft delete.

Untuk produksi, baca juga:

```txt
docs/SECURITY_NOTES.md
```

---

## 13. Troubleshooting Error Umum

### Halaman 404

Pastikan folder project berada di:

```txt
C:/xampp/htdocs/lms-smk-karya-teladan
```

### Database gagal terkoneksi

Cek file:

```txt
app/config/database.php
```

Pastikan nama database:

```txt
lms_smk_karya_teladan
```

### Login selalu gagal

Pastikan `seed.sql` sudah diimport setelah file struktur database.

### Upload gagal

Cek:

- Folder `storage/` ada.
- Ukuran file tidak lebih dari 5MB.
- Format file sesuai whitelist.
- Permission folder storage mengizinkan proses tulis.

### Fetch API gagal

Cek nilai ini di:

```txt
public/assets/js/api.js
```

```js
const APP_BASE = '/lms-smk-karya-teladan';
```

Jika nama folder project diganti, nilai tersebut juga harus diganti.

---

## 14. Checklist Testing

Checklist lengkap tersedia di:

```txt
docs/TESTING_CHECKLIST.md
```

---

## 15. Status Project

Project ini sudah mencakup lima tahap pengerjaan:

1. Struktur folder, database, auth, middleware, login.
2. CRUD guru dan murid.
3. CRUD kelas, mata pelajaran, dan jadwal.
4. Presensi, nilai, tugas, materi, dan pengumuman.
5. Finishing UI, profil, upload foto profil, validasi tambahan, dokumentasi, dan checklist testing.

Project sudah layak dijadikan pondasi LMS untuk dikembangkan lebih lanjut.
