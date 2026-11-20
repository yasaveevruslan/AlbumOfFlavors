<?php
require_once __DIR__ . '/album_functions.php';

$userId = getUserId();

$albumId = trim($_POST['album_id'] ?? '');
$templateId = trim($_POST['template_id'] ?? '');
$coverUrl = trim($_POST['cover_url'] ?? '');

if ($albumId === '') {
    jsonResponse(false, 'Не указан альбом');
}

if (!checkAlbumOwner($connect, $albumId, $userId)) {
    jsonResponse(false, 'Альбом не найден');
}

$templateIdValue = null;

// Выбор готового шаблона
if ($templateId !== '') {

    $stmt = $connect->prepare("
        SELECT id, cover_url
        FROM album_templates
        WHERE id = ? AND is_active = 1
    ");

    $stmt->execute([$templateId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        jsonResponse(false, 'Обложка не найдена');
    }

    $coverUrl = $template['cover_url'];
    $templateIdValue = $template['id'];
}

// Загрузка своей обложки
if (
    isset($_FILES['cover_file']) &&
    $_FILES['cover_file']['error'] !== UPLOAD_ERR_NO_FILE
) {
    $coverUrl = uploadAlbumCover($_FILES['cover_file']);
    $templateIdValue = null;
}

if ($coverUrl === '') {
    jsonResponse(false, 'Выберите обложку');
}

$stmt = $connect->prepare("
    UPDATE albums
    SET cover_url = ?,
        template_id = ?,
        updated_at = NOW()
    WHERE id = ? AND user_id = ?
");

if (!$stmt->execute([
    $coverUrl,
    $templateIdValue,
    $albumId,
    $userId
])) {
    jsonResponse(false, 'Ошибка при сохранении обложки');
}

jsonResponse(true, 'Обложка изменена', [
    'cover_url' => $coverUrl,
    'template_id' => $templateIdValue
]);