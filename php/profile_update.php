<?php
session_start();
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['system' => 'Необходима авторизация']]);
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$user_id = $_SESSION['user_id'];
$errors = [];

if ($username === '') {
    $errors['username'] = 'Введите имя пользователя';
} elseif (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
    $errors['username'] = 'Имя должно быть от 3 до 50 символов';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректную почту';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$stmt = $connect->prepare("SELECT username, email FROM users WHERE (username = ? OR email = ?) AND id != ? AND deleted_at IS NULL");
$stmt->execute([$username, $email, $user_id]);
$taken = $stmt->fetch(PDO::FETCH_ASSOC);

if ($taken) {
    if ($taken['username'] === $username) $errors['username'] = 'Это имя уже занято';
    if ($taken['email'] === $email) $errors['email'] = 'Эта почта уже используется';
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$stmt = $connect->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
if ($stmt->execute([$username, $email, $user_id])) {
    $_SESSION['username'] = $username;
    echo json_encode(['success' => true, 'message' => 'Данные успешно обновлены']);
} else {
    echo json_encode(['success' => false, 'errors' => ['system' => 'Ошибка сервера']]);
}
?>