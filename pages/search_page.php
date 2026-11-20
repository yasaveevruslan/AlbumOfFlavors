<?php
    global $connect;

    $query = trim($_GET['q'] ?? '');
    $times = isset($_GET['time']) ? (array)$_GET['time'] : [];
    $calories = isset($_GET['calories']) ? (array)$_GET['calories'] : [];
    $sort = $_GET['sort'] ?? 'relevance';
    
    $per_page = 12;
    $current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $offset = ($current_page - 1) * $per_page;

    $where = ["r.status = 'published'", "r.deleted_at IS NULL"];
    $params = [];

    if ($query !== '') {
        $searchTerm = '%' . $query . '%';
        $where[] = "(r.title LIKE ? OR r.description LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if (!empty($times)) {
        $time_conds = [];
        foreach ($times as $t) {
            if ($t == '15') $time_conds[] = "r.cooking_time <= 15";
            elseif ($t == '30') $time_conds[] = "r.cooking_time BETWEEN 16 AND 30";
            elseif ($t == '60') $time_conds[] = "r.cooking_time BETWEEN 31 AND 60";
        }
        if (!empty($time_conds)) $where[] = "(" . implode(" OR ", $time_conds) . ")";
    }

    if (!empty($calories)) {
        $cal_conds = [];
        foreach ($calories as $c) {
            if ($c == 'low') $cal_conds[] = "r.calories <= 200";
            elseif ($c == 'medium') $cal_conds[] = "r.calories BETWEEN 201 AND 400";
            elseif ($c == 'high') $cal_conds[] = "r.calories > 400";
        }
        if (!empty($cal_conds)) $where[] = "(" . implode(" OR ", $cal_conds) . ")";
    }

    $where_sql = implode(" AND ", $where);

    $order_sql = match ($sort) {
        'newest' => "r.created_at DESC",
        'popular' => "r.created_at DESC",
        default => "r.created_at DESC"
    };

    $count_sql = "SELECT COUNT(DISTINCT r.id) FROM recipes r WHERE $where_sql";
    $stmt = $connect->prepare($count_sql);
    $stmt->execute($params);
    $total_results = (int)$stmt->fetchColumn();
    $total_pages = max(1, ceil($total_results / $per_page));

    $sql = " SELECT r.id, r.title, r.cover_url, r.cooking_time, r.calories,
      GROUP_CONCAT(CONCAT(ri.name, ' ', ri.amount) ORDER BY ri.order_index ASC SEPARATOR ', ') AS ingredients,
      COUNT(DISTINCT uf.user_id) AS likes_count
      FROM recipes r
      LEFT JOIN recipe_ingredients ri ON r.id = ri.recipe_id
      LEFT JOIN user_favorites uf ON r.id = uf.recipe_id
      WHERE $where_sql
      GROUP BY r.id
      ORDER BY $order_sql
      LIMIT $per_page OFFSET $offset
    ";
    $stmt = $connect->prepare($sql);
    $stmt->execute($params);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt_cats = $connect->query("SELECT id, name, slug, image_url FROM categories ORDER BY name ASC LIMIT 8");
    $collections = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <section class="search_section">
        <h3>Поиск рецептов</h3>

        <form method="GET" action="index.php" class="search_wrapper" id="searchForm">
            <input type="hidden" name="page" value="search_page">

            <div class="search_input">
                <input type="text" name="q" value="<?= htmlspecialchars($query) ?>"
                    placeholder="Расширенный поиск по содержимому (например: курица, суп...)">
            </div>
            <div class="search_actions">
                <button type="button" class="action-btn" data-panel="collections">Подборки</button>
                <button type="button" class="action-btn" data-panel="filters">Фильтры</button>
            </div>

            <div class="history_search" id="historyBlock" style="display: none;">
                <p class="history_title">История поиска:</p>
                <div class="history_tags" id="historyTags"></div>
                <button type="button" class="clear_history" id="clearHistoryBtn">Очистить историю поиска</button>
            </div>
        </form>
    </section>

    <section class="search_results_section">
        <p class="results_count">Найдено рецептов: <span style="font-weight:500"><?= $total_results ?></span></p>

        <?php if (!empty($search_results)): ?>
        <div class="recipes_grid">
            <?php foreach ($search_results as $recipe): ?>
            <a class="recipe_card" href="?page=recipe&id=<?= $recipe['id'] ?>">
                <div class="recipe_card_image">
                    <img src="<?= htmlspecialchars($recipe['cover_url'] ?: 'asset/img/placeholder.png') ?>"
                        alt="<?= htmlspecialchars($recipe['title']) ?>" loading="lazy">
                </div>
                <div class="recipe_card_body">
                    <div class="recipe_card_meta">
                        <span class="meta_item"><img src="asset/img/icon_kkal.svg"><?= (int)$recipe['calories'] ?>
                            ккал</span>
                        <span class="meta_item"><img src="asset/img/icon_clock.svg"><?= (int)$recipe['cooking_time'] ?>
                            мин</span>
                    </div>
                    <h3 class="recipe_card_title"><?= htmlspecialchars($recipe['title']) ?></h3>
                    <div class="recipe_card_footer">
                        <p class="recipe_card_ingredients">
                            <?= htmlspecialchars(mb_strimwidth($recipe['ingredients'] ?? 'Состав не указан', 0, 50, '...')) ?>
                        </p>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination mt-16">
            <?php if ($current_page > 1): ?>
            <a href="?page=search_page&q=<?= urlencode($query) ?>&p=<?= $current_page - 1 ?>"
                class="pagination_arrow">←</a>
            <?php else: ?>
            <span class="pagination_arrow disabled">←</span>
            <?php endif; ?>

            <div class="pagination_pages">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=search_page&q=<?= urlencode($query) ?>&p=<?= $i ?>"
                    class="page <?= $i == $current_page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>

            <?php if ($current_page < $total_pages): ?>
            <a href="?page=search_page&q=<?= urlencode($query) ?>&p=<?= $current_page + 1 ?>"
                class="pagination_arrow">→</a>
            <?php else: ?>
            <span class="pagination_arrow disabled">→</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php elseif ($query !== '' || !empty($times) || !empty($calories)): ?>
        <div class="empty_state">
            <h3>Ничего не найдено</h3>
            <p>Попробуйте изменить запрос или сбросить фильтры</p>
            <a href="?page=search_page" class="btn v1">Сбросить все фильтры</a>
        </div>
        <?php else: ?>
        <div class="empty_state">
            <p>Введите запрос, чтобы найти рецепты</p>
        </div>
        <?php endif; ?>
    </section>
</div>

<div class="panel-overlay" id="panelOverlay"></div>

<aside class="side-panel" id="panel-collections">
    <div class="panel-header">
        <h3 class="panel-title">Подборки рецептов</h3>
        <button class="panel-close" aria-label="Закрыть"><img src="asset/img/icon_close.svg" alt="Закрыть"></button>
    </div>
    <div class="panel-body">
        <div class="collections-grid">
            <?php foreach ($collections as $cat): ?>
            <a href="?page=category&slug=<?= urlencode($cat['slug']) ?>" class="collection-card">
                <div class="collection-img"
                    style="background-image: url('<?= htmlspecialchars($cat['image_url'] ?: 'asset/img/placeholder.png') ?>');">
                </div>
                <div class="collection-info">
                    <span class="collection-name"><?= htmlspecialchars($cat['name']) ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</aside>

<aside class="side-panel" id="panel-filters">
    <div class="panel-header">
        <h3 class="panel-title">Фильтры поиска</h3>
        <button class="panel-close" aria-label="Закрыть"><img src="asset/img/icon_close.svg" alt="Закрыть"></button>
    </div>
    <form id="filterFormContent">
        <div class="panel-body">
            <div class="filter-group">
                <h4 class="filter-group-title">Время приготовления</h4>
                <div class="filter-options">
                    <label class="filter-checkbox"><input type="checkbox" name="time[]" value="15"
                            <?= in_array('15', $times) ? 'checked' : '' ?>><span>До 15 минут</span></label>
                    <label class="filter-checkbox"><input type="checkbox" name="time[]" value="30"
                            <?= in_array('30', $times) ? 'checked' : '' ?>><span>15-30 минут</span></label>
                    <label class="filter-checkbox"><input type="checkbox" name="time[]" value="60"
                            <?= in_array('60', $times) ? 'checked' : '' ?>><span>30-60 минут</span></label>
                </div>
            </div>
            <div class="filter-group">
                <h4 class="filter-group-title">Калорийность</h4>
                <div class="filter-options">
                    <label class="filter-checkbox"><input type="checkbox" name="calories[]" value="low"
                            <?= in_array('low', $calories) ? 'checked' : '' ?>><span>До 200 ккал</span></label>
                    <label class="filter-checkbox"><input type="checkbox" name="calories[]" value="medium"
                            <?= in_array('medium', $calories) ? 'checked' : '' ?>><span>200-400 ккал</span></label>
                    <label class="filter-checkbox"><input type="checkbox" name="calories[]" value="high"
                            <?= in_array('high', $calories) ? 'checked' : '' ?>><span>Более 400 ккал</span></label>
                </div>
            </div>
            <div class="filter-group">
                <h4 class="filter-group-title">Сортировка</h4>
                <select class="filter-select" name="sort">
                    <option value="relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>По релевантности</option>
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Сначала новые</option>
                    <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>По популярности</option>
                </select>
            </div>
        </div>
        <div class="panel-footer">
            <button type="button" class="btn-reset" id="resetFilters">Сбросить</button>
            <button type="button" class="btn v1" id="applyFilters">Применить</button>
        </div>
    </form>
</aside>