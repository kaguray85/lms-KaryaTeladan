<?php
class PasswordResetOtp
{
    public static function latestForUser(PDO $db, int $userId): ?array
    {
        $statement = $db->prepare(
            'SELECT id, user_id, otp_hash, expired_at, is_used, attempts, created_at
             FROM password_reset_otps
             WHERE user_id = :user_id
             ORDER BY id DESC
             LIMIT 1'
        );
        $statement->execute([':user_id' => $userId]);
        $otp = $statement->fetch();

        return $otp ?: null;
    }

    public static function create(PDO $db, int $userId, string $otpHash, string $expiredAt): int
    {
        self::invalidateUnusedForUser($db, $userId);

        $statement = $db->prepare(
            'INSERT INTO password_reset_otps (user_id, otp_hash, expired_at, is_used, attempts, created_at)
             VALUES (:user_id, :otp_hash, :expired_at, 0, 0, NOW())'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':otp_hash' => $otpHash,
            ':expired_at' => $expiredAt,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function incrementAttempts(PDO $db, int $id): void
    {
        $statement = $db->prepare(
            'UPDATE password_reset_otps SET attempts = attempts + 1 WHERE id = :id AND is_used = 0'
        );
        $statement->execute([':id' => $id]);
    }

    public static function markUsed(PDO $db, int $id): bool
    {
        $statement = $db->prepare(
            'UPDATE password_reset_otps SET is_used = 1 WHERE id = :id AND is_used = 0'
        );
        $statement->execute([':id' => $id]);

        return $statement->rowCount() === 1;
    }

    public static function invalidateUnusedForUser(PDO $db, int $userId): void
    {
        $statement = $db->prepare(
            'UPDATE password_reset_otps SET is_used = 1 WHERE user_id = :user_id AND is_used = 0'
        );
        $statement->execute([':user_id' => $userId]);
    }
}
