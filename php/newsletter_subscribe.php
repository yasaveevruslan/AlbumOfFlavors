<?php
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$errors = [];

if ($email === '') {
    $errors['email'] = 'Введите электронную почту';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Некорректный формат почты';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$stmt = $connect->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = ?");
$stmt->execute([$email]);
$subscriber = $stmt->fetch(PDO::FETCH_ASSOC);

if ($subscriber) {
    if ($subscriber['status'] === 'active') {
        echo json_encode(['success' => false, 'errors' => ['email' => 'Вы уже подписаны на рассылку']]);
        exit;
    } elseif ($subscriber['status'] === 'unsubscribed') {
        $stmt = $connect->prepare("UPDATE newsletter_subscribers SET status = 'active', unsubscribed_at = NULL, subscribed_at = NOW() WHERE email = ?");
        $stmt->execute([$email]);
        
        echo json_encode(['success' => true, 'message' => 'Подписка возобновлена!']);
        exit;
    }
}

$stmt = $connect->prepare("INSERT INTO newsletter_subscribers (email, status) VALUES (?, 'active')");

if ($stmt->execute([$email])) {
    echo json_encode(['success' => true, 'message' => 'Спасибо за подписку!']);
} else {
    echo json_encode(['success' => false, 'errors' => ['system' => 'Ошибка сервера. Попробуйте позже.']]);
}
?>