<?php
session_start();
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $errors = [];

    if (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
        $errors['username'] = 'Имя должно быть от 3 до 50 символов';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректную электронную почту';
    }
    if (mb_strlen($password) < 6) {
        $errors['password'] = 'Пароль должен быть не менее 6 символов';
    }
    if ($password !== $password_confirm) {
        $errors['password_confirm'] = 'Пароли не совпадают';
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = $connect->prepare("SELECT email, username FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    $taken = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($taken) {
        if ($taken['email'] === $email) $errors['email'] = 'Эта почта уже зарегистрирована';
        if ($taken['username'] === $username) $errors['username'] = 'Это имя уже занято';
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $connect->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')");
    
    if ($stmt->execute([$username, $email, $password_hash])) {
        $stmt_user = $connect->prepare("SELECT id, username, role FROM users WHERE email = ?");
        $stmt_user->execute([$email]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];

        echo json_encode(['success' => true, 'redirect' => 'index.php?page=main']);
    } else {
        echo json_encode(['success' => false, 'errors' => ['system' => 'Ошибка сервера']]);
    }
}
?>