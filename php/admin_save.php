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
$mode = $id ? 'edit' : 'create';

$allowed_entities = ['recipes', 'categories', 'tags', 'ingredients', 'comments'];
if (!in_array($entity, $allowed_entities)) {
    echo json_encode(['success' => false, 'message' => 'Неверная сущность']);
    exit;
}

try {
    switch ($entity) {
        case 'recipes':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $cooking_time = !empty($_POST['cooking_time']) ? (int)$_POST['cooking_time'] : null;
            $servings = !empty($_POST['servings']) ? (int)$_POST['servings'] : null;
            $calories = !empty($_POST['calories']) ? (int)$_POST['calories'] : null;
            $cover_url = trim($_POST['cover_url'] ?? '');
            $status = $_POST['status'] ?? 'draft';
            $author_id = !empty($_POST['author_id']) ? $_POST['author_id'] : null;
            
            if ($title === '') {
                echo json_encode(['success' => false, 'message' => 'Название обязательно']);
                exit;
            }
            
            if (!in_array($status, ['published', 'draft', 'moderation', 'hidden'])) {
                echo json_encode(['success' => false, 'message' => 'Неверный статус']);
                exit;
            }
            
            if ($mode === 'edit') {
                $stmt = $connect->prepare("
                    UPDATE recipes 
                    SET title = ?, description = ?, cooking_time = ?, servings = ?, 
                        calories = ?, cover_url = ?, status = ?, author_id = ?
                    WHERE id = ? AND deleted_at IS NULL
                ");
                $stmt->execute([$title, $description, $cooking_time, $servings, $calories, $cover_url, $status, $author_id, $id]);
            } else {
                $stmt = $connect->prepare("
                    INSERT INTO recipes (title, description, cooking_time, servings, calories, cover_url, status, author_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $description, $cooking_time, $servings, $calories, $cover_url, $status, $author_id]);
                $id = $connect->lastInsertId();
            }
            break;
            
        case 'categories':
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $icon_url = trim($_POST['icon_url'] ?? '');
            
            if ($name === '' || $slug === '') {
                echo json_encode(['success' => false, 'message' => 'Название и slug обязательны']);
                exit;
            }
            
            if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
                echo json_encode(['success' => false, 'message' => 'Slug может содержать только латинские буквы, цифры и дефис']);
                exit;
            }
            
            $stmt = $connect->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id ?: '']);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Такой slug уже существует']);
                exit;
            }
            
            if ($mode === 'edit') {
                $stmt = $connect->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, icon_url = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $icon_url, $id]);
            } else {
                $stmt = $connect->prepare("INSERT INTO categories (name, slug, description, icon_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $icon_url]);
            }
            break;
            
        case 'tags':
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => 'Название обязательно']);
                exit;
            }
            
            if ($mode === 'edit') {
                $stmt = $connect->prepare("UPDATE tags SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
            } else {
                $stmt = $connect->prepare("INSERT INTO tags (name) VALUES (?)");
                $stmt->execute([$name]);
            }
            break;
            
        case 'ingredients':
            $name = trim($_POST['name'] ?? '');
            $amount = trim($_POST['amount'] ?? '');
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => 'Название обязательно']);
                exit;
            }
            
            if ($mode === 'edit') {
                $stmt = $connect->prepare("UPDATE recipe_ingredients SET name = ?, amount = ? WHERE id = ?");
                $stmt->execute([$name, $amount, $id]);
            } else {
                $stmt = $connect->prepare("INSERT INTO recipe_ingredients (name, amount) VALUES (?, ?)");
                $stmt->execute([$name, $amount]);
            }
            break;
            
        case 'comments':
            $text = trim($_POST['text'] ?? '');
            if ($text === '') {
                echo json_encode(['success' => false, 'message' => 'Текст комментария обязателен']);
                exit;
            }
            
            if ($mode === 'edit') {
                $stmt = $connect->prepare("UPDATE comments SET text = ? WHERE id = ?");
                $stmt->execute([$text, $id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Создание комментариев через эту форму не поддерживается']);
                exit;
            }
            break;
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $mode === 'edit' ? 'Изменения сохранены' : 'Запись создана',
        'id' => $id
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}
?>