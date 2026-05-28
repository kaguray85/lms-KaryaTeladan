<?php
function jsonResponse(bool $status, string $message, mixed $data = [], int $httpCode = 200, array $errors = []): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'errors' => $errors,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

function successResponse(string $message = 'Data berhasil diproses', mixed $data = [], int $httpCode = 200): void
{
    jsonResponse(true, $message, $data, $httpCode);
}

function errorResponse(string $message = 'Terjadi kesalahan', int $httpCode = 400, array $errors = []): void
{
    jsonResponse(false, $message, [], $httpCode, $errors);
}
