<?php
require_once __DIR__ . '/album_functions.php';

$userId = getUserId();

$albumId = trim($_POST['album_id'] ?? '');
$title = trim($_POST['title'] ?? '');

if ($albumId === '' || $title === '') {
    jsonResponse(false, 'Заполните все поля');
}

if (!checkAlbumOwner($connect, $albumId, $userId)) {
    jsonResponse(false, 'Альбом не найден');
}

$stmt = $connect->prepare("
    UPDATE albums
    SET title = ?, updated_at = NOW()
    WHERE id = ? AND user_id = ?
");

if (!$stmt->execute([$title, $albumId, $userId])) {
    jsonResponse(false, 'Ошибка при сохранении');
}

jsonResponse(true, 'Название изменено', [
    'new_title' => $title
]);