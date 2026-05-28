<?php
function cleanString(?string $value): string
{
    return htmlspecialchars(trim((string) $value), ENT_QUOTES, 'UTF-8');
}

function logActivity(PDO $db, ?int $userId, string $activity, string $role = 'guest'): void
{
    try {
        $statement = $db->prepare(
            'INSERT INTO aktivitas_log (user_id, aktivitas, role, created_at)
             VALUES (:user_id, :aktivitas, :role, NOW())'
        );

        $statement->execute([
            ':user_id' => $userId,
            ':aktivitas' => $activity,
            ':role' => $role,
        ]);
    } catch (Throwable $exception) {
        // Log aktivitas tidak boleh membuat request utama gagal.
    }
}

function destroySession(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}
