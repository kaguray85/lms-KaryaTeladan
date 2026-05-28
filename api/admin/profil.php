<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/admin.php';
$user = requireAdmin();
require_once __DIR__ . '/../../app/helpers/profile.php';

handleProfileEndpoint($user);
