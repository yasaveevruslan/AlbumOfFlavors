<?php
    global $connect;
    $active_nav = 'my_recipes';
    
    $status_filter = $_GET['status'] ?? 'all'; // all, published, draft, moderation, hidden
    $allowed_statuses = ['all', 'published', 'draft', 'moderation', 'hidden'];
    if (!in_array($status_filter, $allowed_statuses)) {
        $status_filter = 'all';
    }
    
    $per_page = 12;
    $current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    $connect->query("SET SESSION group_concat_max_len = 10000");
    
    $where = ["r.author_id = ?", "r.deleted_at IS NULL"];
    $params = [$_SESSION['user_id']];
    
    if ($status_filter !== 'all') {
        $where[] = "r.status = ?";
        $params[] = $status_filter;
    }
    
    $where_sql = implode(" AND ", $where);
    
    $count_sql = "SELECT COUNT(*) FROM recipes r WHERE $where_sql";
    $stmt = $connect->prepare($count_sql);
    $stmt->execute($params);
    $total_recipes = (int)$stmt->fetchColumn();
    $total_pages = max(1, ceil($total_recipes / $per_page));
    
    $stmt = $connect->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = 'moderation' THEN 1 ELSE 0 END) as moderation,
            SUM(CASE WHEN status = 'hidden' THEN 1 ELSE 0 END) as hidden
        FROM recipes 
        WHERE author_id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $sql = "
        SELECT 
            r.id, r.title, r.description, r.cover_url, 
            r.calories, r.cooking_time, r.status, r.created_at, r.updated_at,
            GROUP_CONCAT(CONCAT(ri.name, ' ', ri.amount) ORDER BY ri.order_index ASC SEPARATOR ', ') AS ingredients,
            COUNT(DISTINCT uf.user_id) AS likes_count,
            COUNT(DISTINCT c.id) AS comments_count
        FROM recipes r
        LEFT JOIN recipe_ingredients ri ON r.id = ri.recipe_id
        LEFT JOIN user_favorites uf ON r.id = uf.recipe_id
        LEFT JOIN comments c ON r.id = c.recipe_id
        WHERE $where_sql
        GROUP BY r.id
        ORDER BY r.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";
    $stmt = $connect->prepare($sql);
    $stmt->execute($params);
    $my_recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    function getStatusInfo($status) {
        return match ($status) {
            'published'  => ['text' => 'Опубликован', 'class' => 'status-published'],
            'draft'      => ['text' => 'Черновик', 'class' => 'status-draft'],
            'moderation' => ['text' => 'На модерации', 'class' => 'status-moderation'],
            'hidden'     => ['text' => 'Скрыт', 'class' => 'status-hidden'],
            default      => ['text' => 'Неизвестно', 'class' => 'status-unknown']
        };
    }
    
    function buildMyRecipesUrl($page_num, $status) {
        $params = ['page' => 'my_recipes', 'p' => $page_num];
        if ($status !== 'all') {
            $params['status'] = $status;
        }
        return '?' . http_build_query($params);
    }
?>

<div class="container">
    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <a href="?page=profile">Личный кабинет</a>
        <span>Мои рецепты</span>
    </nav>

    <?php include __DIR__ . '/../../includes/profile_header.php'; ?>

    <div class="profile_container">
        <?php include __DIR__ . '/../../includes/profile_sidebar.php'; ?>

        <div class="profile_content">
            <div class="my_recipes_header">
                <h2 class="section_title">Авторские рецепты</h2>
                <a href="?page=create_recipe" class="btn v1">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Создать рецепт</span>
                </a>
            </div>

            <div class="recipes_stats">
                <div class="stat_card <?= $status_filter === 'all' ? 'active' : '' ?>">
                    <a href="?page=my_recipes">
                        <span class="stat_number"><?= (int)$stats['total'] ?></span>
                        <span class="stat_text">Всего</span>
                    </a>
                </div>
                <div class="stat_card <?= $status_filter === 'published' ? 'active' : '' ?>">
                    <a href="?page=my_recipes&status=published">
                        <span class="stat_number"><?= (int)$stats['published'] ?></span>
                        <span class="stat_text">Опубликовано</span>
                    </a>
                </div>
                <div class="stat_card <?= $status_filter === 'draft' ? 'active' : '' ?>">
                    <a href="?page=my_recipes&status=draft">
                        <span class="stat_number"><?= (int)$stats['draft'] ?></span>
                        <span class="stat_text">Черновики</span>
                    </a>
                </div>
                <div class="stat_card <?= $status_filter === 'moderation' ? 'active' : '' ?>">
                    <a href="?page=my_recipes&status=moderation">
                        <span class="stat_number"><?= (int)$stats['moderation'] ?></span>
                        <span class="stat_text">На модерации</span>
                    </a>
                </div>
                <div class="stat_card <?= $status_filter === 'hidden' ? 'active' : '' ?>">
                    <a href="?page=my_recipes&status=hidden">
                        <span class="stat_number"><?= (int)$stats['hidden'] ?></span>
                        <span class="stat_text">Скрытые</span>
                    </a>
                </div>
            </div>

            <?php if (!empty($my_recipes)): ?>
            <div class="recipes_grid my_recipes_grid">
                <?php foreach ($my_recipes as $recipe): 
                        $status_info = getStatusInfo($recipe['status']);
                    ?>
                <div class="recipe_card recipe_card_owner" data-recipe-id="<?= $recipe['id'] ?>">
                    <span class="recipe_status_badge <?= $status_info['class'] ?>">
                        <?= $status_info['text'] ?>
                    </span>

                    <a href="?page=recipe&id=<?= $recipe['id'] ?>" class="recipe_card_link">
                        <div class="recipe_card_image">
                            <img src="<?= htmlspecialchars($recipe['cover_url'] ?: 'asset/img/placeholder.png') ?>"
                                alt="<?= htmlspecialchars($recipe['title']) ?>" loading="lazy">
                        </div>
                        <div class="recipe_card_body">
                            <div class="recipe_card_meta">
                                <?php if ($recipe['calories']): ?>
                                <span class="meta_item">
                                    <img src="asset/img/icon_kkal.svg" alt="kkal">
                                    <?= (int)$recipe['calories'] ?> ккал
                                </span>
                                <?php endif; ?>
                                <?php if ($recipe['cooking_time']): ?>
                                <span class="meta_item">
                                    <img src="asset/img/icon_clock.svg" alt="time">
                                    <?= (int)$recipe['cooking_time'] ?> мин
                                </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="recipe_card_title"><?= htmlspecialchars($recipe['title']) ?></h3>
                            <div class="recipe_card_footer">
                                <p class="recipe_card_ingredients">
                                    <?= htmlspecialchars(mb_strimwidth($recipe['ingredients'] ?? 'Состав не указан', 0, 50, '...')) ?>
                                </p>
                                <div class="recipe_card_stats">
                                    <span class="mini_stat">
                                        <img src="asset/img/icon_heart.svg" alt="likes">
                                        <?= (int)$recipe['likes_count'] ?>
                                    </span>
                                    <span class="mini_stat">
                                        <img src="asset/img/icon_comment.svg" alt="comments">
                                        <?= (int)$recipe['comments_count'] ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>

                    <div class="recipe_card_admin_actions">
                        <?php if ($recipe['status'] === 'moderation'): ?>
                        <span class="moderation_hint">Рецепт на проверке</span>
                        <?php else: ?>
                        <a href="?page=edit_recipe&id=<?= $recipe['id'] ?>" class="btn btn_edit">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                            </svg>
                            <span>Редактировать</span>
                        </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn_delete_recipe" data-recipe-id="<?= $recipe['id'] ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <polyline points="3 6 5 6 21 6" />
                                <path
                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            </svg>
                            <span>Удалить</span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination mt-16">
                <?php if ($current_page > 1): ?>
                <a href="<?= buildMyRecipesUrl($current_page - 1, $status_filter) ?>" class="pagination_arrow">←</a>
                <?php else: ?>
                <span class="pagination_arrow disabled">←</span>
                <?php endif; ?>

                <div class="pagination_pages">
                    <?php 
                                $pages_to_show = [];
                                for ($i = 1; $i <= $total_pages; $i++) {
                                    if ($i == 1 || $i == $total_pages || abs($i - $current_page) <= 2) {
                                        $pages_to_show[] = $i;
                                    } elseif (end($pages_to_show) !== '...') {
                                        $pages_to_show[] = '...';
                                    }
                                }
                                foreach ($pages_to_show as $p):
                                    if ($p === '...'): 
                            ?>
                    <span class="page dots">...</span>
                    <?php else: ?>
                    <a href="<?= buildMyRecipesUrl($p, $status_filter) ?>"
                        class="page <?= $p == $current_page ? 'active' : '' ?>">
                        <?= $p ?>
                    </a>
                    <?php 
                                    endif;
                                endforeach; 
                            ?>
                </div>

                <?php if ($current_page < $total_pages): ?>
                <a href="<?= buildMyRecipesUrl($current_page + 1, $status_filter) ?>" class="pagination_arrow">→</a>
                <?php else: ?>
                <span class="pagination_arrow disabled">→</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="empty_state">
                <h3>
                    <?php if ($status_filter === 'all'): ?>
                    У вас пока нет рецептов
                    <?php else: ?>
                    Нет рецептов в этой категории
                    <?php endif; ?>
                </h3>
                <p style="color:#888; margin-bottom:24px;">
                    <?php if ($status_filter === 'all'): ?>
                    Создайте свой первый рецепт и поделитесь им с другими!
                    <?php else: ?>
                    Попробуйте выбрать другую категорию или создайте новый рецепт
                    <?php endif; ?>
                </p>
                <a href="?page=create_recipe" class="btn v1">Создать рецепт</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal-overlay" id="deleteRecipeOverlay"></div>
<div class="modal" id="deleteRecipeModal">
    <button class="modal_close" aria-label="Закрыть"></button>
    <h2 class="modal_title">Удалить рецепт?</h2>
    <p style="text-align:center; color:#666; margin-bottom:24px;">
        Рецепт "<span id="deleteRecipeTitle"></span>" будет удалён.<br>
    </p>
    <div style="display:flex; gap:12px;">
        <button type="button" class="btn v1 btn_cancel" style="flex:1; background:#f0f0f0; color:#333;">Отмена</button>
        <button type="button" class="btn v1 btn_confirm" id="confirmDeleteRecipe"
            style="flex:1; background:#e74c3c;">Удалить</button>
    </div>
</div>