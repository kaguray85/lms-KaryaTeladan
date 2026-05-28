<?php
class Nilai
{
    public static function calculateFinal(float $tugas, float $uts, float $uas): float
    {
        return round(($tugas * 0.4) + ($uts * 0.3) + ($uas * 0.3), 2);
    }

    public static function grade(float $nilaiAkhir): string
    {
        if ($nilaiAkhir >= 85) return 'A';
        if ($nilaiAkhir >= 75) return 'B';
        if ($nilaiAkhir >= 65) return 'C';
        return 'D';
    }

    public static function all(PDO $db, array $filters = []): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['guru_id'])) { $where[] = 'n.guru_id = :guru_id'; $params[':guru_id'] = (int)$filters['guru_id']; }
        if (!empty($filters['murid_id'])) { $where[] = 'n.murid_id = :murid_id'; $params[':murid_id'] = (int)$filters['murid_id']; }
        if (!empty($filters['kelas_id'])) { $where[] = 'n.kelas_id = :kelas_id'; $params[':kelas_id'] = (int)$filters['kelas_id']; }
        if (!empty($filters['mapel_id'])) { $where[] = 'n.mapel_id = :mapel_id'; $params[':mapel_id'] = (int)$filters['mapel_id']; }

        $sql = "SELECT n.*, m.nama_murid, m.nis, g.nama_guru, k.nama_kelas, k.jurusan, mp.nama_mapel, t.judul_tugas
                FROM nilai n
                INNER JOIN murid m ON m.id = n.murid_id
                INNER JOIN guru g ON g.id = n.guru_id
                INNER JOIN kelas k ON k.id = n.kelas_id
                INNER JOIN mata_pelajaran mp ON mp.id = n.mapel_id
                LEFT JOIN tugas t ON t.id = n.tugas_id";
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY n.updated_at DESC, m.nama_murid ASC';
        $stmt = $db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare('SELECT * FROM nilai WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]); $row = $stmt->fetch(); return $row ?: null;
    }

    public static function save(PDO $db, array $payload, ?int $id = null): int
    {
        $nilaiAkhir = self::calculateFinal((float)$payload['nilai_tugas'], (float)$payload['nilai_uts'], (float)$payload['nilai_uas']);
        $grade = self::grade($nilaiAkhir);
        $params = [
            ':murid_id' => $payload['murid_id'], ':guru_id' => $payload['guru_id'], ':kelas_id' => $payload['kelas_id'], ':mapel_id' => $payload['mapel_id'],
            ':tugas_id' => $payload['tugas_id'] ?: null, ':nilai_tugas' => $payload['nilai_tugas'], ':nilai_uts' => $payload['nilai_uts'], ':nilai_uas' => $payload['nilai_uas'],
            ':nilai_akhir' => $nilaiAkhir, ':grade' => $grade, ':komentar' => $payload['komentar'] ?: null,
        ];

        if ($id === null) {
            $stmt = $db->prepare('INSERT INTO nilai (murid_id, guru_id, kelas_id, mapel_id, tugas_id, nilai_tugas, nilai_uts, nilai_uas, nilai_akhir, grade, komentar, created_at, updated_at)
                                  VALUES (:murid_id, :guru_id, :kelas_id, :mapel_id, :tugas_id, :nilai_tugas, :nilai_uts, :nilai_uas, :nilai_akhir, :grade, :komentar, NOW(), NOW())');
            $stmt->execute($params);
            return (int)$db->lastInsertId();
        }

        $params[':id'] = $id;
        $stmt = $db->prepare('UPDATE nilai SET murid_id=:murid_id, guru_id=:guru_id, kelas_id=:kelas_id, mapel_id=:mapel_id, tugas_id=:tugas_id,
                              nilai_tugas=:nilai_tugas, nilai_uts=:nilai_uts, nilai_uas=:nilai_uas, nilai_akhir=:nilai_akhir, grade=:grade, komentar=:komentar, updated_at=NOW()
                              WHERE id=:id');
        $stmt->execute($params);
        return $id;
    }

    public static function guruCanAccessStudent(PDO $db, int $guruId, int $kelasId, int $mapelId, int $muridId): bool
    {
        $stmt = $db->prepare("SELECT m.id
                              FROM murid m
                              INNER JOIN mata_pelajaran mp ON mp.kelas_id = m.kelas_id
                              WHERE m.id = :murid_id AND m.kelas_id = :kelas_id AND mp.id = :mapel_id AND mp.guru_id = :guru_id AND m.status = 'active'
                              LIMIT 1");
        $stmt->execute([':murid_id'=>$muridId, ':kelas_id'=>$kelasId, ':mapel_id'=>$mapelId, ':guru_id'=>$guruId]);
        return (bool)$stmt->fetch();
    }
}
