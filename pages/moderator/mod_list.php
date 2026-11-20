<?php
    global $connect;
    
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && isset($_GET['entity'])) {
        $del_entity = $_GET['entity'];
        $del_id = $_GET['id'];
        
        $deletable_entities = ['categories', 'ingredients', 'comments', 'newsletter_subscribers','recipes'];
        
        if (in_array($del_entity, $deletable_entities)) {
            try {
                $connect->beginTransaction();

                if ($del_entity === 'recipes') {
                    $stmt = $connect->prepare("
                        UPDATE recipes
                        SET deleted_at = NOW()
                        WHERE id = ?
                        AND deleted_at IS NULL
                    ");

                    $stmt->execute([$del_id]);

                    if ($stmt->rowCount() > 0) {
                        $_SESSION['success_message'] = 'Рецепт успешно удалён';
                    } else {
                        $_SESSION['error_message'] = 'Рецепт не найден или уже удалён';
                    }

                } elseif ($del_entity === 'categories') {

                    $stmt = $connect->prepare("
                        DELETE FROM recipe_categories
                        WHERE category_id = ?
                    ");
                    $stmt->execute([$del_id]);

                    $stmt = $connect->prepare("
                        DELETE FROM categories
                        WHERE id = ?
                    ");
                    $stmt->execute([$del_id]);

                } else {

                    $table_map = [
                        'ingredients' => 'recipe_ingredients',
                        'comments' => 'comments',
                        'newsletter_subscribers' => 'newsletter_subscribers'
                    ];

                    $table = $table_map[$del_entity];

                    $stmt = $connect->prepare("
                        DELETE FROM `$table`
                        WHERE id = ?
                    ");

                    $stmt->execute([$del_id]);
                }

                $connect->commit();

                if (!isset($_SESSION['success_message'])) {
                    $_SESSION['success_message'] = 'Запись успешно удалена';
                }

            } catch (Exception $e) {

                if ($connect->inTransaction()) {
                    $connect->rollBack();
                }

                error_log('Ошибка удаления: ' . $e->getMessage());

                $_SESSION['error_message'] = 'Ошибка при удалении записи';
            }

        }
        
        $redirect_url = '?page=mod_list&entity=' . urlencode($del_entity);
        if (isset($_GET['q']) && $_GET['q'] !== '') {
            $redirect_url .= '&q=' . urlencode($_GET['q']);
        }
        if ($del_entity === 'recipes' && isset($_GET['status'])) {
            $redirect_url .= '&status=' . urlencode($_GET['status']);
        }
        
        header("Location: $redirect_url");
        exit;
    }

    $entity = $_GET['entity'] ?? '';
    $status_filter = $_GET['status'] ?? 'all';
    $search_query = trim($_GET['q'] ?? '');
    $current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $per_page = 20;
    $offset = ($current_page - 1) * $per_page;
    
    $allowed_entities = ['recipes', 'ingredients', 'categories', 'comments', 'newsletter_subscribers'];
    if (!in_array($entity, $allowed_entities)) {
        $entity = 'recipes';
    }
    
    $entity_info = [
        'recipes'                => ['title' => 'Рецепты', 'add_text' => 'Добавить рецепт', 'add_url' => '?page=mod_form&entity=recipes'],
        'categories'             => ['title' => 'Категории', 'add_text' => 'Добавить категорию', 'add_url' => '?page=mod_form&entity=categories'],
        'ingredients'            => ['title' => 'Справочник ингредиентов', 'add_text' => '', 'add_url' => ''],
        'comments'               => ['title' => 'Комментарии', 'add_text' => '', 'add_url' => ''],
        'newsletter_subscribers' => ['title' => 'Подписчики рассылки', 'add_text' => '', 'add_url' => ''],
    ];
    
    $info = $entity_info[$entity];
    $items = [];
    $total = 0;
    $columns = [];
    
    switch ($entity) {
        case 'recipes':
            $where = ["r.deleted_at IS NULL"];
            $params = [];
            
            if ($status_filter !== 'all' && in_array($status_filter, ['published', 'draft', 'moderation', 'hidden'])) {
                $where[] = "r.status = ?";
                $params[] = $status_filter;
            }
            if ($search_query !== '') {
                $where[] = "r.title LIKE ?";
                $params[] = "%$search_query%";
            }
            
            $where_sql = implode(" AND ", $where);
            $stmt = $connect->prepare("SELECT COUNT(*) FROM recipes r WHERE $where_sql");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
            
            $sql = "SELECT r.id, r.title, r.status, r.created_at, r.cooking_time, r.calories,
                           u.username AS author_name, COUNT(DISTINCT uf.user_id) AS likes_count
                    FROM recipes r
                    LEFT JOIN users u ON r.author_id = u.id
                    LEFT JOIN user_favorites uf ON r.id = uf.recipe_id
                    WHERE $where_sql
                    GROUP BY r.id
                    ORDER BY r.created_at DESC
                    LIMIT $per_page OFFSET $offset";
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $columns = [
                ['key' => 'title', 'label' => 'Название'],
                ['key' => 'author_name', 'label' => 'Автор'],
                ['key' => 'status', 'label' => 'Статус'],
                ['key' => 'likes_count', 'label' => 'Лайки'],
                ['key' => 'created_at', 'label' => 'Дата'],
            ];
            break;
            
        case 'categories':
            $where = [];
            $params = [];
            if ($search_query !== '') {
                $where[] = "c.name LIKE ?";
                $params[] = "%$search_query%";
            }
            $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
            
            $stmt = $connect->prepare("SELECT COUNT(*) FROM categories c $where_sql");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
            
            $stmt = $connect->prepare("SELECT c.id, c.name, c.slug, c.description, COUNT(rc.recipe_id) AS recipes_count
                                       FROM categories c
                                       LEFT JOIN recipe_categories rc ON c.id = rc.category_id
                                       $where_sql GROUP BY c.id ORDER BY c.name ASC LIMIT $per_page OFFSET $offset");
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $columns = [
                ['key' => 'name', 'label' => 'Название'],
                ['key' => 'slug', 'label' => 'Slug'],
                ['key' => 'recipes_count', 'label' => 'Рецептов'],
            ];
            break;
            
        case 'comments':
            $where = ["r.deleted_at IS NULL"];
            $params = [];
            if ($search_query !== '') {
                $where[] = "c.text LIKE ?";
                $params[] = "%$search_query%";
            }
            $where_sql = "WHERE " . implode(" AND ", $where);
            
            $stmt = $connect->prepare("SELECT COUNT(*) FROM comments c INNER JOIN recipes r ON c.recipe_id = r.id $where_sql");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
            
            $stmt = $connect->prepare("SELECT c.id, c.text, c.created_at, c.recipe_id, u.username AS author_name, r.title AS recipe_title
                                       FROM comments c
                                       INNER JOIN recipes r ON c.recipe_id = r.id
                                       LEFT JOIN users u ON c.user_id = u.id
                                       $where_sql
                                       ORDER BY c.created_at DESC
                                       LIMIT $per_page OFFSET $offset");
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $columns = [
                ['key' => 'text', 'label' => 'Текст'],
                ['key' => 'author_name', 'label' => 'Автор'],
                ['key' => 'recipe_title', 'label' => 'Рецепт'],
                ['key' => 'created_at', 'label' => 'Дата'],
            ];
            break;
            
        case 'ingredients':
            $where = [];
            $params = [];
            if ($search_query !== '') {
                $where[] = "name LIKE ?";
                $params[] = "%$search_query%";
            }
            $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
            
            $stmt = $connect->prepare("SELECT COUNT(DISTINCT name) FROM recipe_ingredients $where_sql");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
            
            $stmt = $connect->prepare("SELECT MIN(id) as id, name, COUNT(*) as usage_count 
                                       FROM recipe_ingredients 
                                       $where_sql 
                                       GROUP BY name 
                                       ORDER BY usage_count DESC, name ASC 
                                       LIMIT $per_page OFFSET $offset");
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $columns = [
                ['key' => 'name', 'label' => 'Название ингредиента'],
                ['key' => 'usage_count', 'label' => 'В рецептах'],
            ];
            break;

        case 'newsletter_subscribers':
            $where = [];
            $params = [];
            if ($search_query !== '') {
                $where[] = "email LIKE ?";
                $params[] = "%$search_query%";
            }
            $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
            
            $stmt = $connect->prepare("SELECT COUNT(*) FROM newsletter_subscribers $where_sql");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
            
            $stmt = $connect->prepare("SELECT id, email, subscribed_at FROM newsletter_subscribers $where_sql ORDER BY subscribed_at DESC LIMIT $per_page OFFSET $offset");
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $columns = [
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'subscribed_at', 'label' => 'Дата подписки'],
            ];
            break;
    }
    
    $total_pages = max(1, ceil($total / $per_page));
    
    function buildAdminListUrl($params) {
        $base = [
            'page' => 'mod_list',
            'entity' => $_GET['entity'] ?? 'recipes',
            'q' => $_GET['q'] ?? ''
        ];
        if (($base['entity'] ?? '') === 'recipes') {
            $base['status'] = $_GET['status'] ?? 'all';
        }
        return '?' . http_build_query(array_merge($base, $params));
    }
    
    function getStatusBadge($status) {
        return match ($status) {
            'published'  => '<span class="status-badge status-published">Опубликован</span>',
            'draft'      => '<span class="status-badge status-draft">Черновик</span>',
            'moderation' => '<span class="status-badge status-moderation">На модерации</span>',
            'hidden'     => '<span class="status-badge status-hidden">Скрыт</span>',
            default      => '—'
        };
    }
?>

<div class="container">
    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <a href="?page=mod_panel">Панель модератора</a>
        <span><?= htmlspecialchars($info['title']) ?></span>
    </nav>

    <div class="admin_box">
        <div class="admin_body">
            <div class="admin_list_header">
                <h2 class="admin_main_categories_title">
                    <?= htmlspecialchars($info['title']) ?>
                    <span class="total_count">(<?= $total ?>)</span>
                </h2>

                <?php if ($entity === 'recipes'): ?>
                <div class="admin_status_tabs">
                    <a href="<?= buildAdminListUrl(['status' => 'all', 'p' => 1]) ?>"
                        class="status_tab <?= $status_filter === 'all' ? 'active' : '' ?>">Все</a>
                    <a href="<?= buildAdminListUrl(['status' => 'published', 'p' => 1]) ?>"
                        class="status_tab <?= $status_filter === 'published' ? 'active' : '' ?>">Опубликованные</a>
                    <a href="<?= buildAdminListUrl(['status' => 'moderation', 'p' => 1]) ?>"
                        class="status_tab <?= $status_filter === 'moderation' ? 'active' : '' ?>">На модерации</a>
                    <a href="<?= buildAdminListUrl(['status' => 'draft', 'p' => 1]) ?>"
                        class="status_tab <?= $status_filter === 'draft' ? 'active' : '' ?>">Черновики</a>
                    <a href="<?= buildAdminListUrl(['status' => 'hidden', 'p' => 1]) ?>"
                        class="status_tab <?= $status_filter === 'hidden' ? 'active' : '' ?>">Скрытые</a>
                </div>
                <?php endif; ?>
            </div>

            <div class="admin_toolbar">
                <form method="GET" class="search_input" style="flex:1; max-width:400px;">
                    <input type="hidden" name="page" value="mod_list">
                    <input type="hidden" name="entity" value="<?= htmlspecialchars($entity) ?>">
                    <?php if ($entity === 'recipes'): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                    <?php endif; ?>
                    <input type="search" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Поиск...">
                </form>

                <?php if (!empty($info['add_text'])): ?>
                <a href="<?= htmlspecialchars($info['add_url']) ?>" class="btn v1">
                    <img src="asset/img/icon_add.svg" alt="">
                    <span><?= htmlspecialchars($info['add_text']) ?></span>
                </a>
                <?php endif; ?>
            </div>

            <div class="products_table_wrapper">
                <table class="products_table">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                            <th><?= htmlspecialchars($col['label']) ?></th>
                            <?php endforeach; ?>
                            <th class="actions_col">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                        <tr data-id="<?= htmlspecialchars($item['id']) ?>">
                            <?php foreach ($columns as $col): 
                                $key = $col['key'];
                                $value = $item[$key] ?? '—';
                            ?>
                            <td>
                                <?php if ($key === 'title' && $entity === 'recipes'): ?>
                                <a href="?page=recipe&id=<?= $item['id'] ?>" class="table_link" target="_blank">
                                    <?= htmlspecialchars(mb_strimwidth($value, 0, 60, '...')) ?>
                                </a>
                                <?php elseif ($key === 'text' && $entity === 'comments'): ?>
                                <div class="comment_preview">
                                    <?= htmlspecialchars(mb_strimwidth($value, 0, 100, '...')) ?></div>
                                <?php elseif ($key === 'status'): ?>
                                <?= getStatusBadge($value) ?>
                                <?php elseif ($key === 'created_at' && !empty($value)): ?>
                                <?= date('d.m.Y H:i', strtotime($value)) ?>
                                <?php elseif ($key === 'recipe_title' && $entity === 'comments'): ?>
                                <a href="?page=recipe&id=<?= $item['recipe_id'] ?>" target="_blank" class="table_link">
                                    <?= htmlspecialchars(mb_strimwidth($value, 0, 40, '...')) ?>
                                </a>
                                <?php else: ?>
                                <?= htmlspecialchars($value) ?>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>

                            <td class="actions_col">
                                <div class="table_actions">
                                    <?php if ($entity === 'comments'): ?>
                                    <a href="?page=mod_list&entity=comments&action=delete&id=<?= $item['id'] ?>"
                                        class="action_btn" title="Удалить комментарий"
                                        onclick="return confirm('Вы уверены, что хотите удалить этот комментарий? Это действие нельзя отменить.');">
                                        <img src="/asset/img/icon_trash.svg" alt="Удалить">
                                    </a>

                                    <?php elseif (in_array($entity, ['recipes','categories', 'ingredients'])): ?>
                                    <a href="?page=mod_form&entity=<?= $entity ?>&id=<?= $item['id'] ?>"
                                        class="action_btn" title="Редактировать">
                                        <img src="/asset/img/icon_edit.svg" style="width:18px; height:18px;"
                                            alt="Редактировать">
                                    </a>
                                    <a href="?page=mod_list&entity=<?= $entity ?>&action=delete&id=<?= $item['id'] ?>"
                                        class="action_btn" title="Удалить"
                                        onclick="return confirm('Вы уверены, что хотите удалить эту запись? Это действие нельзя отменить.');">
                                        <img src="/asset/img/icon_trash.svg" alt="Удалить">
                                    </a>

                                    <?php elseif ($entity === 'newsletter_subscribers'): ?>
                                    <a href="?page=mod_list&entity=newsletter_subscribers&action=delete&id=<?= $item['id'] ?>"
                                        class="action_btn" title="Отписать"
                                        onclick="return confirm('Отписать этого пользователя от рассылки?');">
                                        <img src="/asset/img/icon_trash.svg" alt="Отписать">
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="<?= count($columns) + 1 ?>"
                                style="text-align:center; padding:40px; color:#888;">
                                Ничего не найдено
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination mt-16">
        <?php if ($current_page > 1): ?>
        <a href="<?= buildAdminListUrl(['p' => $current_page - 1]) ?>" class="pagination_arrow">←</a>
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
            <a href="<?= buildAdminListUrl(['p' => $p]) ?>"
                class="page <?= $p == $current_page ? 'active' : '' ?>"><?= $p ?></a>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
        <?php if ($current_page < $total_pages): ?>
        <a href="<?= buildAdminListUrl(['p' => $current_page + 1]) ?>" class="pagination_arrow">→</a>
        <?php else: ?>
        <span class="pagination_arrow disabled">→</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>