<?php

header('Content-Type: application/json; charset=utf-8');

$album_id = $_GET['id'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';

try {

    // Проверяем альбом и пользователя.
    if ($album_id === '' || $user_id === '') {
        throw new Exception('Не удалось определить альбом или пользователя.');
    }

    // Получаем JSON из запроса.
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        throw new Exception('Некорректные данные запроса.');
    }

    // Проверяем CSRF-токен.
    if (
        empty($_SESSION['editor_csrf']) ||
        !isset($input['csrf_token']) ||
        !is_string($input['csrf_token']) ||
        !hash_equals($_SESSION['editor_csrf'], $input['csrf_token'])
    ) {
        http_response_code(403);
        throw new Exception('Ошибка проверки безопасности. Обнови страницу.');
    }

    // Проверяем, что альбом принадлежит пользователю.
    $stmt = $connect->prepare("
        SELECT id
        FROM albums
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$album_id, $user_id]);

    if (!$stmt->fetchColumn()) {
        throw new Exception('Альбом не найден или нет доступа.');
    }

    $connect->beginTransaction();

    // Находим последнюю страницу альбома.
    $stmt = $connect->prepare("
        SELECT COALESCE(MAX(page_number), 0)
        FROM album_pages
        WHERE album_id = ?
    ");

    $stmt->execute([$album_id]);

    $maxPageNumber = (int)$stmt->fetchColumn();

    // Следующая левая страница должна иметь чётный номер.
    $newLeftNumber = max(2, $maxPageNumber + 1);

    if ($newLeftNumber % 2 !== 0) {
        $newLeftNumber++;
    }

    $newRightNumber = $newLeftNumber + 1;

    // Получаем layout предыдущего разворота.
    $previousLeftNumber = $newLeftNumber - 2;
    $previousRightNumber = $newLeftNumber - 1;

    $stmt = $connect->prepare("
        SELECT page_number, layout
        FROM album_pages
        WHERE album_id = ?
          AND page_number IN (?, ?)
    ");

    $stmt->execute([
        $album_id,
        $previousLeftNumber,
        $previousRightNumber
    ]);

    $previousLayouts = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $previousLayouts[(int)$row['page_number']] =
            $row['layout'] ?? '{}';
    }

    $leftLayout = $previousLayouts[$previousLeftNumber] ?? '{}';
    $rightLayout = $previousLayouts[$previousRightNumber] ?? '{}';

    // Создаём две новые страницы.
    $stmt = $connect->prepare("INSERT INTO album_pages (id, album_id, page_number, layout) VALUES (UUID(), ?, ?,?)");

    $stmt->execute([
        $album_id,
        $newLeftNumber,
        $leftLayout
    ]);

    $stmt->execute([
        $album_id,
        $newRightNumber,
        $rightLayout
    ]);
    $stmt = $connect->prepare("
        SELECT COUNT(*)
        FROM album_pages
        WHERE album_id = ?
    ");

    $stmt->execute([$album_id]);

    $pagesCount = (int)$stmt->fetchColumn();

    $stmt = $connect->prepare("
        UPDATE albums
        SET pages_count = ?,
            updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([
        $pagesCount,
        $album_id,
        $user_id
    ]);

    // Получаем ID созданных страниц
    $stmt = $connect->prepare("
        SELECT id, page_number, layout
        FROM album_pages
        WHERE album_id = ?
          AND page_number IN (?, ?)
        ORDER BY page_number ASC
    ");

    $stmt->execute([
        $album_id,
        $newLeftNumber,
        $newRightNumber
    ]);

    $newPages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($newPages) !== 2) {
        throw new Exception('Не удалось создать новый разворот.');
    }

    $connect->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Новый разворот успешно добавлен.',
        'pages' => $newPages,
        'spread_index' => (int)floor(($newLeftNumber - 2) / 2)
    ], JSON_UNESCAPED_UNICODE);

    exit;

} catch (Throwable $e) {

    if ($connect->inTransaction()) {
        $connect->rollBack();
    }

    if (http_response_code() < 400) {
        http_response_code(400);
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}