<?php
function getRandomRecipesByCategory($category_slug, $limit = 8) {
    global $connect;
    
    $connect->query("SET SESSION group_concat_max_len = 10000");
    $limit = (int)$limit;
    
    $sql = "SELECT r.id, r.title, r.description, r.cover_url, r.calories, r.cooking_time,  
            u.id AS author_id, u.username AS author_name, u.avatar_url AS author_avatar,
            GROUP_CONCAT(CONCAT(ri.name, ' ', ri.amount) ORDER BY ri.order_index ASC SEPARATOR ', ') AS ingredients,
            COUNT(DISTINCT uf.user_id) AS likes_count
        FROM recipes r
        INNER JOIN recipe_categories rc ON r.id = rc.recipe_id
        INNER JOIN categories c ON rc.category_id = c.id
        LEFT JOIN users u ON r.author_id = u.id
        LEFT JOIN recipe_ingredients ri ON r.id = ri.recipe_id
        LEFT JOIN user_favorites uf ON r.id = uf.recipe_id
        WHERE c.slug = ? 
          AND r.status = 'published' 
          AND r.deleted_at IS NULL
        GROUP BY r.id
        ORDER BY RAND()
        LIMIT $limit";
    
    $stmt = $connect->prepare($sql);
    $stmt->execute([$category_slug]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function renderRecipeSection($section_id, $title, $subtitle, $recipes, $liked_recipe_ids = []) {
    if (empty($recipes)) return;
?>
<section class="recipe_section" data-section="<?= $section_id ?>">
    <div class="section_header">
        <div class="section_titles">
            <h2 class="section_main_title"><?= htmlspecialchars($title) ?></h2>
            <p class="section_sub_title"><?= htmlspecialchars($subtitle) ?></p>
        </div>
    </div>

    <div class="recipes_grid">
        <?php foreach ($recipes as $recipe): 
            $is_liked = in_array($recipe['id'], $liked_recipe_ids);
            $heart_class = $is_liked ? 'active' : '';
            $heart_icon = $is_liked ? 'icon_heart-active.svg' : 'icon_heart.svg';
        ?>
        <div class="recipe_card" data-recipe-id="<?= $recipe['id'] ?>" data-url="?page=recipe&id=<?= $recipe['id'] ?>">
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
</section>
<?php
}

/**
 * Склонение слов в зависимости от числа (для русского языка)
 */
function get_plural($number, $one, $two, $five) {
    $number = (int)$number;
    $mod10 = $number % 10;
    $mod100 = $number % 100;
    
    if ($mod100 >= 11 && $mod100 <= 19) {
        return $five;
    }
    
    if ($mod10 === 1) return $one;
    if ($mod10 >= 2 && $mod10 <= 4) return $two;
    return $five;
}

/**
 * Возвращает название месяца и год на русском языке в родительном падеже.
 * 
 * @param string $date Строка с датой (например, '2026-11-16 13:40:18')
 * @return string Отформатированная строка (например, 'ноября 2026')
 */
function getRussianMonthYear($date) {
    if (empty($date)) {
        return 'неизвестно';
    }
    $months = [
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
        5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'
    ];
    
    $timestamp = strtotime($date);
    if (!$timestamp) {
        return 'неизвестно';
    }
    
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return $month . ' ' . $year;
}

/**
 * Возвращает название роли пользователя на русском языке.
 * 
 * @param string $role Роль из БД ('user', 'moderator', 'admin')
 * @return string Переведённое название роли
 */
function getUserRoleName($role) {
    return match ($role) {
        'admin'     => 'Администратор',
        'moderator' => 'Модератор',
        'user'      => 'Пользователь',
        'guest'     => 'Гость',
        default     => 'Пользователь'
    };
}
?>