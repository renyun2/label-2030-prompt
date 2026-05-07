<?php
session_start();
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/core/Logger.php';

// 简单的登录检查
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        Logger::warning('未授权访问，跳转登录', ['uri' => $_SERVER['REQUEST_URI'] ?? '']);
        header('Location: /admin/login.php');
        exit;
    }
}

function formatDateTime($datetime) {
    return date('Y-m-d H:i', strtotime($datetime));
}

function isEventExpired($endTime) {
    return strtotime($endTime) < time();
}

define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB

function uploadFile($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errCode = $file['error'] ?? -1;
        Logger::error('文件上传失败', ['error_code' => $errCode, 'original_name' => $file['name'] ?? '']);
        return ['success' => false, 'message' => '文件上传失败'];
    }

    if ($file['size'] > UPLOAD_MAX_SIZE) {
        Logger::warning('上传文件超出大小限制', ['size' => $file['size'], 'limit' => UPLOAD_MAX_SIZE, 'name' => $file['name']]);
        return ['success' => false, 'message' => '文件大小不能超过 5MB'];
    }

    if (!in_array($file['type'], $allowedTypes)) {
        Logger::warning('上传文件类型不允许', ['type' => $file['type'], 'name' => $file['name']]);
        return ['success' => false, 'message' => '不支持的文件类型，仅允许 JPG/PNG/GIF/WebP'];
    }

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        Logger::info('文件上传成功', ['filename' => $filename, 'size' => $file['size']]);
        return ['success' => true, 'filename' => $filename, 'path' => '/uploads/' . $filename];
    }

    Logger::error('文件保存失败', ['filepath' => $filepath]);
    return ['success' => false, 'message' => '文件保存失败'];
}
