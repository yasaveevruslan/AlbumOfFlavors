<?php
session_start();
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['moderator', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}

$entity = $_POST['entity'] ?? '';
$id = $_POST['id'] ?? '';

$allowed_entities = ['recipes', 'categories', 'ingredients', 'comments'];
if (!in_array($entity, $allowed_entities) || $id === '') {
    echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
    exit;
}

try {
    switch ($entity) {
        case 'recipes':
            $stmt = $connect->prepare("UPDATE recipes SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            break;
            
        case 'categories':
            $stmt = $connect->prepare("SELECT COUNT(*) FROM recipe_categories WHERE category_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Нельзя удалить категорию, к которой привязаны рецепты']);
                exit;
            }
            $stmt = $connect->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            break;
            
        case 'ingredients':
            $stmt = $connect->prepare("DELETE FROM recipe_ingredients WHERE id = ?");
            $stmt->execute([$id]);
            break;
            
        case 'comments':
            $stmt = $connect->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$id]);
            break;
    }
    
    echo json_encode(['success' => true, 'message' => 'Запись удалена']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}
?>