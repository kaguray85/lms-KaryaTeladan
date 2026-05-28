<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/middleware/guru.php';
require_once __DIR__ . '/../../app/models/Jadwal.php';

$user = requireGuru(); $db = Database::connection();
$stmt = $db->prepare('SELECT id FROM guru WHERE user_id = :user_id LIMIT 1'); $stmt->execute([':user_id'=>$user['id']]); $guru=$stmt->fetch();
if (!$guru) errorResponse('Profil guru tidak ditemukan.', 404);
try { $filters = $_GET; $filters['guru_id'] = (int)$guru['id']; successResponse('Jadwal guru berhasil dimuat.', Jadwal::all($db, $filters)); } catch(Throwable $e){ errorResponse('Jadwal guru gagal dimuat.',500); }
