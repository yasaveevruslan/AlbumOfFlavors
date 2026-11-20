<?php
    global $connect;

    $slug = trim($_GET['slug'] ?? '');
    if ($slug === '') {
        echo "<div class='container'><h1>Категория не указана</h1></div>";
        return;
    }

    $stmt = $connect->prepare("SELECT id, name, slug, description, image_url FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        echo "<div class='container'><h1>Категория не найдена</h1></div>";
        return;
    }

    $category_id = $category['id'];

    $per_page = 12;
    $current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $offset = ($current_page - 1) * $per_page;

    $connect->query("SET SESSION group_concat_max_len = 10000");

    $where = [
        "rc.category_id = ?",
        "r.status = 'published'",
        "r.deleted_at IS NULL"
    ];
    $params = [$category_id];

    if (!empty($_GET['time'])) {
        $time_filters = (array)$_GET['time'];
        $time_conditions = [];
        foreach ($time_filters as $t) {
            switch ($t) {
                case '15':  $time_conditions[] = "r.cooking_time <= 15"; break;
                case '30':  $time_conditions[] = "r.cooking_time BETWEEN 16 AND 30"; break;
                case '60':  $time_conditions[] = "r.cooking_time BETWEEN 31 AND 60"; break;
                case '120': $time_conditions[] = "r.cooking_time > 60"; break;
            }
        }
        if (!empty($time_conditions)) {
            $where[] = "(" . implode(" OR ", $time_conditions) . ")";
        }
    }

    if (!empty($_GET['calories'])) {
        $cal_filters = (array)$_GET['calories'];
        $cal_conditions = [];
        foreach ($cal_filters as $c) {
            switch ($c) {
                case 'low':    $cal_conditions[] = "r.calories <= 200"; break;
                case 'medium': $cal_conditions[] = "r.calories BETWEEN 201 AND 400"; break;
                case 'high':   $cal_conditions[] = "r.calories > 400"; break;
            }
        }
        if (!empty($cal_conditions)) {
            $where[] = "(" . implode(" OR ", $cal_conditions) . ")";
        }
    }


    $where_sql = implode(" AND ", $where);
    $sort = $_GET['sort'] ?? 'newest';
    $order_sql = match ($sort) {
        'time'     => "r.cooking_time ASC",
        'calories' => "r.calories ASC",
        default    => "r.created_at DESC"
    };

    $count_sql = "SELECT COUNT(DISTINCT r.id) 
                  FROM recipes r
                  INNER JOIN recipe_categories rc ON r.id = rc.recipe_id
                  WHERE $where_sql";
    $stmt = $connect->prepare($count_sql);
    $stmt->execute($params);
    $total_recipes = (int)$stmt->fetchColumn();
    $total_pages = max(1, ceil($total_recipes / $per_page));

    $sql = "SELECT 
                r.id, r.title, r.description, r.cover_url, 
                r.calories, r.cooking_time, r.servings,
                r.created_at,
                u.id AS author_id, u.username AS author_name, u.avatar_url AS author_avatar,
                GROUP_CONCAT(CONCAT(ri.name, ' ', ri.amount) ORDER BY ri.order_index ASC SEPARATOR ', ') AS ingredients,
                COUNT(DISTINCT uf.user_id) AS likes_count
            FROM recipes r
            INNER JOIN recipe_categories rc ON r.id = rc.recipe_id
            LEFT JOIN users u ON r.author_id = u.id
            LEFT JOIN recipe_ingredients ri ON r.id = ri.recipe_id
            LEFT JOIN user_favorites uf ON r.id = uf.recipe_id
            WHERE $where_sql
            GROUP BY r.id
            ORDER BY $order_sql
            LIMIT $per_page OFFSET $offset";
            
    $stmt = $connect->prepare($sql);
    $stmt->execute($params);
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $liked_recipe_ids = [];
    if (isset($_SESSION['user_id'])) {
        $stmt_likes = $connect->prepare("SELECT recipe_id FROM user_favorites WHERE user_id = ?");
        $stmt_likes->execute([$_SESSION['user_id']]);
        $liked_recipe_ids = $stmt_likes->fetchAll(PDO::FETCH_COLUMN);
    }
    function buildPageUrl($page_num, $get_params, $slug) {
        $get_params['p'] = $page_num;
        $get_params['page'] = 'category';
        $get_params['slug'] = $slug;
        unset($get_params['id']); 
        return '?' . http_build_query($get_params);
    }
?>

<div class="container">
    <div class="category_header">
        <div class="category_title_block">
            <nav class="breadcrumbs">
                <a href="?page=main">Главная</a>
                <a href="?page=categories">Каталог рецептов</a>
                <span><?= htmlspecialchars($category['name']) ?></span>
            </nav>
            <h2><?= htmlspecialchars($category['name']) ?></h2>
            <?php if (!empty($category['description'])): ?>
            <p class="category_description">
                <?= htmlspecialchars($category['description']) ?>
            </p>
            <?php endif; ?>
        </div>
        <button class="action-btn" id="openFiltersBtn">Фильтры</button>
    </div>

    <div class="recipes_grid">
        <?php if (!empty($recipes)): ?>
        <?php foreach ($recipes as $recipe): ?>
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
        <?php else: ?>
        <p style="grid-column: 1/-1; text-align: center; padding: 40px 0;">
            В этой категории пока нет опубликованных рецептов.
        </p>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($current_page > 1): ?>
        <a href="<?= buildPageUrl($current_page - 1, $_GET, $slug) ?>" class="pagination_arrow">←</a>
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
            <a href="<?= buildPageUrl($p, $_GET, $slug) ?>" class="page <?= $p == $current_page ? 'active' : '' ?>">
                <?= $p ?>
            </a>
            <?php 
                        endif;
                    endforeach; 
                ?>
        </div>

        <?php if ($current_page < $total_pages): ?>
        <a href="<?= buildPageUrl($current_page + 1, $_GET, $slug) ?>" class="pagination_arrow">→</a>
        <?php else: ?>
        <span class="pagination_arrow disabled">→</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="filter-overlay" id="filterOverlay"></div>
<aside class="filter_panel" id="filterPanel">
    <div class="filter_header">
        <h3 class="filter_title">Фильтры</h3>
        <button class="filter_close" id="closeFiltersBtn" aria-label="Закрыть">
            <img src="asset/img/icon_close.svg" alt="close">
        </button>
    </div>

    <form method="GET" action="" id="filterForm">
        <input type="hidden" name="page" value="category">
        <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">

        <div class="filter_body">
            <div class="filter_group">
                <h4 class="filter_group_title">Время приготовления</h4>
                <div class="filter_options">
                    <?php 
                        $current_time = (array)($_GET['time'] ?? []);
                        foreach (['15' => 'До 15 минут', '30' => '15-30 минут', '60' => '30-60 минут', '120' => 'Более 1 часа'] as $val => $label):
                    ?>
                    <label class="filter_checkbox">
                        <input type="checkbox" name="time[]" value="<?= $val ?>"
                            <?= in_array($val, $current_time) ? 'checked' : '' ?>>
                        <span><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter_group">
                <h4 class="filter_group_title">Калорийность</h4>
                <div class="filter_options">
                    <?php 
                        $current_cal = (array)($_GET['calories'] ?? []);
                        foreach (['low' => 'До 200 ккал', 'medium' => '200-400 ккал', 'high' => 'Более 400 ккал'] as $val => $label):
                    ?>
                    <label class="filter_checkbox">
                        <input type="checkbox" name="calories[]" value="<?= $val ?>"
                            <?= in_array($val, $current_cal) ? 'checked' : '' ?>>
                        <span><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter_group">
                <h4 class="filter_group_title">Сортировка</h4>
                <select class="filter_select" name="sort">
                    <?php 
                        $current_sort = $_GET['sort'] ?? 'newest';
                        foreach (['newest' => 'Сначала новые', 'time' => 'По времени приготовления', 'calories' => 'По калорийности'] as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= $current_sort === $val ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="filter_footer">
            <button type="button" class="btn_reset" id="resetFiltersBtn">Сбросить</button>
            <button type="submit" class="btn v1">Применить</button>
        </div>
    </form>
</aside>