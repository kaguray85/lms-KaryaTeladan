<?php
class Presensi
{
    public static function all(PDO $db, array $filters = []): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['guru_id'])) { $where[] = 'p.guru_id = :guru_id'; $params[':guru_id'] = (int)$filters['guru_id']; }
        if (!empty($filters['murid_id'])) { $where[] = 'p.murid_id = :murid_id'; $params[':murid_id'] = (int)$filters['murid_id']; }
        if (!empty($filters['kelas_id'])) { $where[] = 'j.kelas_id = :kelas_id'; $params[':kelas_id'] = (int)$filters['kelas_id']; }
        if (!empty($filters['tanggal'])) { $where[] = 'p.tanggal = :tanggal'; $params[':tanggal'] = $filters['tanggal']; }
        if (!empty($filters['status'])) { $where[] = 'p.status = :status'; $params[':status'] = $filters['status']; }

        $sql = "SELECT p.id, p.jadwal_id, p.murid_id, p.guru_id, p.tanggal, p.status, p.keterangan,
                       m.nama_murid, m.nis, k.nama_kelas, k.jurusan, mp.nama_mapel, g.nama_guru,
                       j.hari, TIME_FORMAT(j.jam_mulai, '%H:%i') AS jam_mulai, TIME_FORMAT(j.jam_selesai, '%H:%i') AS jam_selesai
                FROM presensi p
                INNER JOIN jadwal_pelajaran j ON j.id = p.jadwal_id
                INNER JOIN murid m ON m.id = p.murid_id
                INNER JOIN guru g ON g.id = p.guru_id
                INNER JOIN kelas k ON k.id = j.kelas_id
                INNER JOIN mata_pelajaran mp ON mp.id = j.mapel_id";
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY p.tanggal DESC, k.nama_kelas ASC, m.nama_murid ASC';
        $stmt = $db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }

    public static function studentsBySchedule(PDO $db, int $jadwalId, ?int $guruId = null): array
    {
        $params = [':jadwal_id' => $jadwalId];
        $sql = "SELECT m.id, m.nama_murid, m.nis, m.kelas_id
                FROM jadwal_pelajaran j
                INNER JOIN murid m ON m.kelas_id = j.kelas_id AND m.status = 'active'
                WHERE j.id = :jadwal_id AND j.status = 'active'";
        if ($guruId !== null) { $sql .= ' AND j.guru_id = :guru_id'; $params[':guru_id'] = $guruId; }
        $sql .= ' ORDER BY m.nama_murid ASC';
        $stmt = $db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }

    public static function upsert(PDO $db, array $payload): void
    {
        $stmt = $db->prepare("INSERT INTO presensi (jadwal_id, murid_id, guru_id, tanggal, status, keterangan, created_at, updated_at)
                              VALUES (:jadwal_id, :murid_id, :guru_id, :tanggal, :status, :keterangan, NOW(), NOW())
                              ON DUPLICATE KEY UPDATE status = VALUES(status), keterangan = VALUES(keterangan), updated_at = NOW()");
        $stmt->execute([
            ':jadwal_id' => $payload['jadwal_id'], ':murid_id' => $payload['murid_id'], ':guru_id' => $payload['guru_id'],
            ':tanggal' => $payload['tanggal'], ':status' => $payload['status'], ':keterangan' => $payload['keterangan'] ?: null,
        ]);
    }

    public static function scheduleBelongsToGuru(PDO $db, int $jadwalId, int $guruId): bool
    {
        $stmt = $db->prepare("SELECT id FROM jadwal_pelajaran WHERE id = :id AND guru_id = :guru_id AND status = 'active' LIMIT 1");
        $stmt->execute([':id' => $jadwalId, ':guru_id' => $guruId]);
        return (bool)$stmt->fetch();
    }
}
