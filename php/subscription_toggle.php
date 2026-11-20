<?php
session_start();
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

try {
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['system' => 'Необходима авторизация']]);
    exit;
}

$target_user_id = $_POST['user_id'] ?? '';
$action = $_POST['action'] ?? 'toggle';
$current_user_id = $_SESSION['user_id'];

if ($target_user_id === '') {
    echo json_encode(['success' => false, 'message' => 'Неверный пользователь']);
    exit;
}

if ($target_user_id === $current_user_id) {
    echo json_encode(['success' => false, 'message' => 'Нельзя подписаться на себя']);
    exit;
}

$stmt = $connect->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$target_user_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
    exit;
}

$stmt = $connect->prepare("SELECT 1 FROM subscriptions WHERE follower_id = ? AND following_id = ?");
$stmt->execute([$current_user_id, $target_user_id]);
$is_following = $stmt->fetch() ? true : false;

    $should_follow = false;
    if ($action === 'follow') {
        $should_follow = true;
    } elseif ($action === 'unfollow') {
        $should_follow = false;
    } else {
        $should_follow = !$is_following;
    }

    if ($should_follow && !$is_following) {
        $stmt = $connect->prepare("INSERT INTO subscriptions (follower_id, following_id) VALUES (?, ?)");
        $success = $stmt->execute([$current_user_id, $target_user_id]);
        $new_action = 'followed';
    } elseif (!$should_follow && $is_following) {
        $stmt = $connect->prepare("DELETE FROM subscriptions WHERE follower_id = ? AND following_id = ?");
        $success = $stmt->execute([$current_user_id, $target_user_id]);
        $new_action = 'unfollowed';
    } else {
        $success = true;
        $new_action = $is_following ? 'followed' : 'unfollowed';
    }

    if ($success) {
        $stmt = $connect->prepare("SELECT COUNT(*) FROM subscriptions WHERE following_id = ?");
        $stmt->execute([$target_user_id]);
        $new_followers_count = (int)$stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'action' => $new_action,
            'followers_count' => $new_followers_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера при обновлении']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка сервера: ' . $e->getMessage()
    ]);
}
?>