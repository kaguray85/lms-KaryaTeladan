<?php
class Murid
{
    public static function all(PDO $db, array $filters = []): array
    {
        $where = [];
        $params = [];

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

        $search = $filters['search'] ?? '';
        if ($search !== '') {
            $where[] = '(m.nama_murid LIKE :search OR m.nis LIKE :search OR m.email LIKE :search OR k.nama_kelas LIKE :search OR m.jurusan LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $sql = "SELECT
                    m.id,
                    m.user_id,
                    m.kelas_id,
                    m.nama_murid,
                    m.nis,
                    m.email,
                    m.no_hp,
                    m.jurusan,
                    m.status,
                    u.status AS user_status,
                    k.nama_kelas,
                    k.tahun_ajaran,
                    m.created_at,
                    m.updated_at
                FROM murid m
                INNER JOIN users u ON u.id = m.user_id
                LEFT JOIN kelas k ON k.id = m.kelas_id";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY m.id DESC';

        $statement = $db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $statement = $db->prepare(
            'SELECT m.*, u.name AS user_name, u.email AS user_email, u.status AS user_status
             FROM murid m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $murid = $statement->fetch();

        return $murid ?: null;
    }

    public static function nisExists(PDO $db, string $nis, ?int $excludeMuridId = null): bool
    {
        $sql = 'SELECT id FROM murid WHERE nis = :nis';
        $params = [':nis' => $nis];

        if ($excludeMuridId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeMuridId;
        }

        $sql .= ' LIMIT 1';
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetch();
    }

    public static function emailExists(PDO $db, string $email, ?int $excludeMuridId = null): bool
    {
        $sql = 'SELECT id FROM murid WHERE email = :email';
        $params = [':email' => $email];

        if ($excludeMuridId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeMuridId;
        }

        $sql .= ' LIMIT 1';
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetch();
    }

    public static function kelasExists(PDO $db, int $kelasId): bool
    {
        $statement = $db->prepare("SELECT id FROM kelas WHERE id = :id AND status = 'active' LIMIT 1");
        $statement->execute([':id' => $kelasId]);

        return (bool) $statement->fetch();
    }

    public static function kelasOptions(PDO $db): array
    {
        $statement = $db->query(
            "SELECT id, nama_kelas, jurusan, tahun_ajaran
             FROM kelas
             WHERE status = 'active'
             ORDER BY nama_kelas ASC"
        );

        return $statement->fetchAll();
    }

    public static function updateJumlahMurid(PDO $db, ?int $kelasId): void
    {
        if ($kelasId === null || $kelasId <= 0) {
            return;
        }

        $statement = $db->prepare(
            "UPDATE kelas
             SET jumlah_murid = (
                SELECT COUNT(*) FROM murid WHERE kelas_id = :kelas_id_count AND status = 'active'
             ), updated_at = NOW()
             WHERE id = :kelas_id_update"
        );

        $statement->execute([
            ':kelas_id_count' => $kelasId,
            ':kelas_id_update' => $kelasId,
        ]);
    }

    public static function create(PDO $db, array $payload): int
    {
        $userId = User::create($db, [
            'name' => $payload['nama_murid'],
            'email' => $payload['email'],
            'password' => $payload['password'],
            'role' => 'murid',
            'status' => $payload['status'],
        ]);

        $statement = $db->prepare(
            'INSERT INTO murid (user_id, kelas_id, nama_murid, nis, email, no_hp, jurusan, status, created_at, updated_at)
             VALUES (:user_id, :kelas_id, :nama_murid, :nis, :email, :no_hp, :jurusan, :status, NOW(), NOW())'
        );

        $statement->execute([
            ':user_id' => $userId,
            ':kelas_id' => $payload['kelas_id'],
            ':nama_murid' => $payload['nama_murid'],
            ':nis' => $payload['nis'],
            ':email' => $payload['email'],
            ':no_hp' => $payload['no_hp'] ?: null,
            ':jurusan' => $payload['jurusan'] ?: null,
            ':status' => $payload['status'],
        ]);

        $muridId = (int) $db->lastInsertId();
        self::updateJumlahMurid($db, (int) $payload['kelas_id']);

        return $muridId;
    }

    public static function update(PDO $db, int $id, array $payload): void
    {
        $murid = self::findById($db, $id);
        if (!$murid) {
            throw new RuntimeException('Data murid tidak ditemukan.');
        }

        $oldKelasId = $murid['kelas_id'] ? (int) $murid['kelas_id'] : null;
        $newKelasId = (int) $payload['kelas_id'];

        User::updateAccount($db, (int) $murid['user_id'], [
            'name' => $payload['nama_murid'],
            'email' => $payload['email'],
            'password' => $payload['password'] ?? '',
            'status' => $payload['status'],
        ]);

        $statement = $db->prepare(
            'UPDATE murid
             SET kelas_id = :kelas_id,
                 nama_murid = :nama_murid,
                 nis = :nis,
                 email = :email,
                 no_hp = :no_hp,
                 jurusan = :jurusan,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            ':id' => $id,
            ':kelas_id' => $newKelasId,
            ':nama_murid' => $payload['nama_murid'],
            ':nis' => $payload['nis'],
            ':email' => $payload['email'],
            ':no_hp' => $payload['no_hp'] ?: null,
            ':jurusan' => $payload['jurusan'] ?: null,
            ':status' => $payload['status'],
        ]);

        self::updateJumlahMurid($db, $oldKelasId);
        self::updateJumlahMurid($db, $newKelasId);
    }

    public static function softDelete(PDO $db, int $id): void
    {
        $murid = self::findById($db, $id);
        if (!$murid) {
            throw new RuntimeException('Data murid tidak ditemukan.');
        }

        $statement = $db->prepare('UPDATE murid SET status = :status, updated_at = NOW() WHERE id = :id');
        $statement->execute([
            ':id' => $id,
            ':status' => 'inactive',
        ]);

        User::updateStatus($db, (int) $murid['user_id'], 'inactive');
        self::updateJumlahMurid($db, $murid['kelas_id'] ? (int) $murid['kelas_id'] : null);
    }
}
