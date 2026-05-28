<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/murid.php';
$user = requireMurid();
require_once __DIR__ . '/../../app/helpers/profile.php';

handleProfileEndpoint($user);
