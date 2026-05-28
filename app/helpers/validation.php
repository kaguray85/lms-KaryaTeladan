<?php
function requestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }

    return $_POST;
}

function getValue(array $data, string $key, mixed $default = null): mixed
{
    return isset($data[$key]) ? trim((string) $data[$key]) : $default;
}

function validateRequired(array $data, array $fields): array
{
    $errors = [];

    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
            $errors[$field] = "Field {$field} wajib diisi.";
        }
    }

    return $errors;
}

function validateEmailFormat(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateMinLength(string $value, int $min): bool
{
    return mb_strlen($value) >= $min;
}
