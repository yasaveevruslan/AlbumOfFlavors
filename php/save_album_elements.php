<?php

header('Content-Type: application/json; charset=utf-8');

$album_id = $_GET['id'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';

try {

    if ($album_id === '' || $user_id === '') {
        throw new Exception('Не удалось определить альбом или пользователя.');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        throw new Exception('Некорректные данные.');
    }

    // Проверка CSRF
    if (
        empty($_SESSION['editor_csrf']) ||
        !isset($input['csrf_token']) ||
        !is_string($input['csrf_token']) ||
        !hash_equals($_SESSION['editor_csrf'], $input['csrf_token'])
    ) {
        http_response_code(403);
        throw new Exception('Ошибка проверки безопасности. Обнови страницу.');
    }

    $submittedPages = $input['pages'] ?? null;

    if (
        !is_array($submittedPages) ||
        count($submittedPages) === 0 ||
        count($submittedPages) > 100
    ) {
        throw new Exception('Некорректный список страниц.');
    }

    // Проверяем владельца альбома
    $stmt = $connect->prepare("
        SELECT id
        FROM albums
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$album_id, $user_id]);

    if (!$stmt->fetchColumn()) {
        throw new Exception('Альбом не найден или нет доступа.');
    }

    $allowedCardTemplates = [
        'recipe',
        'photo',
        'ingredients',
        'steps',
        'tip',
        'rating'
    ];

    $connect->beginTransaction();

    foreach ($submittedPages as $submittedPage) {

        $pageId = $submittedPage['page_id'] ?? '';
        $elements = $submittedPage['elements'] ?? null;

        if (
            !is_string($pageId) ||
            $pageId === '' ||
            !is_array($elements)
        ) {
            throw new Exception('Некорректная страница.');
        }

        // Проверяем принадлежность страницы альбому
        $stmt = $connect->prepare("
            SELECT id
            FROM album_pages
            WHERE id = ? AND album_id = ?
        ");

        $stmt->execute([$pageId, $album_id]);

        if (!$stmt->fetchColumn()) {
            throw new Exception('Страница не принадлежит этому альбому.');
        }

        // Удаляем прежние элементы страницы
        $stmt = $connect->prepare("
            DELETE FROM page_elements
            WHERE page_id = ?
        ");

        $stmt->execute([$pageId]);

        foreach ($elements as $element) {

            if (!is_array($element)) {
                throw new Exception('Некорректный элемент.');
            }

            $submittedType = $element['type'] ?? '';

            $assetId = null;
            $sourceUrl = null;
            $type = '';

            
            // КАРТОЧКА
            

            if ($submittedType === 'card') {

                $template = $element['template'] ?? '';

                if (
                    !is_string($template) ||
                    !in_array($template, $allowedCardTemplates, true)
                ) {
                    throw new Exception('Неизвестный шаблон карточки.');
                }

                $type = 'card';

                // У карточки нет записи в assets.
                $assetId = null;
                $sourceUrl = null;

                $submittedData = $element['data'] ?? [];

                if (!is_array($submittedData)) {
                    $submittedData = [];
                }

                $title = $submittedData['title'] ?? '';
                $text = $submittedData['text'] ?? '';
                $time = $submittedData['time'] ?? '';
                $servings = $submittedData['servings'] ?? '';
                $recipeId = $submittedData['recipe_id'] ?? '';

                if (
                    !is_string($title) ||
                    !is_string($text) ||
                    !is_string($time) ||
                    !is_string($servings) ||
                    !is_string($recipeId)
                ) {
                    throw new Exception('Некорректные данные карточки.');
                }

                // По умолчанию дополнительных данных рецепта нет.
                $coverUrl = '';
                $calories = '';

                // Если карточка создана из избранного рецепта,
                // проверяем, что рецепт действительно принадлежит избранному пользователя.
                if ($recipeId !== '') {

                    $stmt = $connect->prepare("
                        SELECT
                            r.cover_url,
                            r.calories
                        FROM user_favorites uf
                        INNER JOIN recipes r
                            ON r.id = uf.recipe_id
                        WHERE uf.user_id = ?
                        AND r.id = ?
                        AND r.status = 'published'
                        AND r.deleted_at IS NULL
                        LIMIT 1
                    ");

                    $stmt->execute([$user_id, $recipeId]);

                    $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$recipe) {
                        throw new Exception(
                            'Рецепт не найден или отсутствует в избранном.'
                        );
                    }

                    // Берём обложку и калорийность непосредственно из базы,
                    // а не доверяем значениям, присланным браузером.
                    $coverUrl = (string)($recipe['cover_url'] ?? '');
                    $calories = (string)($recipe['calories'] ?? '');
                }

                $data = [
                    'template' => $template,
                    'title' => mb_substr($title, 0, 200),
                    'text' => mb_substr($text, 0, 2000),
                    'time' => mb_substr($time, 0, 50),
                    'servings' => mb_substr($servings, 0, 50),
                    'recipe_id' => $recipeId,
                    'cover_url' => $coverUrl,
                    'calories' => $calories
                ];
            } else {

                
                // ОБЫЧНЫЙ МАТЕРИАЛ ИЛИ ФОТОГРАФИЯ
                

                $assetId = $element['asset_id'] ?? '';

                if (!is_string($assetId) || $assetId === '') {
                    throw new Exception('У элемента отсутствует материал.');
                }

                // Сначала ищем в общей библиотеке
                $stmt = $connect->prepare("
                    SELECT id, type, image_url
                    FROM assets
                    WHERE id = ? AND is_active = 1
                ");

                $stmt->execute([$assetId]);

                $asset = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($asset) {

                    $type = $asset['type'];
                    $sourceUrl = $asset['image_url'];

                } else {

                    // Затем проверяем личную библиотеку пользователя
                    $stmt = $connect->prepare("
                        SELECT id, type, url
                        FROM user_assets
                        WHERE id = ?
                          AND user_id = ?
                          AND type = 'photo'
                    ");

                    $stmt->execute([$assetId, $user_id]);

                    $userPhoto = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$userPhoto) {
                        throw new Exception(
                            'Материал не найден или нет доступа к фотографии.'
                        );
                    }

                    $type = 'photo';
                    $sourceUrl = $userPhoto['url'];
                }

                $data = $element['data'] ?? new stdClass();
            }

            
            // КООРДИНАТЫ И РАЗМЕРЫ
            

            $x = filter_var(
                $element['x'] ?? null,
                FILTER_VALIDATE_FLOAT
            );

            $y = filter_var(
                $element['y'] ?? null,
                FILTER_VALIDATE_FLOAT
            );

            $width = filter_var(
                $element['width'] ?? null,
                FILTER_VALIDATE_FLOAT
            );

            $height = filter_var(
                $element['height'] ?? null,
                FILTER_VALIDATE_FLOAT
            );

            $rotation = filter_var(
                $element['rotation'] ?? 0,
                FILTER_VALIDATE_FLOAT
            );

            if (
                $x === false || $y === false ||
                $width === false || $height === false ||
                $rotation === false ||
                !is_finite((float)$x) ||
                !is_finite((float)$y) ||
                !is_finite((float)$width) ||
                !is_finite((float)$height) ||
                !is_finite((float)$rotation) ||
                $width <= 0 || $height <= 0 ||
                $width > 2000 || $height > 2000 ||
                abs($x) > 10000 || abs($y) > 10000
            ) {
                throw new Exception('Некорректные координаты элемента.');
            }

            $zIndex = (int)($element['z_index'] ?? 1);

            $jsonData = json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            if ($jsonData === false) {
                throw new Exception('Не удалось обработать данные элемента.');
            }

            
            // СОХРАНЕНИЕ ЭЛЕМЕНТА
            

            $stmt = $connect->prepare("
                INSERT INTO page_elements (
                    id,
                    page_id,
                    type,
                    source_id,
                    source_url,
                    position_x,
                    position_y,
                    width,
                    height,
                    rotation,
                    z_index,
                    data,
                    created_at,
                    updated_at
                ) VALUES (
                    UUID(),
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )
            ");

            $stmt->execute([
                $pageId,
                $type,
                $assetId,
                $sourceUrl,
                $x,
                $y,
                $width,
                $height,
                $rotation,
                $zIndex,
                $jsonData
            ]);
        }
    }

    // Обновляем дату изменения альбома
    $stmt = $connect->prepare("
        UPDATE albums
        SET updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$album_id, $user_id]);

    $connect->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Альбом успешно сохранён.'
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