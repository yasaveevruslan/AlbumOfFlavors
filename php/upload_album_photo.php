<?php
header('Content-Type: application/json; charset=utf-8');
$user_id = $_SESSION['user_id'] ?? '';
$album_id = $_GET['id'] ?? '';
try {

    if ($user_id === '' || $album_id === '') {
        throw new Exception('Не удалось определить пользователя или альбом.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Недопустимый метод запроса.');
    }

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['editor_csrf']) ||
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['editor_csrf'], $csrfToken)
    ) {
        http_response_code(403);
        throw new Exception('Ошибка проверки безопасности. Обнови страницу.');
    }

    $stmt = $connect->prepare("SELECT id FROM albums WHERE id = ? AND user_id = ?");
    $stmt->execute([$album_id, $user_id]);

    if (!$stmt->fetchColumn()) {
        http_response_code(403);
        throw new Exception('Альбом не найден или нет доступа.');
    }

    if (
        !isset($_FILES['photo']) ||
        $_FILES['photo']['error'] !== UPLOAD_ERR_OK
    ) {
        throw new Exception('Не удалось загрузить фотографию.');
    }

    $file = $_FILES['photo'];

    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('Размер фотографии не должен превышать 10 МБ.');
    }

    if ($file['size'] <= 0 || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Некорректный загруженный файл.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedTypes[$mime])) {
        throw new Exception('Разрешены только JPG, PNG и WEBP изображения.');
    }

    $imageInfo = getimagesize($file['tmp_name']);

    if ($imageInfo === false) {
        throw new Exception('Файл не является корректным изображением.');
    }

    $extension = $allowedTypes[$mime];
    $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

    $uploadDirectory = __DIR__ . '/../uploads/user_assets/';

    if (
        !is_dir($uploadDirectory) &&
        !mkdir($uploadDirectory, 0755, true)
    ) {
        throw new Exception('Не удалось создать папку для фотографий.');
    }

    if (!is_writable($uploadDirectory)) {
        throw new Exception('Нет доступа к папке для загрузки фотографий.');
    }

    $filePath = $uploadDirectory . $fileName;
    $fileUrl = 'uploads/user_assets/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Не удалось сохранить фотографию на сервере.');
    }

    try {

        $stmt = $connect->prepare("INSERT INTO user_assets (id, user_id, type, url,  name, created_at ) VALUES (UUID(), ?, 'photo',?, ?, NOW())");

        $originalName = basename($file['name']);
        $originalName = mb_substr($originalName, 0, 255);

        $stmt->execute([
            $user_id,
            $fileUrl,
            $originalName
        ]);

        $photoId = $connect->lastInsertId();

        $stmt = $connect->prepare("
            SELECT id
            FROM user_assets
            WHERE user_id = ? AND url = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->execute([$user_id, $fileUrl]);

        $photoId = $stmt->fetchColumn();

        if (!$photoId) {
            throw new Exception('Не удалось получить фотографию из базы данных.');
        }

    } catch (Throwable $e) {
        if (is_file($filePath)) {
            unlink($filePath);
        }

        throw $e;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Фотография успешно загружена.',
        'photo' => [
            'id' => $photoId,
            'type' => 'photo',
            'url' => $fileUrl,
            'name' => $originalName,
            'width' => $imageInfo[0],
            'height' => $imageInfo[1]
        ]
    ], JSON_UNESCAPED_UNICODE);

    exit;

} catch (Throwable $e) {

    if (http_response_code() < 400) {
        http_response_code(400);
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}