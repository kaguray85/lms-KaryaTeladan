USE lms_smk_karya_teladan;

INSERT INTO users (id, name, email, password, role, status) VALUES
(1, 'Administrator LMS', 'admin@smkkaryateladan.sch.id', '$2y$12$JE.FMRd4qxFNKS7uRYZX.OSvqvMNgRkHb5WLpkroa1H/Y4N4CfQJS', 'admin', 'active'),
(2, 'Guru Demo', 'guru@smkkaryateladan.sch.id', '$2y$12$TZ2LTzfCzGzVovzT.S4tA.W882iFmGRpSsohi66yjLX3EJKlpQ4G2', 'guru', 'active'),
(3, 'Murid Demo', 'murid@smkkaryateladan.sch.id', '$2y$12$E8/QbqnIsSj3QPyJvadXd.CjFWyTckGLsIMmN7D0Jqenu8NQQa2wS', 'murid', 'active');

INSERT INTO guru (id, user_id, nama_guru, nip, email, no_hp, mata_pelajaran_utama, status) VALUES
(1, 2, 'Guru Demo', 'GURU001', 'guru@smkkaryateladan.sch.id', '081234567890', 'Pemrograman Web', 'active');

INSERT INTO kelas (id, nama_kelas, jurusan, wali_kelas_id, jumlah_murid, tahun_ajaran, status) VALUES
(1, 'XII RPL 1', 'Rekayasa Perangkat Lunak', 1, 1, '2025/2026', 'active');

INSERT INTO murid (id, user_id, kelas_id, nama_murid, nis, email, no_hp, jurusan, status) VALUES
(1, 3, 1, 'Murid Demo', 'MURID001', 'murid@smkkaryateladan.sch.id', '081234567891', 'Rekayasa Perangkat Lunak', 'active');

INSERT INTO mata_pelajaran (id, kode_mapel, nama_mapel, guru_id, kelas_id, semester, status) VALUES
(1, 'PWEB-XII', 'Pemrograman Web', 1, 1, 'Ganjil', 'active');

INSERT INTO jadwal_pelajaran (id, hari, jam_mulai, jam_selesai, kelas_id, mapel_id, guru_id, ruangan, status) VALUES
(1, 'Senin', '08:00:00', '09:30:00', 1, 1, 1, 'Lab RPL', 'active');

INSERT INTO pengumuman (user_id, judul, isi, target_role, tanggal) VALUES
(1, 'Selamat Datang di LMS SMK Karya Teladan', 'Sistem LMS tahap awal sudah aktif untuk admin, guru, dan murid.', 'all', CURDATE());

INSERT INTO aktivitas_log (user_id, aktivitas, role) VALUES
(1, 'Seed data awal berhasil dibuat', 'admin');
