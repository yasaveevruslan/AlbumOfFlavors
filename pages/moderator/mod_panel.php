<?php
    global $connect;
    
    $stats = [];
    
    $stmt = $connect->query("SELECT COUNT(*) FROM recipes WHERE deleted_at IS NULL");
    $stats['recipes_total'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM recipes WHERE status = 'moderation' AND deleted_at IS NULL");
    $stats['recipes_moderation'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(DISTINCT name) FROM recipe_ingredients");
    $stats['ingredients'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM categories");
    $stats['categories'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM newsletter_subscribers");
    $stats['newsletter_subscribers'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("
        SELECT COUNT(*) 
        FROM comments c
        INNER JOIN recipes r ON c.recipe_id = r.id
        WHERE r.deleted_at IS NULL
    ");
    $stats['comments_total'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("
        SELECT COUNT(*) 
        FROM comments c
        INNER JOIN recipes r ON c.recipe_id = r.id
        WHERE r.deleted_at IS NULL 
          AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stats['comments_recent'] = (int)$stmt->fetchColumn();
?>

<div class="container">
    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <span>Панель модератора</span>
    </nav>
    <div class="admin_page_header">
        <h2 class="section_title">Панель модератора</h2>
        <p class="admin_subtitle">Управление контентом сайта и проверка материалов пользователей</p>
    </div>
    <div class="admin_box">
        <div class="admin_body">

            <div class="main_categories">
                <h5 class="admin_main_categories_title">Рецепты и справочники</h5>
                <div class="main_categories_list scroll_mobile_box" style="flex-wrap:wrap;">

                    <a href="?page=mod_list&entity=recipes" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Рецепты</h6>
                            <p>Все рецепты сайта</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['recipes_total'] ?></span>
                            <span class="stat_label">всего</span>
                        </div>
                    </a>
                    <a href="?page=mod_list&entity=ingredients" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Ингредиенты</h6>
                            <p>Справочник ингредиентов</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['ingredients'] ?></span>
                            <span class="stat_label">всего</span>
                        </div>
                    </a>
                    <a href="?page=mod_list&entity=categories" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Категории</h6>
                            <p>Категории для рецептов</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['categories'] ?></span>
                            <span class="stat_label">всего</span>
                        </div>
                    </a>
                </div>
            </div>

            <div class="main_categories mt-24">
                <h5 class="admin_main_categories_title">
                    Модерация
                    <?php if ($stats['recipes_moderation'] > 0): ?>
                    <span class="admin_badge"><?= $stats['recipes_moderation'] ?></span>
                    <?php endif; ?>
                </h5>
                <div class="main_categories_list scroll_mobile_box" style="flex-wrap:wrap;">
                    <a href="?page=mod_list&entity=recipes&status=moderation"
                        class="main_category_item admin_card_link <?= $stats['recipes_moderation'] > 0 ? 'has_alert' : '' ?>">
                        <div class="main_category_body">
                            <h6>На модерации</h6>
                            <p>Рецепты, ожидающие проверки</p>
                        </div>
                        <div class="admin_card_stat <?= $stats['recipes_moderation'] > 0 ? 'alert' : '' ?>">
                            <span class="stat_value"><?= $stats['recipes_moderation'] ?></span>
                            <span class="stat_label">ожидают</span>
                        </div>
                    </a>

                    <a href="?page=mod_list&entity=comments" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Комментарии</h6>
                            <p>Последние комментарии</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['comments_recent'] ?></span>
                            <span class="stat_label">за 7 дней</span>
                        </div>
                    </a>
                    <a href="?page=mod_list&entity=newsletter_subscribers" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Подписчики</h6>
                            <p>Подписчики на email-рассылку</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['newsletter_subscribers'] ?></span>
                            <span class="stat_label">всего</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>