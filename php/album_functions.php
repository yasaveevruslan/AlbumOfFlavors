<?php
session_start();
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($success, $message = '', $data = [])
{
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data), JSON_UNESCAPED_UNICODE);

    exit;
}

function getUserId()
{
    if (empty($_SESSION['user_id'])) {
        jsonResponse(false, 'Необходима авторизация');
    }

    return $_SESSION['user_id'];
}

function generateUUID()
{
    $data = random_bytes(16);

    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function checkAlbumOwner($connect, $albumId, $userId)
{
    $stmt = $connect->prepare(
        "SELECT id FROM albums WHERE id = ? AND user_id = ?"
    );

    $stmt->execute([$albumId, $userId]);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function uploadAlbumCover($file)
{
    if (
        !isset($file) ||
        $file['error'] !== UPLOAD_ERR_OK
    ) {
        return null;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        jsonResponse(false, 'Размер изображения не должен превышать 2 МБ');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        jsonResponse(false, 'Разрешены только JPG, PNG и WEBP');
    }

    $uploadDir = __DIR__ . '/../asset/img/albums/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'album_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        jsonResponse(false, 'Не удалось загрузить изображение');
    }

    return 'asset/img/albums/' . $filename;
}