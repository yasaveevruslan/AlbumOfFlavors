<?php
global $connect;

// 1. ПРОВЕРКА ДОСТУПА
if (
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header('Location: ?page=main');
    exit;
}

// 2. НАСТРОЙКИ И СПИСКИ
$allowedEntities = [
    'users',
    'recipes',
    'ingredients',
    'categories',
    'comments',
    'subscriptions',
    'favorites',
    'album_templates',
    'assets',
    'fonts',
    'newsletter_subscribers'
];

$entityInfo = [
    'users' => [
        'title' => 'Пользователи',
        'add_text' => ''
    ],
    'recipes' => [
        'title' => 'Рецепты',
        'add_text' => 'Добавить рецепт',
        'add_url' => '?page=admin_form&entity=recipes'
    ],
    'categories' => [
        'title' => 'Категории',
        'add_text' => 'Добавить категорию',
        'add_url' => '?page=admin_form&entity=categories'
    ],
    'ingredients' => [
        'title' => 'Справочник ингредиентов',
        'add_text' => ''
    ],
    'comments' => [
        'title' => 'Комментарии',
        'add_text' => ''
    ],
    'newsletter_subscribers' => [
        'title' => 'Подписчики рассылки',
        'add_text' => ''
    ],
    'subscriptions' => [
        'title' => 'Подписки',
        'add_text' => ''
    ],
    'favorites' => [
        'title' => 'Избранное',
        'add_text' => ''
    ],
    'album_templates' => [
        'title' => 'Шаблоны альбомов',
        'add_text' => 'Добавить шаблон',
        'add_url' => '?page=admin_form&entity=album_templates'
    ],
    'assets' => [
        'title' => 'Ассеты (Стикеры и др.)',
        'add_text' => 'Добавить ассет',
        'add_url' => '?page=admin_form&entity=assets'
    ],
    'fonts' => [
        'title' => 'Шрифты',
        'add_text' => 'Добавить шрифт',
        'add_url' => '?page=admin_form&entity=fonts'
    ]
];

// 3. УДАЛЕНИЕ ЗАПИСЕЙ

if (
    ($_GET['action'] ?? '') === 'delete' &&
    isset($_GET['entity'])
) {
    $deleteEntity = $_GET['entity'];
    $id = $_GET['id'] ?? '';

    try {
        if (!in_array($deleteEntity, $allowedEntities, true)) {
            throw new Exception('Недопустимый раздел.');
        }

        $connect->beginTransaction();

        switch ($deleteEntity) {

            case 'users':
                if (isset($_SESSION['user_id']) && (string)$id === (string)$_SESSION['user_id']) {
                    $_SESSION['error_message'] = 'Вы не можете удалить собственную учётную запись';

                    header("Location: ?page=admin_list&entity=users");
                    exit;
                }

                $stmt = $connect->prepare("
                    DELETE FROM users
                    WHERE id = ?
                ");

                $stmt->execute([$id]);
                break;

            case 'recipes':
                $stmt = $connect->prepare(
                    "UPDATE recipes SET deleted_at = NOW() WHERE id = ?"
                );
                $stmt->execute([$id]);
                break;

            case 'categories':
                $connect->prepare(
                    "DELETE FROM recipe_categories WHERE category_id = ?"
                )->execute([$id]);

                $connect->prepare(
                    "DELETE FROM categories WHERE id = ?"
                )->execute([$id]);
                break;

            case 'subscriptions':
                $followerId = $_GET['follower_id'] ?? '';
                $followingId = $_GET['following_id'] ?? '';

                if ($followerId === '' || $followingId === '') {
                    throw new Exception('Не указаны участники подписки.');
                }

                $stmt = $connect->prepare("
                    DELETE FROM subscriptions
                    WHERE follower_id = ? AND following_id = ?
                ");

                $stmt->execute([$followerId, $followingId]);
                break;

            case 'ingredients':
                $stmt = $connect->prepare(
                    "SELECT name FROM recipe_ingredients WHERE id = ?"
                );
                $stmt->execute([$id]);

                $ingredientName = $stmt->fetchColumn();

                if ($ingredientName !== false) {
                    $stmt = $connect->prepare(
                        "DELETE FROM recipe_ingredients WHERE name = ?"
                    );
                    $stmt->execute([$ingredientName]);
                }
                break;

            default:
                $tableMap = [
                    'comments' => 'comments',
                    'newsletter_subscribers' => 'newsletter_subscribers',
                    'assets' => 'assets',
                    'album_templates' => 'album_templates',
                    'fonts' => 'fonts',
                    'favorites' => 'user_favorites'
                ];

                if (!isset($tableMap[$deleteEntity])) {
                    throw new Exception('Удаление для этого раздела не настроено.');
                }

                if ($id === '') {
                    throw new Exception('Не указан идентификатор записи.');
                }

                $table = $tableMap[$deleteEntity];

                $stmt = $connect->prepare(
                    "DELETE FROM `$table` WHERE id = ?"
                );

                $stmt->execute([$id]);
                break;
        }

        $connect->commit();

        $_SESSION['success_message'] = 'Запись успешно удалена';

    } catch (Throwable $e) {

        if ($connect->inTransaction()) {
            $connect->rollBack();
        }

        $_SESSION['error_message'] = 'Ошибка при удалении: ' . $e->getMessage();
    }

    $redirectParams = [
        'page' => 'admin_list',
        'entity' => $deleteEntity
    ];

    if (!empty($_GET['q'])) {
        $redirectParams['q'] = $_GET['q'];
    }

    if ($deleteEntity === 'recipes' && isset($_GET['status'])) {
        $redirectParams['status'] = $_GET['status'];
    }

    header('Location: ?' . http_build_query($redirectParams));
    exit;
}


// 4. ПАРАМЕТРЫ СПИСКА

$entity = $_GET['entity'] ?? 'users';

if (!in_array($entity, $allowedEntities, true)) {
    $entity = 'users';
}

$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');

$currentPage = max(1, (int)($_GET['p'] ?? 1));

$perPage = 20;
$offset = ($currentPage - 1) * $perPage;

$info = $entityInfo[$entity];

$items = [];
$total = 0;
$columns = [];


// 5. ПОЛУЧЕНИЕ ДАННЫХ
switch ($entity) {
    // ПОЛЬЗОВАТЕЛИ
    case 'users':

        $where = '';
        $params = [];

        if ($searchQuery !== '') {
            $where = "WHERE username LIKE ? OR email LIKE ?";
            $params = ["%$searchQuery%", "%$searchQuery%"];
        }

        $stmt = $connect->prepare(
            "SELECT COUNT(*) FROM users $where"
        );
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $connect->prepare("
            SELECT id, username, email, role, created_at
            FROM users
            $where
            ORDER BY created_at DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columns = [
            ['key' => 'username', 'label' => 'Имя'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'role', 'label' => 'Роль'],
            ['key' => 'created_at', 'label' => 'Регистрация']
        ];

        break;

    // РЕЦЕПТЫ
    case 'recipes':

        $where = ['r.deleted_at IS NULL'];
        $params = [];

        if (
            $statusFilter !== 'all' &&
            in_array($statusFilter, ['published', 'draft', 'moderation', 'hidden'], true)
        ) {
            $where[] = 'r.status = ?';
            $params[] = $statusFilter;
        }

        if ($searchQuery !== '') {
            $where[] = 'r.title LIKE ?';
            $params[] = "%$searchQuery%";
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $connect->prepare(
            "SELECT COUNT(*) FROM recipes r WHERE $whereSql"
        );
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $connect->prepare("
            SELECT
                r.id,
                r.title,
                r.status,
                r.created_at,
                r.cooking_time,
                r.calories,
                u.username AS author_name,
                COUNT(DISTINCT uf.user_id) AS likes_count
            FROM recipes r
            LEFT JOIN users u ON r.author_id = u.id
            LEFT JOIN user_favorites uf ON r.id = uf.recipe_id
            WHERE $whereSql
            GROUP BY r.id
            ORDER BY r.created_at DESC
            LIMIT $perPage OFFSET $offset
        ");

        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columns = [
            ['key' => 'title', 'label' => 'Название'],
            ['key' => 'author_name', 'label' => 'Автор'],
            ['key' => 'status', 'label' => 'Статус'],
            ['key' => 'likes_count', 'label' => 'Лайки'],
            ['key' => 'created_at', 'label' => 'Дата']
        ];

        break;

    // КАТЕГОРИИ
    case 'categories':

        $where = '';
        $params = [];

        if ($searchQuery !== '') {
            $where = 'WHERE c.name LIKE ?';
            $params[] = "%$searchQuery%";
        }

        $stmt = $connect->prepare("
            SELECT COUNT(*) FROM categories c $where
        ");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $connect->prepare("
            SELECT
                c.id,
                c.name,
                c.slug,
                c.description,
                COUNT(rc.recipe_id) AS recipes_count
            FROM categories c
            LEFT JOIN recipe_categories rc ON c.id = rc.category_id
            $where
            GROUP BY c.id
            ORDER BY c.name ASC
            LIMIT $perPage OFFSET $offset
        ");

        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columns = [
            ['key' => 'name', 'label' => 'Название'],
            ['key' => 'slug', 'label' => 'Slug'],
            ['key' => 'recipes_count', 'label' => 'Рецептов']
        ];

        break;

    // ИНГРЕДИЕНТЫ
    case 'ingredients':

        $where = '';
        $params = [];

        if ($searchQuery !== '') {
            $where = 'WHERE name LIKE ?';
            $params[] = "%$searchQuery%";
        }

        $stmt = $connect->prepare("
            SELECT COUNT(DISTINCT name)
            FROM recipe_ingredients
            $where
        ");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $connect->prepare("
            SELECT
                MIN(id) AS id,
                name,
                COUNT(*) AS usage_count
            FROM recipe_ingredients
            $where
            GROUP BY name
            ORDER BY usage_count DESC, name ASC
            LIMIT $perPage OFFSET $offset
        ");

        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columns = [
            ['key' => 'name', 'label' => 'Название ингредиента'],
            ['key' => 'usage_count', 'label' => 'В рецептах']
        ];

        break;
    // КОММЕНТАРИИ
    case 'comments':

        $where = ['r.deleted_at IS NULL'];
        $params = [];

        if ($searchQuery !== '') {
            $where[] = 'c.text LIKE ?';
            $params[] = "%$searchQuery%";
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $connect->prepare("
            SELECT COUNT(*)
            FROM comments c
            INNER JOIN recipes r ON c.recipe_id = r.id
            WHERE $whereSql
        ");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $connect->prepare("
            SELECT
                c.id,
                c.text,
                c.created_at,
                c.recipe_id,
                u.username AS author_name,
                r.title AS recipe_title
            FROM comments c
            INNER JOIN recipes r ON c.recipe_id = r.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE $whereSql
            ORDER BY c.created_at DESC
            LIMIT $perPage OFFSET $offset
        ");

        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columns = [
            ['key' => 'text', 'label' => 'Текст'],
            ['key' => 'author_name', 'label' => 'Автор'],
            ['key' => 'recipe_title', 'label' => 'Рецепт'],
            ['key' => 'created_at', 'label' => 'Дата']
        ];

        break;
    // ПОДПИСЧИКИ РАССЫЛКИ
    case 'newsletter_subscribers':

        $where = '';
        $params = [];

        if ($searchQuery !== '') {
            $where = 'WHERE email LIKE ?';
            $params[] = "%$searchQuery%";
        }

        $stmt = $connect->prepare("
            SELECT COUNT(*) FROM newsletter_subscribers $where
        ");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $connect->prepare("
            SELECT id, email, subscribed_at
            FROM newsletter_subscribers
            $where
            ORDER BY subscribed_at DESC
            LIMIT $perPage OFFSET $offset
        ");

        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columns = [
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'subscribed_at', 'label' => 'Дата подписки']
        ];

        break;
    // ПОДПИСКИ
    case 'subscriptions':

        $stmt = $connect->query(
            "SELECT COUNT(*) FROM subscriptions"
        );
        $total = (int)$stmt->fetchColumn();

        $stmt = $connect->prepare("
            SELECT
                s.follower_id,
                s.following_id,
                u1.email AS follower,
                u2.email AS following,
                s.created_at
            FROM subscriptions s
            LEFT JOIN users u1 ON s.follower_id = u1.id
            LEFT JOIN users u2 ON s.following_id = u2.id
            ORDER BY s.created_at DESC
            LIMIT $perPage OFFSET $offset
        ");

        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columns = [
            ['key' => 'follower', 'label' => 'Кто подписан'],
            ['key' => 'following', 'label' => 'На кого'],
            ['key' => 'created_at', 'label' => 'Дата']
        ];

        break;
    // ИЗБРАННОЕ
    case 'favorites':

        $where = '';
        $params = [];

        if ($searchQuery !== '') {
            $where = 'WHERE u.username LIKE ? OR r.title LIKE ?';
            $params = ["%$searchQuery%", "%$searchQuery%"];
        }

        $stmt = $connect->prepare("
            SELECT COUNT(*)
            FROM user_favorites uf
            INNER JOIN users u ON uf.user_id = u.id
            INNER JOIN recipes r ON uf.recipe_id = r.id
            $where
        ");

        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $connect->prepare("
            SELECT
                uf.id,
                uf.user_id,
                uf.recipe_id,
                uf.created_at,
                u.username AS user_name,
                r.title AS recipe_title
            FROM user_favorites uf
            INNER JOIN users u ON uf.user_id = u.id
            INNER JOIN recipes r ON uf.recipe_id = r.id
            $where
            ORDER BY uf.created_at DESC
            LIMIT $perPage OFFSET $offset
        ");

        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columns = [
            ['key' => 'user_name', 'label' => 'Пользователь'],
            ['key' => 'recipe_title', 'label' => 'Рецепт'],
            ['key' => 'created_at', 'label' => 'Добавлено']
        ];

        break;
    // ШАБЛОНЫ АЛЬБОМОВ
    case 'album_templates':

        $where = '';
        $params = [];

        if ($searchQuery !== '') {
            $where = 'WHERE title LIKE ?';
            $params[] = "%$searchQuery%";
        }

        $stmt = $connect->prepare("
            SELECT COUNT(*) FROM album_templates $where
        ");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $connect->prepare("
            SELECT id, title, is_active
            FROM album_templates
            $where
            ORDER BY id DESC
            LIMIT $perPage OFFSET $offset
        ");

        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columns = [
            ['key' => 'title', 'label' => 'Название шаблона'],
            ['key' => 'is_active', 'label' => 'Активен']
        ];

        break;
    // АССЕТЫ
    case 'assets':

        $where = '';
        $params = [];

        if ($searchQuery !== '') {
            $where = 'WHERE name LIKE ? OR type LIKE ? OR category LIKE ?';
            $params = [
                "%$searchQuery%",
                "%$searchQuery%",
                "%$searchQuery%"
            ];
        }

        $stmt = $connect->prepare("
            SELECT COUNT(*) FROM assets $where
        ");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $connect->prepare("
            SELECT id, name, type, category, image_url, is_active, created_at
            FROM assets
            $where
            ORDER BY created_at DESC
            LIMIT $perPage OFFSET $offset
        ");

        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columns = [
            ['key' => 'name', 'label' => 'Название'],
            ['key' => 'type', 'label' => 'Тип'],
            ['key' => 'category', 'label' => 'Категория'],
            ['key' => 'is_active', 'label' => 'Активен'],
            ['key' => 'created_at', 'label' => 'Добавлен']
        ];

        break;
}
// 6. ПАГИНАЦИЯ И ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
$totalPages = max(1, (int)ceil($total / $perPage));

function buildAdminListUrl($params = [])
{
    $base = [
        'page' => 'admin_list',
        'entity' => $_GET['entity'] ?? 'users'
    ];

    if (!empty($_GET['q'])) {
        $base['q'] = $_GET['q'];
    }

    if (($base['entity'] ?? '') === 'recipes') {
        $base['status'] = $_GET['status'] ?? 'all';
    }

    return '?' . http_build_query(array_merge($base, $params));
}

function getRoleBadge($role)
{
    return match ($role) {
        'admin' => '<span class="status-badge" style="background:#f8d7da;color:#721c24;">Админ</span>',
        'moderator' => '<span class="status-badge" style="background:#fff3cd;color:#856404;">Модератор</span>',
        default => '<span class="status-badge" style="background:#d4edda;color:#155724;">Пользователь</span>'
    };
}

function getStatusBadge($status)
{
    return match ($status) {
        'published' => '<span class="status-badge status-published">Опубликован</span>',
        'draft' => '<span class="status-badge status-draft">Черновик</span>',
        'moderation' => '<span class="status-badge status-moderation">На модерации</span>',
        'hidden' => '<span class="status-badge status-hidden">Скрыт</span>',
        default => '—'
    };
}

?>

<!-- 7. ИНТЕРФЕЙС СПИСКА -->
<div class="container">

    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <a href="?page=admin_panel">Панель администратора</a>
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

                    <?php
                        $statuses = [
                            'all' => 'Все',
                            'published' => 'Опубликованные',
                            'moderation' => 'На модерации',
                            'draft' => 'Черновики',
                            'hidden' => 'Скрытые'
                        ];
                        ?>

                    <?php foreach ($statuses as $key => $label): ?>

                    <a href="<?= buildAdminListUrl(['status' => $key, 'p' => 1]) ?>"
                        class="status_tab <?= $statusFilter === $key ? 'active' : '' ?>">
                        <?= $label ?>
                    </a>

                    <?php endforeach; ?>

                </div>

                <?php endif; ?>

            </div>

            <!-- ПОИСК И ДОБАВЛЕНИЕ -->
            <div class="admin_toolbar">

                <form method="GET" class="search_input" style="flex:1;max-width:400px;">

                    <input type="hidden" name="page" value="admin_list">

                    <input type="hidden" name="entity" value="<?= htmlspecialchars($entity) ?>">

                    <?php if ($entity === 'recipes'): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                    <?php endif; ?>

                    <input type="search" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Поиск...">

                </form>

                <?php if (!empty($info['add_text'])): ?>

                <a href="<?= htmlspecialchars($info['add_url']) ?>" class="btn v1">
                    <img src="asset/img/icon_add.svg" alt="">
                    <span><?= htmlspecialchars($info['add_text']) ?></span>
                </a>

                <?php endif; ?>

            </div>

            <!-- ТАБЛИЦА -->
            <div class="products_table_wrapper">

                <table class="products_table">

                    <thead>
                        <tr>

                            <?php foreach ($columns as $column): ?>
                            <th><?= htmlspecialchars($column['label']) ?></th>
                            <?php endforeach; ?>

                            <th class="actions_col">Действия</th>

                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!empty($items)): ?>

                        <?php foreach ($items as $item): ?>

                        <tr>

                            <?php foreach ($columns as $column): ?>

                            <?php
                                    $key = $column['key'];
                                    $value = $item[$key] ?? '—';
                                    ?>

                            <td>

                                <?php if ($key === 'title' && $entity === 'recipes'): ?>

                                <a href="?page=recipe&id=<?= urlencode($item['id']) ?>" class="table_link"
                                    target="_blank">
                                    <?= htmlspecialchars(mb_strimwidth((string)$value, 0, 60, '...')) ?>
                                </a>

                                <?php elseif ($key === 'role' && $entity === 'users'): ?>

                                <?= getRoleBadge($value) ?>

                                <?php elseif ($key === 'status' && $entity === 'recipes'): ?>

                                <?= getStatusBadge($value) ?>

                                <?php elseif ($key === 'text' && $entity === 'comments'): ?>

                                <div class="comment_preview">
                                    <?= htmlspecialchars(mb_strimwidth((string)$value, 0, 100, '...')) ?>
                                </div>

                                <?php elseif ($key === 'recipe_title' && in_array($entity, ['comments', 'favorites'], true)): ?>

                                <a href="?page=recipe&id=<?= urlencode($item['recipe_id']) ?>" target="_blank"
                                    class="table_link">
                                    <?= htmlspecialchars(mb_strimwidth((string)$value, 0, 50, '...')) ?>
                                </a>

                                <?php elseif ($key === 'user_name' && $entity === 'favorites'): ?>

                                <a href="?page=user_profile&id=<?= urlencode($item['user_id']) ?>" class="table_link">
                                    <?= htmlspecialchars($value) ?>
                                </a>

                                <?php elseif ($key === 'is_active' && in_array($entity, ['assets', 'fonts', 'album_templates'], true)): ?>

                                <?= $value ? 'Да' : 'Нет' ?>

                                <?php elseif ($key === 'created_at' && !empty($value)): ?>

                                <?= date('d.m.Y H:i', strtotime($value)) ?>

                                <?php elseif ($key === 'subscribed_at' && !empty($value)): ?>

                                <?= date('d.m.Y H:i', strtotime($value)) ?>

                                <?php elseif ($key === 'type' && $entity === 'assets'): ?>

                                <span style="text-transform:capitalize;font-weight:500;color:#555;">
                                    <?= htmlspecialchars($value) ?>
                                </span>

                                <?php elseif ($key === 'category' && $entity === 'assets'): ?>

                                <span
                                    style="background:#f0f0f0;padding:2px 8px;border-radius:12px;font-size:12px;color:#666;">
                                    <?= htmlspecialchars($value) ?>
                                </span>

                                <?php elseif ($key === 'family' && $entity === 'fonts'): ?>

                                <span
                                    style="font-family:monospace;background:#f0f0f0;padding:2px 8px;border-radius:4px;font-size:13px;color:#333;">
                                    <?= htmlspecialchars($value) ?>
                                </span>

                                <?php elseif ($key === 'style' && $entity === 'fonts'): ?>

                                <span style="text-transform:capitalize;color:#666;font-size:14px;">
                                    <?= htmlspecialchars($value ?: '—') ?>
                                </span>

                                <?php elseif ($key === 'is_active' && in_array($entity, ['assets', 'fonts'], true)): ?>

                                <?= $value
                                                ? '<span style="color:#28a745;font-weight:600;">Да</span>'
                                                : '<span style="color:#dc3545;font-weight:600;">Нет</span>' ?>

                                <?php elseif ($key === 'image_url' && $entity === 'assets'): ?>

                                <?php if ($value !== '—' && $value !== ''): ?>
                                <img src="<?= htmlspecialchars($value) ?>" alt=""
                                    style="width:55px;height:55px;object-fit:cover;border-radius:6px;">
                                <?php else: ?>
                                —
                                <?php endif; ?>

                                <?php else: ?>

                                <?= htmlspecialchars((string)$value) ?>

                                <?php endif; ?>

                            </td>

                            <?php endforeach; ?>

                            <!-- ДЕЙСТВИЯ -->

                            <td class="actions_col">

                                <div class="table_actions">

                                    <?php if ($entity === 'comments'): ?>

                                    <a href="?page=admin_list&entity=comments&action=delete&id=<?= urlencode($item['id']) ?>"
                                        class="action_btn" title="Удалить комментарий"
                                        onclick="return confirm('Вы уверены, что хотите удалить этот комментарий?');">
                                        <img src="/asset/img/icon_trash.svg" alt="Удалить">
                                    </a>

                                    <?php elseif ($entity === 'subscriptions'): ?>

                                    <a href="?page=admin_list&entity=subscriptions&action=delete&follower_id=<?= urlencode($item['follower_id']) ?>&following_id=<?= urlencode($item['following_id']) ?>"
                                        class="action_btn" title="Разорвать связь"
                                        onclick="return confirm('Разорвать эту связь подписки?');">
                                        <img src="/asset/img/icon_trash.svg" alt="Удалить">
                                    </a>

                                    <?php elseif ($entity === 'favorites'): ?>

                                    <a href="?page=admin_list&entity=favorites&action=delete&id=<?= urlencode($item['id']) ?>"
                                        class="action_btn" title="Убрать из избранного"
                                        onclick="return confirm('Убрать этот рецепт из избранного пользователя?');">
                                        <img src="/asset/img/icon_trash.svg" alt="Удалить">
                                    </a>

                                    <?php elseif ($entity === 'newsletter_subscribers'): ?>

                                    <a href="?page=admin_list&entity=newsletter_subscribers&action=delete&id=<?= urlencode($item['id']) ?>"
                                        class="action_btn" title="Отписать"
                                        onclick="return confirm('Отписать этого пользователя от рассылки?');">
                                        <img src="/asset/img/icon_trash.svg" alt="Отписать">
                                    </a>

                                    <?php elseif (in_array($entity, ['recipes', 'categories', 'ingredients', 'users', 'album_templates', 'assets'], true)): ?>

                                    <a href="?page=admin_form&entity=<?= urlencode($entity) ?>&id=<?= urlencode($item['id']) ?>"
                                        class="action_btn" title="Редактировать">
                                        <img src="/asset/img/icon_edit.svg" style="width:18px;height:18px;"
                                            alt="Редактировать">
                                    </a>

                                    <a href="?page=admin_list&entity=<?= urlencode($entity) ?>&action=delete&id=<?= urlencode($item['id']) ?>"
                                        class="action_btn" title="Удалить"
                                        onclick="return confirm('Вы уверены, что хотите удалить эту запись? Это действие нельзя отменить.');">
                                        <img src="/asset/img/icon_trash.svg" alt="Удалить">
                                    </a>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                        <?php else: ?>

                        <tr>
                            <td colspan="<?= count($columns) + 1 ?>" style="text-align:center;padding:40px;color:#888;">
                                Ничего не найдено
                            </td>
                        </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>
            <!-- ПАГИНАЦИЯ -->
            <?php if ($totalPages > 1): ?>

            <div class="pagination mt-16">

                <?php if ($currentPage > 1): ?>

                <a href="<?= buildAdminListUrl(['p' => $currentPage - 1]) ?>" class="pagination_arrow">←</a>

                <?php else: ?>

                <span class="pagination_arrow disabled">←</span>

                <?php endif; ?>

                <div class="pagination_pages">

                    <?php
                        $pagesToShow = [];

                        for ($i = 1; $i <= $totalPages; $i++) {

                            if (
                                $i === 1 ||
                                $i === $totalPages ||
                                abs($i - $currentPage) <= 2
                            ) {
                                $pagesToShow[] = $i;

                            } elseif (end($pagesToShow) !== '...') {

                                $pagesToShow[] = '...';
                            }
                        }
                        ?>

                    <?php foreach ($pagesToShow as $pageNumber): ?>

                    <?php if ($pageNumber === '...'): ?>

                    <span class="page dots">...</span>

                    <?php else: ?>

                    <a href="<?= buildAdminListUrl(['p' => $pageNumber]) ?>"
                        class="page <?= $pageNumber === $currentPage ? 'active' : '' ?>">
                        <?= $pageNumber ?>
                    </a>

                    <?php endif; ?>

                    <?php endforeach; ?>

                </div>

                <?php if ($currentPage < $totalPages): ?>

                <a href="<?= buildAdminListUrl(['p' => $currentPage + 1]) ?>" class="pagination_arrow">→</a>

                <?php else: ?>

                <span class="pagination_arrow disabled">→</span>

                <?php endif; ?>

            </div>

            <?php endif; ?>

        </div>
    </div>
</div>