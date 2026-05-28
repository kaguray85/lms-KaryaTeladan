<?php
class Mapel
{
    public static function all(PDO $db, array $filters = []): array
    {
        $where = [];
        $params = [];

        $search = $filters['search'] ?? '';
        if ($search !== '') {
            $where[] = '(m.kode_mapel LIKE :search OR m.nama_mapel LIKE :search OR g.nama_guru LIKE :search OR k.nama_kelas LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $status = $filters['status'] ?? '';
        if ($status !== '') {
            $where[] = 'm.status = :status';
            $params[':status'] = $status;
        }

        $kelasId = $filters['kelas_id'] ?? '';
        if ($kelasId !== '') {
            $where[] = 'm.kelas_id = :kelas_id';
            $params[':kelas_id'] = (int) $kelasId;
        }

        $sql = "SELECT
                    m.id,
                    m.kode_mapel,
                    m.nama_mapel,
                    m.guru_id,
                    COALESCE(g.nama_guru, '-') AS nama_guru,
                    m.kelas_id,
                    COALESCE(k.nama_kelas, '-') AS nama_kelas,
                    COALESCE(k.jurusan, '-') AS jurusan,
                    m.semester,
                    m.status,
                    m.created_at,
                    m.updated_at
                FROM mata_pelajaran m
                LEFT JOIN guru g ON g.id = m.guru_id
                LEFT JOIN kelas k ON k.id = m.kelas_id";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY m.id DESC';

        $statement = $db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function activeOptions(PDO $db, ?int $kelasId = null): array
    {
        $sql = "SELECT
                    m.id,
                    m.kode_mapel,
                    m.nama_mapel,
                    m.guru_id,
                    g.nama_guru,
                    m.kelas_id,
                    k.nama_kelas,
                    m.semester
                FROM mata_pelajaran m
                LEFT JOIN guru g ON g.id = m.guru_id
                LEFT JOIN kelas k ON k.id = m.kelas_id
                WHERE m.status = 'active'";
        $params = [];

        if ($kelasId !== null && $kelasId > 0) {
            $sql .= ' AND m.kelas_id = :kelas_id';
            $params[':kelas_id'] = $kelasId;
        }

        $sql .= ' ORDER BY m.nama_mapel ASC';
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $statement = $db->prepare('SELECT * FROM mata_pelajaran WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $mapel = $statement->fetch();

        return $mapel ?: null;
    }

    public static function kodeExists(PDO $db, string $kodeMapel, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM mata_pelajaran WHERE kode_mapel = :kode_mapel';
        $params = [':kode_mapel' => $kodeMapel];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetch();
    }

    public static function create(PDO $db, array $payload): int
    {
        $statement = $db->prepare(
            'INSERT INTO mata_pelajaran (kode_mapel, nama_mapel, guru_id, kelas_id, semester, status, created_at, updated_at)
             VALUES (:kode_mapel, :nama_mapel, :guru_id, :kelas_id, :semester, :status, NOW(), NOW())'
        );

        $statement->execute([
            ':kode_mapel' => $payload['kode_mapel'],
            ':nama_mapel' => $payload['nama_mapel'],
            ':guru_id' => $payload['guru_id'],
            ':kelas_id' => $payload['kelas_id'],
            ':semester' => $payload['semester'],
            ':status' => $payload['status'],
        ]);

        return (int) $db->lastInsertId();
    }

    public static function update(PDO $db, int $id, array $payload): void
    {
        $statement = $db->prepare(
            'UPDATE mata_pelajaran
             SET kode_mapel = :kode_mapel,
                 nama_mapel = :nama_mapel,
                 guru_id = :guru_id,
                 kelas_id = :kelas_id,
                 semester = :semester,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            ':id' => $id,
            ':kode_mapel' => $payload['kode_mapel'],
            ':nama_mapel' => $payload['nama_mapel'],
            ':guru_id' => $payload['guru_id'],
            ':kelas_id' => $payload['kelas_id'],
            ':semester' => $payload['semester'],
            ':status' => $payload['status'],
        ]);
    }

    public static function softDelete(PDO $db, int $id): void
    {
        $statement = $db->prepare("UPDATE mata_pelajaran SET status = 'inactive', updated_at = NOW() WHERE id = :id");
        $statement->execute([':id' => $id]);
    }
}
