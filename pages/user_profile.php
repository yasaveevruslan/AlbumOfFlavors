<?php
    global $connect;

    $target_user_id = $_GET['id'] ?? '';
    
    if ($target_user_id === '') {
        echo "<div class='container'><h1>Пользователь не указан</h1></div>";
        return;
    }
    if (isset($_SESSION['user_id'])) {
        if ($target_user_id === $_SESSION['user_id']){
            header('Location: ?page=profile');
        }
    }
    $stmt = $connect->prepare("
        SELECT id, username, avatar_url, created_at 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$target_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<div class='container'><h1>Пользователь не найден или был удален</h1></div>";
        return;
    }

    $stmt = $connect->prepare("SELECT COUNT(*) FROM subscriptions WHERE following_id = ?");
    $stmt->execute([$target_user_id]);
    $followers_count = (int)$stmt->fetchColumn();

    $is_following = false;
    $is_own_profile = false;
    
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['user_id'] === $target_user_id) {
            $is_own_profile = true;
        } else {
            $stmt = $connect->prepare("SELECT 1 FROM subscriptions WHERE follower_id = ? AND following_id = ?");
            $stmt->execute([$_SESSION['user_id'], $target_user_id]);
            $is_following = (bool)$stmt->fetch();
        }
    }

    $avatar_letter = mb_strtoupper(mb_substr($user['username'], 0, 1));
    $connect->query("SET SESSION group_concat_max_len = 10000");
    
    $sql = "
        SELECT 
            r.id, r.title, r.description, r.cover_url, 
            r.calories, r.cooking_time,
            GROUP_CONCAT(CONCAT(ri.name, ' ', ri.amount) ORDER BY ri.order_index ASC SEPARATOR ', ') AS ingredients,
            COUNT(DISTINCT uf.user_id) AS likes_count
        FROM recipes r
        LEFT JOIN recipe_ingredients ri ON r.id = ri.recipe_id
        LEFT JOIN user_favorites uf ON r.id = uf.recipe_id
        WHERE r.author_id = ? AND r.status = 'published' AND r.deleted_at IS NULL
        GROUP BY r.id
        ORDER BY r.created_at DESC
        LIMIT 12
    ";
    $stmt = $connect->prepare($sql);
    $stmt->execute([$target_user_id]);
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $liked_recipe_ids = [];
    if (isset($_SESSION['user_id']) && !empty($recipes)) {
        $recipe_ids = array_column($recipes, 'id');
        $placeholders = implode(',', array_fill(0, count($recipe_ids), '?'));
        $stmt_likes = $connect->prepare("SELECT recipe_id FROM user_favorites WHERE user_id = ? AND recipe_id IN ($placeholders)");
        $stmt_likes->execute(array_merge([$_SESSION['user_id']], $recipe_ids));
        $liked_recipe_ids = $stmt_likes->fetchAll(PDO::FETCH_COLUMN);
    }
?>

<div class="container">
    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <span><?= htmlspecialchars($user['username']) ?></span>
    </nav>

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
            </div>
            <div class="profile_text">
                <h1 class="profile_username"><?= htmlspecialchars($user['username']) ?></h1>
                <p class="profile_email">Участник с <?= htmlspecialchars(getRussianMonthYear($user['created_at'])) ?>
                </p>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center;">
                <div class="profile_stats">
                    <button class="stat_button">
                        <img src="asset/img/icon_star.svg" alt="">
                        <div class="stat_info">
                            <span class="stat_count"><?= $followers_count ?></span>
                            <span class="stat_label">подписчиков</span>
                        </div>
                    </button>
                </div>

                <?php if (!$is_own_profile): ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                <button class="btn v1 <?= $is_following ? 'btn_unsubscribe' : 'btn_subscribe' ?>"
                    data-user-id="<?= htmlspecialchars($user['id']) ?>">
                    <?= $is_following ? 'Отписаться' : 'Подписаться' ?>
                </button>
                <?php else: ?>
                <a href="#" class="btn v1"
                    onclick="if(typeof openModal==='function' && typeof loginModal!=='undefined'){openModal(loginModal); return false;}">
                    Подписаться
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="profile_container">
        <div class="profile_content" style="width: 100%;">
            <h2 class="section_title">Авторские рецепты</h2>

            <?php if (!empty($recipes)): ?>
            <div class="recipes_grid">
                <?php foreach ($recipes as $recipe): 
                    $is_liked = in_array($recipe['id'], $liked_recipe_ids);
                    $heart_class = $is_liked ? 'active' : '';
                    $heart_icon = $is_liked ? 'icon_heart-active.svg' : 'icon_heart.svg';
                ?>
                <div class="recipe_card" data-recipe-id="<?= $recipe['id'] ?>"
                    data-url="?page=recipe&id=<?= $recipe['id'] ?>">
                    <a href="?page=recipe&id=<?= $recipe['id'] ?>" class="recipe_card_clickable_area">
                        <div class="recipe_card_image">
                            <img src="<?= htmlspecialchars($recipe['cover_url'] ?: 'asset/img/placeholder.png') ?>"
                                alt="<?= htmlspecialchars($recipe['title']) ?>" loading="lazy">
                        </div>
                        <div class="recipe_card_body">
                            <div class="recipe_card_meta">
                                <span class="meta_item">
                                    <img src="asset/img/icon_kkal.svg" alt="kkal">
                                    <?= (int)($recipe['calories'] ?? 0) ?> ккал
                                </span>
                                <span class="meta_item">
                                    <img src="asset/img/icon_clock.svg" alt="time">
                                    <?= (int)($recipe['cooking_time'] ?? 0) ?> мин
                                </span>
                            </div>
                            <h3 class="recipe_card_title"><?= htmlspecialchars($recipe['title']) ?></h3>
                            <div class="recipe_card_footer">
                                <p class="recipe_card_ingredients">
                                    <?= htmlspecialchars(mb_strimwidth($recipe['ingredients'] ?? 'Состав не указан', 0, 50, '...')) ?>
                                </p>
                                <div class="recipe_card_actions">
                                    <?php 
                                $is_liked = in_array($recipe['id'], $liked_recipe_ids);
                                $heart_class = $is_liked ? 'active' : '';
                                $heart_icon = $is_liked ? 'icon_heart-active.svg' : 'icon_heart.svg';
                            ?>
                                    <button class="btn_icon btn_heart <?= $heart_class ?>" title="В избранное"
                                        data-recipe-id="<?= $recipe['id'] ?>">
                                        <img src="asset/img/<?= $heart_icon ?>" alt="heart">
                                    </button>
                                    <button class="btn_icon btn_more" title="Ещё" data-toggle="details">
                                        <img src="asset/img/icon_more.svg" alt="more">
                                    </button>
                                </div>
                            </div>
                        </div>
                    </a>
                    <div class="recipe_card_details">
                        <div class="details_ingredients">
                            <h4>Состав:</h4>
                            <ul>
                                <?php if (!empty($recipe['ingredients'])): ?>
                                <?php foreach (explode(', ', $recipe['ingredients']) as $ing): ?>
                                <li><?= htmlspecialchars(trim($ing)) ?></li>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <li>Ингредиенты не указаны</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="details_likes">
                            <img src="asset/img/icon_heart-active.svg" alt="likes">
                            <span>Понравилось: <?= (int)$recipe['likes_count'] ?></span>
                        </div>
                        <?php if (!empty($recipe['author_name']) && !empty($recipe['author_id'])): ?>
                        <a href="?page=user_profile&id=<?= htmlspecialchars($recipe['author_id']) ?>"
                            class="details_author_link">
                            <div class="details_author">
                                <div class="author_avatar">
                                    <?php 
                            $avatar_letter = mb_strtoupper(mb_substr($recipe['author_name'] ?? '?', 0, 1));
                            if (!empty($recipe['author_avatar'])): 
                        ?>
                                    <img src="<?= htmlspecialchars($recipe['author_avatar']) ?>" alt="">
                                    <?php else: ?>
                                    <?= htmlspecialchars($avatar_letter) ?>
                                    <?php endif; ?>
                                </div>
                                <span class="author_name"><?= htmlspecialchars($recipe['author_name']) ?></span>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty_state">
                <p>У этого пользователя пока нет опубликованных рецептов.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>