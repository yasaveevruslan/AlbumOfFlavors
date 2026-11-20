<?php
    global $connect;
    $active_nav = 'ideas';
    
    $per_page = 12;
    $current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    $connect->query("SET SESSION group_concat_max_len = 10000");
    
    $stmt = $connect->prepare("SELECT COUNT(*) FROM user_favorites WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_recipes = (int)$stmt->fetchColumn();
    $total_pages = max(1, ceil($total_recipes / $per_page));
    
    $stmt = $connect->prepare("
        SELECT 
            r.id, r.title, r.description, r.cover_url, 
            r.calories, r.cooking_time,
            u.id AS author_id, u.username AS author_name, u.avatar_url AS author_avatar,
            GROUP_CONCAT(CONCAT(ri.name, ' ', ri.amount) ORDER BY ri.order_index ASC SEPARATOR ', ') AS ingredients,
            COUNT(DISTINCT uf2.user_id) AS likes_count,
            uf.created_at AS favorited_at
        FROM user_favorites uf
        INNER JOIN recipes r ON uf.recipe_id = r.id AND r.deleted_at IS NULL
        LEFT JOIN users u ON r.author_id = u.id
        LEFT JOIN recipe_ingredients ri ON r.id = ri.recipe_id
        LEFT JOIN user_favorites uf2 ON r.id = uf2.recipe_id
        WHERE uf.user_id = ?
        GROUP BY r.id, uf.created_at
        ORDER BY uf.created_at DESC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $favorite_recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <a href="?page=profile">Личный кабинет</a>
        <span>Идеи</span>
    </nav>

    <?php include __DIR__ . '/../../includes/profile_header.php'; ?>

    <div class="profile_container">
        <?php include __DIR__ . '/../../includes/profile_sidebar.php'; ?>

        <div class="profile_content">
            <h2 class="section_title">Сохранённые рецепты (<?= $total_recipes ?>)</h2>

            <?php if (!empty($favorite_recipes)): ?>
            <div class="recipes_grid" id="favoritesGrid">
                <?php foreach ($favorite_recipes as $recipe): ?>
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
                                    <button class="btn_icon btn_heart active" title="Убрать из избранного"
                                        data-recipe-id="<?= $recipe['id'] ?>">
                                        <img src="asset/img/icon_heart-active.svg" alt="heart">
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

            <?php if ($total_pages > 1): ?>
            <div class="pagination mt-16">
                <?php if ($current_page > 1): ?>
                <a href="?page=favorites&p=<?= $current_page - 1 ?>" class="pagination_arrow">←</a>
                <?php else: ?>
                <span class="pagination_arrow disabled">←</span>
                <?php endif; ?>

                <div class="pagination_pages">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=favorites&p=<?= $i ?>" class="page <?= $i == $current_page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                </div>

                <?php if ($current_page < $total_pages): ?>
                <a href="?page=favorites&p=<?= $current_page + 1 ?>" class="pagination_arrow">→</a>
                <?php else: ?>
                <span class="pagination_arrow disabled">→</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="empty_state">
                <h3>Пока нет сохранённых рецептов</h3>
                <p style="color:#888; margin-bottom:24px;">Нажмите на сердечко у любого рецепта, чтобы добавить его сюда
                </p>
                <a href="?page=categories" class="btn v1">Найти рецепты</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>