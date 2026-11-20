<?php
session_start();
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Файл не был загружен или ошибка загрузки']);
    exit;
}

$file = $_FILES['avatar'];

$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
$max_size = 2 * 1024 * 1024; // 2 МБ

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Разрешены только форматы JPG, PNG, WEBP']);
    exit;
}

if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'Размер файла не должен превышать 2 МБ']);
    exit;
}

$upload_dir = __DIR__ . '/../asset/img/avatars/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = 'avatar_' . $user_id . '_' . time() . '.' . strtolower($ext);
$upload_path = $upload_dir . $new_filename;
$db_path = 'asset/img/avatars/' . $new_filename;

if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении файла на сервере']);
    exit;
}

$stmt = $connect->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
if ($stmt->execute([$db_path, $user_id])) {
    $stmt_name = $connect->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_name->execute([$user_id]);
    $username = $stmt_name->fetchColumn();
    $avatar_letter = mb_strtoupper(mb_substr($username, 0, 1));

    echo json_encode([
        'success' => true, 
        'message' => 'Аватар успешно обновлён',
        'avatar_url' => $db_path,
        'avatar_letter' => $avatar_letter
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении базы данных']);
}
?>