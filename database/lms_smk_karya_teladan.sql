CREATE DATABASE IF NOT EXISTS lms_smk_karya_teladan
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lms_smk_karya_teladan;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS aktivitas_log;
DROP TABLE IF EXISTS pengumuman;
DROP TABLE IF EXISTS materi;
DROP TABLE IF EXISTS nilai;
DROP TABLE IF EXISTS pengumpulan_tugas;
DROP TABLE IF EXISTS tugas;
DROP TABLE IF EXISTS presensi;
DROP TABLE IF EXISTS jadwal_pelajaran;
DROP TABLE IF EXISTS mata_pelajaran;
DROP TABLE IF EXISTS murid;
DROP TABLE IF EXISTS kelas;
DROP TABLE IF EXISTS guru;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'guru', 'murid') NOT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  profile_photo VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_status (status)
) ENGINE=InnoDB;

CREATE TABLE guru (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  nama_guru VARCHAR(120) NOT NULL,
  nip VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  no_hp VARCHAR(30) NULL,
  mata_pelajaran_utama VARCHAR(120) NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_guru_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_guru_status (status)
) ENGINE=InnoDB;

CREATE TABLE kelas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_kelas VARCHAR(80) NOT NULL,
  jurusan VARCHAR(120) NOT NULL,
  wali_kelas_id INT UNSIGNED NULL,
  jumlah_murid INT UNSIGNED NOT NULL DEFAULT 0,
  tahun_ajaran VARCHAR(20) NOT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_kelas_wali_guru FOREIGN KEY (wali_kelas_id) REFERENCES guru(id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_kelas_status (status),
  INDEX idx_kelas_wali (wali_kelas_id)
) ENGINE=InnoDB;

CREATE TABLE murid (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  kelas_id INT UNSIGNED NULL,
  nama_murid VARCHAR(120) NOT NULL,
  nis VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  no_hp VARCHAR(30) NULL,
  jurusan VARCHAR(120) NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_murid_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_murid_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_murid_kelas (kelas_id),
  INDEX idx_murid_status (status)
) ENGINE=InnoDB;

CREATE TABLE mata_pelajaran (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_mapel VARCHAR(30) NOT NULL UNIQUE,
  nama_mapel VARCHAR(120) NOT NULL,
  guru_id INT UNSIGNED NULL,
  kelas_id INT UNSIGNED NULL,
  semester ENUM('Ganjil', 'Genap') NOT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_mapel_guru FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_mapel_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_mapel_guru (guru_id),
  INDEX idx_mapel_kelas (kelas_id),
  INDEX idx_mapel_status (status)
) ENGINE=InnoDB;

CREATE TABLE jadwal_pelajaran (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hari ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') NOT NULL,
  jam_mulai TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  kelas_id INT UNSIGNED NOT NULL,
  mapel_id INT UNSIGNED NOT NULL,
  guru_id INT UNSIGNED NOT NULL,
  ruangan VARCHAR(80) NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_jadwal_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_jadwal_mapel FOREIGN KEY (mapel_id) REFERENCES mata_pelajaran(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_jadwal_guru FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_jadwal_kelas (kelas_id),
  INDEX idx_jadwal_guru (guru_id),
  INDEX idx_jadwal_hari (hari)
) ENGINE=InnoDB;

CREATE TABLE presensi (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jadwal_id INT UNSIGNED NOT NULL,
  murid_id INT UNSIGNED NOT NULL,
  guru_id INT UNSIGNED NOT NULL,
  tanggal DATE NOT NULL,
  status ENUM('Hadir', 'Izin', 'Sakit', 'Alpha') NOT NULL,
  keterangan TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_presensi_jadwal FOREIGN KEY (jadwal_id) REFERENCES jadwal_pelajaran(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_presensi_murid FOREIGN KEY (murid_id) REFERENCES murid(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_presensi_guru FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  UNIQUE KEY uq_presensi_per_hari (jadwal_id, murid_id, tanggal),
  INDEX idx_presensi_tanggal (tanggal)
) ENGINE=InnoDB;

CREATE TABLE tugas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  guru_id INT UNSIGNED NOT NULL,
  kelas_id INT UNSIGNED NOT NULL,
  mapel_id INT UNSIGNED NOT NULL,
  judul_tugas VARCHAR(180) NOT NULL,
  deskripsi TEXT NULL,
  file_tugas VARCHAR(255) NULL,
  deadline DATETIME NOT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tugas_guru FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_tugas_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_tugas_mapel FOREIGN KEY (mapel_id) REFERENCES mata_pelajaran(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_tugas_guru (guru_id),
  INDEX idx_tugas_kelas (kelas_id),
  INDEX idx_tugas_status (status)
) ENGINE=InnoDB;

CREATE TABLE pengumpulan_tugas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tugas_id INT UNSIGNED NOT NULL,
  murid_id INT UNSIGNED NOT NULL,
  file_jawaban VARCHAR(255) NULL,
  catatan_murid TEXT NULL,
  status ENUM('Belum dikerjakan', 'Sudah dikumpulkan', 'Sudah dinilai') NOT NULL DEFAULT 'Belum dikerjakan',
  nilai DECIMAL(5,2) NULL,
  komentar_guru TEXT NULL,
  submitted_at DATETIME NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pengumpulan_tugas FOREIGN KEY (tugas_id) REFERENCES tugas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pengumpulan_murid FOREIGN KEY (murid_id) REFERENCES murid(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  UNIQUE KEY uq_pengumpulan (tugas_id, murid_id),
  INDEX idx_pengumpulan_status (status)
) ENGINE=InnoDB;

CREATE TABLE nilai (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  murid_id INT UNSIGNED NOT NULL,
  guru_id INT UNSIGNED NOT NULL,
  kelas_id INT UNSIGNED NOT NULL,
  mapel_id INT UNSIGNED NOT NULL,
  tugas_id INT UNSIGNED NULL,
  nilai_tugas DECIMAL(5,2) NOT NULL DEFAULT 0,
  nilai_uts DECIMAL(5,2) NOT NULL DEFAULT 0,
  nilai_uas DECIMAL(5,2) NOT NULL DEFAULT 0,
  nilai_akhir DECIMAL(5,2) NOT NULL DEFAULT 0,
  grade ENUM('A', 'B', 'C', 'D') NULL,
  komentar TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_nilai_murid FOREIGN KEY (murid_id) REFERENCES murid(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_nilai_guru FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_nilai_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_nilai_mapel FOREIGN KEY (mapel_id) REFERENCES mata_pelajaran(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_nilai_tugas FOREIGN KEY (tugas_id) REFERENCES tugas(id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_nilai_murid (murid_id),
  INDEX idx_nilai_guru (guru_id),
  INDEX idx_nilai_kelas (kelas_id)
) ENGINE=InnoDB;

CREATE TABLE materi (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  guru_id INT UNSIGNED NOT NULL,
  kelas_id INT UNSIGNED NOT NULL,
  mapel_id INT UNSIGNED NOT NULL,
  judul_materi VARCHAR(180) NOT NULL,
  deskripsi TEXT NULL,
  file_materi VARCHAR(255) NULL,
  tanggal_upload DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_materi_guru FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_materi_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_materi_mapel FOREIGN KEY (mapel_id) REFERENCES mata_pelajaran(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_materi_guru (guru_id),
  INDEX idx_materi_kelas (kelas_id)
) ENGINE=InnoDB;

CREATE TABLE pengumuman (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  judul VARCHAR(180) NOT NULL,
  isi TEXT NOT NULL,
  target_role ENUM('all', 'admin', 'guru', 'murid') NOT NULL DEFAULT 'all',
  tanggal DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pengumuman_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_pengumuman_target (target_role),
  INDEX idx_pengumuman_tanggal (tanggal)
) ENGINE=InnoDB;

CREATE TABLE aktivitas_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  aktivitas VARCHAR(255) NOT NULL,
  role VARCHAR(30) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_log_user (user_id),
  INDEX idx_log_created_at (created_at)
) ENGINE=InnoDB;
