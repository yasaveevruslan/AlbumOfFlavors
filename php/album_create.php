<?php
require_once __DIR__ . '/album_functions.php';

$userId = getUserId();

$title = trim($_POST['title'] ?? '');
$templateId = trim($_POST['template_id'] ?? '');

if ($title === '') {
    jsonResponse(false, 'Введите название альбома');
}

$coverUrl = null;
$templateIdValue = null;

// Вариант 1. Пользователь выбрал готовый шаблон
if ($templateId !== '') {

    $stmt = $connect->prepare("
        SELECT id, cover_url
        FROM album_templates
        WHERE id = ? AND is_active = 1
    ");

    $stmt->execute([$templateId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        jsonResponse(false, 'Выбранный шаблон не найден');
    }

    $templateIdValue = $template['id'];
    $coverUrl = $template['cover_url'];
}

// Вариант 2. Пользователь загрузил собственную обложку
if (
    isset($_FILES['cover_file']) &&
    $_FILES['cover_file']['error'] !== UPLOAD_ERR_NO_FILE
) {
    $coverUrl = uploadAlbumCover($_FILES['cover_file']);
    $templateIdValue = null;
}

if (!$coverUrl) {
    jsonResponse(false, 'Выберите обложку альбома');
}

$albumId = generateUUID();

$stmt = $connect->prepare("
    INSERT INTO albums (
        id,
        user_id,
        template_id,
        title,
        cover_url,
        pages_count
    )
    VALUES (?, ?, ?, ?, ?, 0)
");

$result = $stmt->execute([
    $albumId,
    $userId,
    $templateIdValue,
    $title,
    $coverUrl
]);

if (!$result) {
    jsonResponse(false, 'Не удалось создать альбом');
}

jsonResponse(true, 'Альбом создан', [
    'album_id' => $albumId,
    'title' => $title,
    'cover_url' => $coverUrl,
    'redirect' => '?page=album_editor&id=' . $albumId
]);