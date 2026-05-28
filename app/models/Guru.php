<?php
class Guru
{
    public static function all(PDO $db, array $filters = []): array
    {
        $where = [];
        $params = [];

        $status = $filters['status'] ?? '';
        if ($status !== '') {
            $where[] = 'g.status = :status';
            $params[':status'] = $status;
        }

        $search = $filters['search'] ?? '';
        if ($search !== '') {
            $where[] = '(g.nama_guru LIKE :search OR g.nip LIKE :search OR g.email LIKE :search OR g.mata_pelajaran_utama LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $sql = "SELECT
                    g.id,
                    g.user_id,
                    g.nama_guru,
                    g.nip,
                    g.email,
                    g.no_hp,
                    g.mata_pelajaran_utama,
                    g.status,
                    u.status AS user_status,
                    g.created_at,
                    g.updated_at
                FROM guru g
                INNER JOIN users u ON u.id = g.user_id";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY g.id DESC';

        $statement = $db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $statement = $db->prepare(
            'SELECT g.*, u.name AS user_name, u.email AS user_email, u.status AS user_status
             FROM guru g
             INNER JOIN users u ON u.id = g.user_id
             WHERE g.id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $guru = $statement->fetch();

        return $guru ?: null;
    }

    public static function nipExists(PDO $db, string $nip, ?int $excludeGuruId = null): bool
    {
        $sql = 'SELECT id FROM guru WHERE nip = :nip';
        $params = [':nip' => $nip];

        if ($excludeGuruId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeGuruId;
        }

        $sql .= ' LIMIT 1';
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetch();
    }

    public static function emailExists(PDO $db, string $email, ?int $excludeGuruId = null): bool
    {
        $sql = 'SELECT id FROM guru WHERE email = :email';
        $params = [':email' => $email];

        if ($excludeGuruId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeGuruId;
        }

        $sql .= ' LIMIT 1';
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetch();
    }

    public static function create(PDO $db, array $payload): int
    {
        $userId = User::create($db, [
            'name' => $payload['nama_guru'],
            'email' => $payload['email'],
            'password' => $payload['password'],
            'role' => 'guru',
            'status' => $payload['status'],
        ]);

        $statement = $db->prepare(
            'INSERT INTO guru (user_id, nama_guru, nip, email, no_hp, mata_pelajaran_utama, status, created_at, updated_at)
             VALUES (:user_id, :nama_guru, :nip, :email, :no_hp, :mata_pelajaran_utama, :status, NOW(), NOW())'
        );

        $statement->execute([
            ':user_id' => $userId,
            ':nama_guru' => $payload['nama_guru'],
            ':nip' => $payload['nip'],
            ':email' => $payload['email'],
            ':no_hp' => $payload['no_hp'] ?: null,
            ':mata_pelajaran_utama' => $payload['mata_pelajaran_utama'] ?: null,
            ':status' => $payload['status'],
        ]);

        return (int) $db->lastInsertId();
    }

    public static function update(PDO $db, int $id, array $payload): void
    {
        $guru = self::findById($db, $id);
        if (!$guru) {
            throw new RuntimeException('Data guru tidak ditemukan.');
        }

        User::updateAccount($db, (int) $guru['user_id'], [
            'name' => $payload['nama_guru'],
            'email' => $payload['email'],
            'password' => $payload['password'] ?? '',
            'status' => $payload['status'],
        ]);

        $statement = $db->prepare(
            'UPDATE guru
             SET nama_guru = :nama_guru,
                 nip = :nip,
                 email = :email,
                 no_hp = :no_hp,
                 mata_pelajaran_utama = :mata_pelajaran_utama,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            ':id' => $id,
            ':nama_guru' => $payload['nama_guru'],
            ':nip' => $payload['nip'],
            ':email' => $payload['email'],
            ':no_hp' => $payload['no_hp'] ?: null,
            ':mata_pelajaran_utama' => $payload['mata_pelajaran_utama'] ?: null,
            ':status' => $payload['status'],
        ]);
    }

    public static function softDelete(PDO $db, int $id): void
    {
        $guru = self::findById($db, $id);
        if (!$guru) {
            throw new RuntimeException('Data guru tidak ditemukan.');
        }

        $statement = $db->prepare('UPDATE guru SET status = :status, updated_at = NOW() WHERE id = :id');
        $statement->execute([
            ':id' => $id,
            ':status' => 'inactive',
        ]);

        User::updateStatus($db, (int) $guru['user_id'], 'inactive');
    }
}
