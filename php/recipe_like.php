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
    echo json_encode(['success' => false, 'message' => 'Неверный рецепт']);
    exit;
}

$stmt = $connect->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND recipe_id = ?");
$stmt->execute([$user_id, $recipe_id]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $connect->prepare("DELETE FROM user_favorites WHERE id = ?");
    $stmt->execute([$existing['id']]);
    $action = 'unliked';
} else {
    $stmt = $connect->prepare("INSERT INTO user_favorites (id, user_id, recipe_id) VALUES (UUID(), ?, ?)");
    $stmt->execute([$user_id, $recipe_id]);
    $action = 'liked';
}

$stmt = $connect->prepare("SELECT COUNT(*) FROM user_favorites WHERE recipe_id = ?");
$stmt->execute([$recipe_id]);
$new_count = (int)$stmt->fetchColumn();

echo json_encode([
    'success' => true, 
    'action' => $action,
    'likes_count' => $new_count
]);
?>