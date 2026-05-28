<?php
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/upload.php';
require_once __DIR__ . '/../../app/middleware/guru.php';
requireGuru();
try{ if(!isset($_FILES['file'])) errorResponse('File wajib dikirim.',422); $path=saveUploadedFile($_FILES['file'],'materi',['pdf','doc','docx','ppt','pptx']); successResponse('File materi berhasil diupload.',['path'=>$path,'url'=>publicFileUrl($path)]); }catch(Throwable $e){ errorResponse($e->getMessage(),422); }
