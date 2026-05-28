<?php
function requestData(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function inputString(array $data, string $key, string $default = ''): string
{
    return trim((string)($data[$key] ?? $default));
}

function inputInt(array $data, string $key, int $default = 0): int
{
    return (int)($data[$key] ?? $default);
}
