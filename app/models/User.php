<?php
class User
{
    public static function findActiveByEmail(PDO $db, string $email): ?array
    {
        $statement = $db->prepare(
            "SELECT id, name, email, password, role, status, profile_photo, created_at, updated_at
             FROM users
             WHERE email = :email AND status = 'active'
             LIMIT 1"
        );
        $statement->execute([':email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function findActiveById(PDO $db, int $id): ?array
    {
        $statement = $db->prepare(
            "SELECT id, name, email, role, status, profile_photo, created_at, updated_at
             FROM users
             WHERE id = :id AND status = 'active'
             LIMIT 1"
        );
        $statement->execute([':id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $statement = $db->prepare(
            'SELECT id, name, email, role, status, profile_photo, created_at, updated_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function emailExists(PDO $db, string $email, ?int $excludeUserId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE email = :email';
        $params = [':email' => $email];

        if ($excludeUserId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeUserId;
        }

        $sql .= ' LIMIT 1';
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetch();
    }

    public static function create(PDO $db, array $payload): int
    {
        $statement = $db->prepare(
            'INSERT INTO users (name, email, password, role, status, created_at, updated_at)
             VALUES (:name, :email, :password, :role, :status, NOW(), NOW())'
        );

        $statement->execute([
            ':name' => $payload['name'],
            ':email' => $payload['email'],
            ':password' => password_hash($payload['password'], PASSWORD_DEFAULT),
            ':role' => $payload['role'],
            ':status' => $payload['status'] ?? 'active',
        ]);

        return (int) $db->lastInsertId();
    }

    public static function updateAccount(PDO $db, int $id, array $payload): void
    {
        $fields = [
            'name = :name',
            'email = :email',
            'status = :status',
            'updated_at = NOW()',
        ];

        $params = [
            ':id' => $id,
            ':name' => $payload['name'],
            ':email' => $payload['email'],
            ':status' => $payload['status'] ?? 'active',
        ];

        if (!empty($payload['password'])) {
            $fields[] = 'password = :password';
            $params[':password'] = password_hash($payload['password'], PASSWORD_DEFAULT);
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $statement = $db->prepare($sql);
        $statement->execute($params);
    }

    public static function updateStatus(PDO $db, int $id, string $status): void
    {
        $statement = $db->prepare('UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id');
        $statement->execute([
            ':id' => $id,
            ':status' => $status,
        ]);
    }

    public static function updatePassword(PDO $db, int $id, string $password): void
    {
        $statement = $db->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id');
        $statement->execute([
            ':id' => $id,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    public static function safeUser(array $user): array
    {
        unset($user['password']);
        return $user;
    }
}
