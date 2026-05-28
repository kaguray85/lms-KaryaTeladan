# Testing Checklist LMS-SMK Karya Teladan

Gunakan checklist ini setelah project dipasang di XAMPP atau Laragon.

## 1. Setup Awal

- [ ] Folder project berada di `htdocs/lms-smk-karya-teladan`.
- [ ] Apache dan MySQL aktif.
- [ ] Database `lms_smk_karya_teladan` berhasil dibuat.
- [ ] File `database/lms_smk_karya_teladan.sql` berhasil diimport.
- [ ] File `database/seed.sql` berhasil diimport.
- [ ] URL `http://localhost/lms-smk-karya-teladan/public/` membuka halaman login.

## 2. Authentication dan Role

- [ ] Login admin berhasil dan diarahkan ke dashboard admin.
- [ ] Login guru berhasil dan diarahkan ke dashboard guru.
- [ ] Login murid berhasil dan diarahkan ke dashboard murid.
- [ ] User yang belum login tidak bisa membuka halaman dashboard.
- [ ] Guru tidak bisa membuka halaman admin.
- [ ] Murid tidak bisa membuka halaman guru/admin.
- [ ] Logout menghancurkan session.

## 3. CRUD Admin

- [ ] Admin bisa menambah guru.
- [ ] Admin bisa mengedit guru tanpa mengganti password.
- [ ] Admin bisa menonaktifkan guru.
- [ ] Email guru duplikat ditolak.
- [ ] NIP duplikat ditolak.
- [ ] Admin bisa menambah murid.
- [ ] Admin bisa mengedit murid tanpa mengganti password.
- [ ] Admin bisa menonaktifkan murid.
- [ ] Email murid duplikat ditolak.
- [ ] NIS duplikat ditolak.
- [ ] Jumlah murid pada kelas otomatis berubah.

## 4. Akademik

- [ ] Kelas bisa ditambah, diedit, dan dinonaktifkan.
- [ ] Mata pelajaran bisa dikaitkan ke guru dan kelas.
- [ ] Jadwal bentrok guru ditolak.
- [ ] Jadwal bentrok kelas ditolak.
- [ ] Jadwal bentrok ruangan ditolak.
- [ ] Guru hanya melihat jadwal miliknya.
- [ ] Murid hanya melihat jadwal kelasnya.

## 5. Presensi, Nilai, Tugas, Materi

- [ ] Guru bisa mengisi presensi murid berdasarkan jadwal.
- [ ] Murid hanya melihat presensi dirinya.
- [ ] Guru bisa memberi nilai.
- [ ] Nilai akhir dan grade otomatis benar.
- [ ] Murid hanya melihat nilai dirinya.
- [ ] Guru bisa membuat tugas dengan file.
- [ ] Murid bisa mengumpulkan jawaban tugas.
- [ ] Guru bisa memberi nilai dan komentar pada pengumpulan.
- [ ] Guru bisa upload materi.
- [ ] Murid bisa download materi sesuai kelasnya.

## 6. Profil dan Upload

- [ ] Admin bisa update nama, email, foto profil, dan password.
- [ ] Guru bisa update nama, email, nomor HP, mapel utama, foto profil, dan password.
- [ ] Murid bisa update nama, email, nomor HP, foto profil, dan password.
- [ ] Upload foto selain JPG/JPEG/PNG/WEBP ditolak.
- [ ] Upload file lebih dari 5MB ditolak.
- [ ] Password lama salah ditolak saat mengganti password.

## 7. UI/UX

- [ ] Sidebar responsif berfungsi di layar kecil.
- [ ] Dark mode dan light mode tersimpan sebagai preferensi UI.
- [ ] Loading state dan pesan error tampil saat API gagal.
- [ ] Tabel tidak rusak saat data panjang.
