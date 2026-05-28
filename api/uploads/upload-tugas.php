<?php
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/upload.php';
require_once __DIR__ . '/../../app/middleware/auth.php';
requireRole(['guru','murid']);
try{ if(!isset($_FILES['file'])) errorResponse('File wajib dikirim.',422); $path=saveUploadedFile($_FILES['file'],'tugas',['pdf','doc','docx','zip','rar']); successResponse('File tugas berhasil diupload.',['path'=>$path,'url'=>publicFileUrl($path)]); }catch(Throwable $e){ errorResponse($e->getMessage(),422); }
