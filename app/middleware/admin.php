<?php
require_once __DIR__ . '/auth.php';

function requireAdmin(): array
{
    return requireRole('admin');
}
