<?php
require_once __DIR__ . '/album_functions.php';

$userId = getUserId();

$albumId = trim($_POST['album_id'] ?? '');

if ($albumId === '') {
    jsonResponse(false, 'Не указан альбом');
}

if (!checkAlbumOwner($connect, $albumId, $userId)) {
    jsonResponse(false, 'Альбом не найден');
}

try {
    $connect->beginTransaction();
    $stmt = $connect->prepare("
        DELETE pe
        FROM page_elements pe
        INNER JOIN album_pages ap ON pe.page_id = ap.id
        WHERE ap.album_id = ?
    ");

    $stmt->execute([$albumId]);
    $stmt = $connect->prepare("
        DELETE FROM album_pages
        WHERE album_id = ?
    ");

    $stmt->execute([$albumId]);
    $stmt = $connect->prepare("
        DELETE FROM albums
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$albumId, $userId]);

    $connect->commit();

    jsonResponse(true, 'Альбом удалён');

} catch (Exception $e) {

    if ($connect->inTransaction()) {
        $connect->rollBack();
    }

    jsonResponse(false, 'Ошибка при удалении альбома');
}