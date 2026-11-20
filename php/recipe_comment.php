<?php
session_start();
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$recipe_id = trim($_POST['recipe_id'] ?? '');
$text = trim($_POST['text'] ?? '');

if ($recipe_id === '' || $text === '') {
    echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
    exit;
}

if (mb_strlen($text) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Комментарий слишком длинный']);
    exit;
}

$stmt = $connect->prepare(" INSERT INTO comments (id, recipe_id, user_id, text) VALUES (UUID(), ?, ?, ?)");
$stmt->execute([$recipe_id, $_SESSION['user_id'], $text]);

$stmt = $connect->prepare("SELECT c.id, c.text, c.created_at, u.username, u.avatar_url FROM comments c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.id = (SELECT id FROM comments ORDER BY created_at DESC LIMIT 1)
");

$stmt->execute();
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'comment' => [
        'id' => $comment['id'],
        'text' => $comment['text'],
        'username' => $comment['username'],
        'avatar_url' => $comment['avatar_url'],
        'created_at' => date('d.m.Y', strtotime($comment['created_at']))
    ]
]);
?>