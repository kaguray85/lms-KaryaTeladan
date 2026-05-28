<?php
class Kelas
{
    public static function all(PDO $db, array $filters = []): array
    {
        $where = [];
        $params = [];

        $search = $filters['search'] ?? '';
        if ($search !== '') {
            $where[] = '(k.nama_kelas LIKE :search OR k.jurusan LIKE :search OR k.tahun_ajaran LIKE :search OR g.nama_guru LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $status = $filters['status'] ?? '';
        if ($status !== '') {
            $where[] = 'k.status = :status';
            $params[':status'] = $status;
        }

        $sql = "SELECT
                    k.id,
                    k.nama_kelas,
                    k.jurusan,
                    k.wali_kelas_id,
                    COALESCE(g.nama_guru, '-') AS wali_kelas,
                    k.jumlah_murid,
                    k.tahun_ajaran,
                    k.status,
                    k.created_at,
                    k.updated_at
                FROM kelas k
                LEFT JOIN guru g ON g.id = k.wali_kelas_id";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY k.id DESC';

        $statement = $db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function activeOptions(PDO $db): array
    {
        $statement = $db->query("SELECT id, nama_kelas, jurusan, tahun_ajaran FROM kelas WHERE status = 'active' ORDER BY nama_kelas ASC");
        return $statement->fetchAll();
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $statement = $db->prepare('SELECT * FROM kelas WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $kelas = $statement->fetch();

        return $kelas ?: null;
    }

    public static function isDuplicate(PDO $db, string $namaKelas, string $jurusan, string $tahunAjaran, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM kelas WHERE nama_kelas = :nama_kelas AND jurusan = :jurusan AND tahun_ajaran = :tahun_ajaran';
        $params = [
            ':nama_kelas' => $namaKelas,
            ':jurusan' => $jurusan,
            ':tahun_ajaran' => $tahunAjaran,
        ];

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
            'INSERT INTO kelas (nama_kelas, jurusan, wali_kelas_id, jumlah_murid, tahun_ajaran, status, created_at, updated_at)
             VALUES (:nama_kelas, :jurusan, :wali_kelas_id, 0, :tahun_ajaran, :status, NOW(), NOW())'
        );

        $statement->execute([
            ':nama_kelas' => $payload['nama_kelas'],
            ':jurusan' => $payload['jurusan'],
            ':wali_kelas_id' => $payload['wali_kelas_id'] ?: null,
            ':tahun_ajaran' => $payload['tahun_ajaran'],
            ':status' => $payload['status'],
        ]);

        return (int) $db->lastInsertId();
    }

    public static function update(PDO $db, int $id, array $payload): void
    {
        $statement = $db->prepare(
            'UPDATE kelas
             SET nama_kelas = :nama_kelas,
                 jurusan = :jurusan,
                 wali_kelas_id = :wali_kelas_id,
                 tahun_ajaran = :tahun_ajaran,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            ':id' => $id,
            ':nama_kelas' => $payload['nama_kelas'],
            ':jurusan' => $payload['jurusan'],
            ':wali_kelas_id' => $payload['wali_kelas_id'] ?: null,
            ':tahun_ajaran' => $payload['tahun_ajaran'],
            ':status' => $payload['status'],
        ]);
    }

    public static function softDelete(PDO $db, int $id): void
    {
        $statement = $db->prepare("UPDATE kelas SET status = 'inactive', updated_at = NOW() WHERE id = :id");
        $statement->execute([':id' => $id]);
    }
}
