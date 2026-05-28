<?php
class Tugas
{
    public static function all(PDO $db, array $filters = []): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['guru_id'])) { $where[] = 't.guru_id = :guru_id'; $params[':guru_id'] = (int)$filters['guru_id']; }
        if (!empty($filters['kelas_id'])) { $where[] = 't.kelas_id = :kelas_id'; $params[':kelas_id'] = (int)$filters['kelas_id']; }
        if (!empty($filters['status'])) { $where[] = 't.status = :status'; $params[':status'] = $filters['status']; }
        if (!empty($filters['search'])) { $where[] = '(t.judul_tugas LIKE :search OR mp.nama_mapel LIKE :search OR k.nama_kelas LIKE :search OR g.nama_guru LIKE :search)'; $params[':search'] = '%' . $filters['search'] . '%'; }

        $sql = "SELECT t.*, g.nama_guru, k.nama_kelas, k.jurusan, mp.nama_mapel,
                       (SELECT COUNT(*) FROM pengumpulan_tugas pt WHERE pt.tugas_id = t.id AND pt.status IN ('Sudah dikumpulkan','Sudah dinilai')) AS total_pengumpulan,
                       (SELECT COUNT(*) FROM murid m WHERE m.kelas_id = t.kelas_id AND m.status = 'active') AS total_murid
                FROM tugas t
                INNER JOIN guru g ON g.id = t.guru_id
                INNER JOIN kelas k ON k.id = t.kelas_id
                INNER JOIN mata_pelajaran mp ON mp.id = t.mapel_id";
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY t.deadline DESC, t.created_at DESC';
        $stmt = $db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }

    public static function forMurid(PDO $db, int $muridId, int $kelasId): array
    {
        $stmt = $db->prepare("SELECT t.*, g.nama_guru, k.nama_kelas, mp.nama_mapel,
                                     pt.id AS pengumpulan_id, pt.file_jawaban, pt.catatan_murid, pt.status AS status_pengumpulan,
                                     pt.nilai, pt.komentar_guru, pt.submitted_at
                              FROM tugas t
                              INNER JOIN guru g ON g.id = t.guru_id
                              INNER JOIN kelas k ON k.id = t.kelas_id
                              INNER JOIN mata_pelajaran mp ON mp.id = t.mapel_id
                              LEFT JOIN pengumpulan_tugas pt ON pt.tugas_id = t.id AND pt.murid_id = :murid_id
                              WHERE t.kelas_id = :kelas_id AND t.status = 'active'
                              ORDER BY t.deadline ASC");
        $stmt->execute([':murid_id'=>$muridId, ':kelas_id'=>$kelasId]);
        return $stmt->fetchAll();
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare('SELECT * FROM tugas WHERE id = :id LIMIT 1'); $stmt->execute([':id'=>$id]); $row=$stmt->fetch(); return $row ?: null;
    }

    public static function create(PDO $db, array $payload): int
    {
        $stmt = $db->prepare('INSERT INTO tugas (guru_id, kelas_id, mapel_id, judul_tugas, deskripsi, file_tugas, deadline, status, created_at, updated_at)
                              VALUES (:guru_id, :kelas_id, :mapel_id, :judul_tugas, :deskripsi, :file_tugas, :deadline, :status, NOW(), NOW())');
        $stmt->execute([
            ':guru_id'=>$payload['guru_id'], ':kelas_id'=>$payload['kelas_id'], ':mapel_id'=>$payload['mapel_id'], ':judul_tugas'=>$payload['judul_tugas'],
            ':deskripsi'=>$payload['deskripsi'] ?: null, ':file_tugas'=>$payload['file_tugas'] ?: null, ':deadline'=>$payload['deadline'], ':status'=>$payload['status'],
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(PDO $db, int $id, array $payload): void
    {
        $old = self::findById($db, $id);
        if (!$old) { throw new RuntimeException('Data tugas tidak ditemukan.'); }
        $file = $payload['file_tugas'] ?: $old['file_tugas'];
        $stmt = $db->prepare('UPDATE tugas SET kelas_id=:kelas_id, mapel_id=:mapel_id, judul_tugas=:judul_tugas, deskripsi=:deskripsi,
                              file_tugas=:file_tugas, deadline=:deadline, status=:status, updated_at=NOW() WHERE id=:id AND guru_id=:guru_id');
        $stmt->execute([
            ':id'=>$id, ':guru_id'=>$payload['guru_id'], ':kelas_id'=>$payload['kelas_id'], ':mapel_id'=>$payload['mapel_id'], ':judul_tugas'=>$payload['judul_tugas'],
            ':deskripsi'=>$payload['deskripsi'] ?: null, ':file_tugas'=>$file ?: null, ':deadline'=>$payload['deadline'], ':status'=>$payload['status'],
        ]);
    }

    public static function softDelete(PDO $db, int $id, int $guruId): void
    {
        $stmt = $db->prepare("UPDATE tugas SET status = 'inactive', updated_at = NOW() WHERE id = :id AND guru_id = :guru_id");
        $stmt->execute([':id'=>$id, ':guru_id'=>$guruId]);
    }

    public static function submissions(PDO $db, int $tugasId, ?int $guruId = null): array
    {
        $params = [':tugas_id'=>$tugasId];
        $sql = "SELECT pt.*, m.nama_murid, m.nis, t.judul_tugas
                FROM pengumpulan_tugas pt
                INNER JOIN murid m ON m.id = pt.murid_id
                INNER JOIN tugas t ON t.id = pt.tugas_id
                WHERE pt.tugas_id = :tugas_id";
        if ($guruId !== null) { $sql .= ' AND t.guru_id = :guru_id'; $params[':guru_id'] = $guruId; }
        $sql .= ' ORDER BY pt.submitted_at DESC, m.nama_murid ASC';
        $stmt=$db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }

    public static function submit(PDO $db, int $tugasId, int $muridId, ?string $fileJawaban, string $catatan): void
    {
        $stmt = $db->prepare("INSERT INTO pengumpulan_tugas (tugas_id, murid_id, file_jawaban, catatan_murid, status, submitted_at, updated_at)
                              VALUES (:tugas_id, :murid_id, :file_jawaban, :catatan_murid, 'Sudah dikumpulkan', NOW(), NOW())
                              ON DUPLICATE KEY UPDATE file_jawaban = COALESCE(VALUES(file_jawaban), file_jawaban), catatan_murid = VALUES(catatan_murid), status = 'Sudah dikumpulkan', submitted_at = NOW(), updated_at = NOW()");
        $stmt->execute([':tugas_id'=>$tugasId, ':murid_id'=>$muridId, ':file_jawaban'=>$fileJawaban, ':catatan_murid'=>$catatan ?: null]);
    }

    public static function gradeSubmission(PDO $db, int $submissionId, int $guruId, float $nilai, string $komentar): void
    {
        $stmt = $db->prepare("UPDATE pengumpulan_tugas pt
                              INNER JOIN tugas t ON t.id = pt.tugas_id
                              SET pt.nilai = :nilai, pt.komentar_guru = :komentar, pt.status = 'Sudah dinilai', pt.updated_at = NOW()
                              WHERE pt.id = :id AND t.guru_id = :guru_id");
        $stmt->execute([':id'=>$submissionId, ':guru_id'=>$guruId, ':nilai'=>$nilai, ':komentar'=>$komentar ?: null]);
    }
}
