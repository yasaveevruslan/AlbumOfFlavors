<?php
session_start();
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$recipe_id = $_POST['recipe_id'] ?? '';
$user_id = $_SESSION['user_id'];

if ($recipe_id === '') {
    echo json_encode(['success' => false, 'message' => 'Неверный ID рецепта']);
    exit;
}

$stmt = $connect->prepare("
    SELECT id, title, status 
    FROM recipes 
    WHERE id = ? AND author_id = ? AND deleted_at IS NULL
");
$stmt->execute([$recipe_id, $user_id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    echo json_encode(['success' => false, 'message' => 'Рецепт не найден или у вас нет прав']);
    exit;
}

$stmt = $connect->prepare("UPDATE recipes SET deleted_at = NOW() WHERE id = ?");
if ($stmt->execute([$recipe_id])) {
    echo json_encode([
        'success' => true, 
        'message' => 'Рецепт успешно удалён',
        'recipe_title' => $recipe['title']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
}
?>