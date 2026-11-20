<?php
session_start();
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$user_id = $_SESSION['user_id'];

if ($current_password === '') {
    echo json_encode(['success' => false, 'message' => 'Введите текущий пароль']);
    exit;
}
if (mb_strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Новый пароль должен быть не менее 6 символов']);
    exit;
}
if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Пароли не совпадают']);
    exit;
}

$stmt = $connect->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!password_verify($current_password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Неверный текущий пароль']);
    exit;
}

$new_hash = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $connect->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
if ($stmt->execute([$new_hash, $user_id])) {
    echo json_encode(['success' => true, 'message' => 'Пароль успешно изменён']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
}
?>