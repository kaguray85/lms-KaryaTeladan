<?php
require_once __DIR__ . '/auth.php';

function requireGuru(): array
{
    return requireRole('guru');
}
