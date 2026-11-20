<?php
session_start();

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/connect.php';

    $role = $_SESSION['user_role'] ?? 'user';
    if (!in_array($role, ['moderator', 'admin'])) {
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
        exit;
    }

    $entity = $_POST['entity'] ?? '';
    $id = $_POST['id'] ?? '';

    if ($entity === '' || $id === '') {
        echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
        exit;
    }

    $entity_table_map = [
        'categories' => 'categories',
        'ingredients' => 'recipe_ingredients',
        'comments' => 'comments',
        'newsletter_subscribers' => 'newsletter_subscribers',
    ];

    if (!isset($entity_table_map[$entity])) {
        echo json_encode(['success' => false, 'message' => 'Недопустимая сущность']);
        exit;
    }

    $table = $entity_table_map[$entity];

    $connect->beginTransaction();
    try {
        if ($entity === 'categories') {
            $stmt = $connect->prepare("DELETE FROM recipe_categories WHERE category_id = ?");
            $stmt->execute([$id]);

            $stmt = $connect->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $connect->prepare("DELETE FROM `$table` WHERE id = ?");
            $stmt->execute([$id]);
        }

        $connect->commit();
        echo json_encode(['success' => true, 'message' => 'Запись удалена']);

    } catch (Exception $e) {
        $connect->rollBack();
        echo json_encode(['success' => false, 'message' => 'Ошибка БД: ' . $e->getMessage()]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()]);
}
?>