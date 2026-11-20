<?php
    global $connect;
    
    $stmt = $connect->prepare("
        SELECT id, username, email, avatar_url, role, created_at 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: ?page=main');
        exit;
    }
    
    $stmt = $connect->prepare("SELECT COUNT(*) FROM subscriptions WHERE following_id = ?");
    $stmt->execute([$user['id']]);
    $followers_count = (int)$stmt->fetchColumn();
    
    $stmt = $connect->prepare("SELECT COUNT(*) FROM subscriptions WHERE follower_id = ?");
    $stmt->execute([$user['id']]);
    $following_count = (int)$stmt->fetchColumn();
    
    $avatar_letter = mb_strtoupper(mb_substr($user['username'], 0, 1));
    
    $active_nav = $active_nav ?? 'profile';
?>

<div class="profile_header">
    <div class="profile_cover">
        <img src="asset/img/profile_cover.png" alt="Фон профиля">
    </div>
    <div class="profile_info">
        <div class="profile_avatar_wrapper">
            <div class="profile_avatar">
                <?php if (!empty($user['avatar_url'])): ?>
                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Аватар">
                <?php else: ?>
                <span class="avatar_letter"><?= htmlspecialchars($avatar_letter) ?></span>
                <?php endif; ?>
            </div>
            <button class="avatar_edit" title="Изменить аватар" id="avatarEditBtn">
                <img src="asset/img/icon_edit_user.svg" alt="">
            </button>
        </div>
        <div class="profile_text">
            <h1 class="profile_username"><?= htmlspecialchars($user['username']) ?></h1>
            <p class="profile_email"><?= htmlspecialchars($user['email']) ?></p>
        </div>
        <div class="profile_stats">
            <button class="stat_button" onclick="window.location.href='?page=subscriptions&tab=followers'">
                <img src="asset/img/icon_star.svg" alt="">
                <div class="stat_info">
                    <span class="stat_count"><?= $followers_count ?></span>
                    <span class="stat_label">подписчиков</span>
                </div>
            </button>
            <button class="stat_button" onclick="window.location.href='?page=subscriptions'">
                <div class="stat_info">
                    <span class="stat_count"><?= $following_count ?></span>
                    <span class="stat_label">подписок</span>
                </div>
            </button>
        </div>
    </div>
</div>

<input type="file" id="avatarInput" accept="image/png, image/jpeg, image/webp" style="display: none;">

<p id="avatarMessage" style="display: none; margin-top: 8px; font-size: 0.9em; text-align: center;"></p>