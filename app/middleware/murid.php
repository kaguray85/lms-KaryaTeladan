<?php
require_once __DIR__ . '/auth.php';

function requireMurid(): array
{
    return requireRole('murid');
}
