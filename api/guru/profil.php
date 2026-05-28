<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/guru.php';
$user = requireGuru();
require_once __DIR__ . '/../../app/helpers/profile.php';

handleProfileEndpoint($user);
