<?php
session_start();
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $errors = [];

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректную электронную почту';
    }
    if ($password === '') {
        $errors['password'] = 'Введите пароль';
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = $connect->prepare("SELECT id, username, role, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        
        echo json_encode(['success' => true, 'redirect' => 'index.php?page=main']);
    } else {
        echo json_encode(['success' => false, 'errors' => ['password' => 'Неверная почта или пароль']]);
    }
}
?>