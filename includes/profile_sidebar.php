<?php
    $active_nav = $active_nav ?? 'profile';
    
    $stmt = $connect->prepare("SELECT COUNT(*) FROM recipes WHERE author_id = ? AND deleted_at IS NULL");
    $stmt->execute([$_SESSION['user_id']]);
    $my_recipes_count = (int)$stmt->fetchColumn();
    
    $stmt = $connect->prepare("SELECT COUNT(*) FROM albums WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $albums_count = (int)$stmt->fetchColumn(); 
    
    $stmt = $connect->prepare("SELECT COUNT(*) FROM user_favorites WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $favorites_count = (int)$stmt->fetchColumn();
?>

<aside class="profile_sidebar">
    <nav class="profile_nav">
        <a href="?page=profile" class="nav_item <?= $active_nav === 'profile' ? 'active' : '' ?>">
            <img src="<?= $active_nav === 'profile' ? 'asset/img/icon_lk_active.svg' : 'asset/img/icon_lk.svg' ?>"
                alt="">
            <span>Личный кабинет</span>
        </a>
        <a href="?page=albums" class="nav_item <?= $active_nav === 'albums' ? 'active' : '' ?>">
            <img src="asset/img/icon_document.svg" alt="">
            <span>Мои альбомы</span>
            <?php if ($albums_count > 0): ?>
            <span class="nav_badge"><?= $albums_count ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=my_recipes" class="nav_item <?= $active_nav === 'my_recipes' ? 'active' : '' ?>">
            <img src="<?= $active_nav === 'my_recipes' ? 'asset/img/icon_folder_active.svg' : 'asset/img/icon_folder.svg' ?>"
                alt="">
            <span>Мои рецепты</span>
            <?php if ($my_recipes_count > 0): ?>
            <span class="nav_badge"><?= $my_recipes_count ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=ideas" class="nav_item <?= $active_nav === 'ideas' ? 'active' : '' ?>">
            <img src="<?= $active_nav === 'ideas' ? 'asset/img/icon_favorite_active.svg' : 'asset/img/icon_favorite.svg' ?>"
                alt="">
            <span>Идеи</span>
            <?php if ($favorites_count > 0): ?>
            <span class="nav_badge"><?= $favorites_count ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=subscriptions" class="nav_item <?= $active_nav === 'subscriptions' ? 'active' : '' ?>">
            <img src="<?= $active_nav === 'subscriptions' ? 'asset/img/icon_people_active.svg' : 'asset/img/icon_people.svg' ?>"
                alt="">
            <span>Подписки</span>
        </a>
    </nav>
    <button type="button" class="btn v1" id="logoutBtn">
        <img src="asset/img/icon_logout.svg" alt="">
        <span>Выйти</span>
    </button>
</aside>

<div class="modal-overlay" id="logoutOverlay"></div>
<div class="modal" id="logoutModal">
    <button class="modal_close" aria-label="Закрыть"></button>
    <h2 class="modal_title">Выйти из аккаунта?</h2>
    <p style="text-align:center; color:#666; margin-bottom:24px;">Вы уверены, что хотите выйти?</p>
    <div style="display:flex; gap:12px;">
        <button type="button" class="btn v1 btn_cancel" style="flex:1; background:#f0f0f0; color:#333;">Отмена</button>
        <button type="button" class="btn v1 btn_confirm" style="flex:1;">Выйти</button>
    </div>
</div>