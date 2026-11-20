<?php
    global $connect;
    
    $stats = [];
    
    $stmt = $connect->query("SELECT COUNT(*) FROM users");
    $stats['users'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stats['users_admin'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM users WHERE role = 'moderator'");
    $stats['users_moderator'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM subscriptions");
    $stats['subscriptions'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM user_favorites");
    $stats['favorites'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM recipes WHERE deleted_at IS NULL");
    $stats['recipes'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM recipes WHERE status = 'moderation' AND deleted_at IS NULL");
    $stats['recipes_moderation'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(DISTINCT name) FROM recipe_ingredients");
    $stats['ingredients'] = (int)$stmt->fetchColumn();

    $stmt = $connect->query("SELECT COUNT(*) FROM categories");
    $stats['categories'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM comments");
    $stats['comments'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM album_templates WHERE is_active = 1");
    $stats['album_templates'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM album_pages");
    $stats['album_pages'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM assets WHERE is_active = 1");
    $stats['assets'] = (int)$stmt->fetchColumn();
    
    $stmt = $connect->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'");
    $stats['newsletter'] = (int)$stmt->fetchColumn();
?>

<div class="container">
    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <span>Панель администратора</span>
    </nav>

    <div class="admin_page_header">
        <h2 class="section_title">Панель администратора</h2>
        <p class="admin_subtitle">Полное управление всеми сущностями сайта</p>
    </div>

    <div class="admin_box">
        <div class="admin_body">

            <div class="main_categories">
                <h5 class="admin_main_categories_title">
                    Пользователи
                </h5>
                <div class="main_categories_list scroll_mobile_box" style="flex-wrap:wrap;">

                    <a href="?page=admin_list&entity=users" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Пользователи</h6>
                            <p>Все зарегистрированные</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['users'] ?></span>
                            <span class="stat_label">всего</span>
                        </div>
                    </a>

                    <a href="?page=admin_list&entity=subscriptions" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Подписки</h6>
                            <p>Связи между пользователями</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['subscriptions'] ?></span>
                            <span class="stat_label">всего</span>
                        </div>
                    </a>

                </div>
            </div>

            <div class="main_categories mt-24">
                <h5 class="admin_main_categories_title">
                    Рецепты
                </h5>
                <div class="main_categories_list scroll_mobile_box" style="flex-wrap:wrap;">

                    <a href="?page=admin_list&entity=recipes" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Рецепты</h6>
                            <p>Все рецепты сайта</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['recipes'] ?></span>
                            <span class="stat_label">всего</span>
                        </div>
                    </a>

                    <a href="?page=admin_list&entity=recipes&status=moderation"
                        class="main_category_item admin_card_link <?= $stats['recipes_moderation'] > 0 ? 'has_alert' : '' ?>">
                        <div class="main_category_body">
                            <h6>На модерации</h6>
                            <p>Ожидают проверки</p>
                        </div>
                        <div class="admin_card_stat <?= $stats['recipes_moderation'] > 0 ? 'alert' : '' ?>">
                            <span class="stat_value"><?= $stats['recipes_moderation'] ?></span>
                            <span class="stat_label">ожидают</span>
                        </div>
                    </a>

                    <a href="?page=admin_list&entity=ingredients" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Ингредиенты</h6>
                            <p>Справочник ингредиентов</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['ingredients'] ?></span>
                            <span class="stat_label">всего</span>
                        </div>
                    </a>

                    <a href="?page=admin_list&entity=categories" class="main_category_item admin_card_link">
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
                    Альбомы
                </h5>
                <div class="main_categories_list scroll_mobile_box" style="flex-wrap:wrap;">
                    <a href="?page=admin_list&entity=album_templates" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Шаблоны</h6>
                            <p>Доступные шаблоны</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['album_templates'] ?></span>
                            <span class="stat_label">активных</span>
                        </div>
                    </a>
                    <a href="?page=admin_list&entity=assets" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Ассеты</h6>
                            <p>Стикеры, скотч, штампы</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['assets'] ?></span>
                            <span class="stat_label">всего</span>
                        </div>
                    </a>
                </div>
            </div>

            <div class="main_categories mt-24">
                <h5 class="admin_main_categories_title">
                    Модерация и активность
                </h5>
                <div class="main_categories_list scroll_mobile_box" style="flex-wrap:wrap;">

                    <a href="?page=admin_list&entity=comments" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Комментарии</h6>
                            <p>Пользовательские комментарии</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['comments'] ?></span>
                            <span class="stat_label">всего</span>
                        </div>
                    </a>

                    <a href="?page=admin_list&entity=newsletter_subscribers" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Рассылка</h6>
                            <p>Подписчики на новости</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['newsletter'] ?></span>
                            <span class="stat_label">активных</span>
                        </div>
                    </a>

                    <a href="?page=admin_list&entity=favorites" class="main_category_item admin_card_link">
                        <div class="main_category_body">
                            <h6>Избранное</h6>
                            <p>Сохранённые рецепты</p>
                        </div>
                        <div class="admin_card_stat">
                            <span class="stat_value"><?= $stats['favorites'] ?></span>
                            <span class="stat_label">всего</span>
                        </div>
                    </a>

                </div>
            </div>

        </div>
    </div>
</div>