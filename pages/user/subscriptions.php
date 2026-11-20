<?php
    global $connect;
    $active_nav = 'subscriptions';
    
    $tab = $_GET['tab'] ?? 'following';
    if (!in_array($tab, ['following', 'followers'])) {
        $tab = 'following';
    }
    
    $per_page = 12;
    $current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    if ($tab === 'following') {
        $count_sql = "SELECT COUNT(*) FROM subscriptions WHERE follower_id = ?";
        $count_params = [$_SESSION['user_id']];
        
        $sql = "
            SELECT 
                u.id, u.username, u.avatar_url,
                (SELECT COUNT(*) FROM subscriptions WHERE following_id = u.id) AS followers_count,
                (SELECT COUNT(*) FROM recipes WHERE author_id = u.id AND status = 'published' AND deleted_at IS NULL) AS recipes_count,
                s.created_at AS subscribed_at
            FROM subscriptions s
            INNER JOIN users u ON s.following_id = u.id
            WHERE s.follower_id = ?
            ORDER BY s.created_at DESC
            LIMIT $per_page OFFSET $offset
        ";
        $sql_params = [$_SESSION['user_id']];
        
        $title = 'Подписки';
        $empty_text = 'Вы пока ни на кого не подписаны';
        $empty_hint = 'Подпишитесь на авторов, чьи рецепты вам нравятся';
    } else {
        $count_sql = "SELECT COUNT(*) FROM subscriptions WHERE following_id = ?";
        $count_params = [$_SESSION['user_id']];
        
        $sql = "
            SELECT 
                u.id, u.username, u.avatar_url,
                (SELECT COUNT(*) FROM subscriptions WHERE following_id = u.id) AS followers_count,
                (SELECT COUNT(*) FROM recipes WHERE author_id = u.id AND status = 'published' AND deleted_at IS NULL) AS recipes_count,
                s.created_at AS subscribed_at
            FROM subscriptions s
            INNER JOIN users u ON s.follower_id = u.id
            WHERE s.following_id = ?
            ORDER BY s.created_at DESC
            LIMIT $per_page OFFSET $offset
        ";
        $sql_params = [$_SESSION['user_id']];
        
        $title = 'Подписчики';
        $empty_text = 'У вас пока нет подписчиков';
        $empty_hint = 'Делитесь рецептами, чтобы привлечь подписчиков';
    }
    
    $stmt = $connect->prepare($count_sql);
    $stmt->execute($count_params);
    $total = (int)$stmt->fetchColumn();
    $total_pages = max(1, ceil($total / $per_page));
    
    $stmt = $connect->prepare($sql);
    $stmt->execute($sql_params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        $my_following_ids = [];
    if ($tab === 'followers') {
        $stmt_following = $connect->prepare("SELECT following_id FROM subscriptions WHERE follower_id = ?");
        $stmt_following->execute([$_SESSION['user_id']]);
        $my_following_ids = $stmt_following->fetchAll(PDO::FETCH_COLUMN);
    }
    
    function buildSubscriptionsUrl($page_num, $tab) {
        $params = ['page' => 'subscriptions', 'tab' => $tab, 'p' => $page_num];
        return '?' . http_build_query($params);
    }
?>

<div class="container">
    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <a href="?page=profile">Личный кабинет</a>
        <span><?= htmlspecialchars($title) ?></span>
    </nav>

    <?php include __DIR__ . '/../../includes/profile_header.php'; ?>

    <div class="profile_container">
        <?php include __DIR__ . '/../../includes/profile_sidebar.php'; ?>

        <div class="profile_content">
            <div class="subscriptions_header">
                <h2 class="section_title"><?= htmlspecialchars($title) ?></h2>
                <span class="subscriptions_count"><?= $total ?>
                    <?= $tab === 'following' ? 'автор' . get_plural($total, '', 'а', 'ов') : 'подписчик' . get_plural($total, '', 'а', 'ов') ?></span>
            </div>

            <div class="subscriptions_tabs">
                <a href="?page=subscriptions&tab=following"
                    class="tab_item <?= $tab === 'following' ? 'active' : '' ?>">
                    Подписки
                </a>
                <a href="?page=subscriptions&tab=followers"
                    class="tab_item <?= $tab === 'followers' ? 'active' : '' ?>">
                    Подписчики
                </a>
            </div>

            <?php if (!empty($users)): ?>
            <div class="subscriptions_grid" id="subscriptionsGrid">
                <?php foreach ($users as $user): 
                        $avatar_letter = mb_strtoupper(mb_substr($user['username'], 0, 1));
                    ?>
                <div class="author_card" data-user-id="<?= $user['id'] ?>">
                    <div class="author_card_body">
                        <a href="?page=user_profile&id=<?= $user['id'] ?>" class="author_avatar_link">
                            <div class="author_avatar">
                                <?php if (!empty($user['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars($user['avatar_url']) ?>"
                                    alt="<?= htmlspecialchars($user['username']) ?>">
                                <?php else: ?>
                                <span class="avatar_letter"><?= htmlspecialchars($avatar_letter) ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="author_info">
                            <a href="?page=user_profile&id=<?= $user['id'] ?>" class="author_name_link">
                                <h3 class="author_name"><?= htmlspecialchars($user['username']) ?></h3>
                            </a>
                            <div class="author_stats">
                                <span><?= (int)$user['followers_count'] ?> подписчиков</span>
                                <span class="stats_separator">•</span>
                                <span><?= (int)$user['recipes_count'] ?> рецептов</span>
                            </div>
                        </div>
                    </div>
                    <div class="author_card_actions">
                        <a href="?page=user_profile&id=<?= $user['id'] ?>" class="btn v1 btn_outline">Перейти</a>

                        <?php if ($tab === 'following'): ?>
                        <button class="btn_author btn_unsubscribe" data-user-id="<?= $user['id'] ?>">
                            Отписаться
                        </button>
                        <?php else: ?>
                        <?php if (in_array($user['id'], $my_following_ids)): ?>
                        <button class="btn_author btn_unsubscribe" data-user-id="<?= $user['id'] ?>">
                            Отписаться
                        </button>
                        <?php else: ?>
                        <button class="btn_author btn_follow_back" data-user-id="<?= $user['id'] ?>">
                            Подписаться в ответ
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination mt-16">
                <?php if ($current_page > 1): ?>
                <a href="<?= buildSubscriptionsUrl($current_page - 1, $tab) ?>" class="pagination_arrow">←</a>
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
                    <a href="<?= buildSubscriptionsUrl($p, $tab) ?>"
                        class="page <?= $p == $current_page ? 'active' : '' ?>">
                        <?= $p ?>
                    </a>
                    <?php 
                                    endif;
                                endforeach; 
                            ?>
                </div>

                <?php if ($current_page < $total_pages): ?>
                <a href="<?= buildSubscriptionsUrl($current_page + 1, $tab) ?>" class="pagination_arrow">→</a>
                <?php else: ?>
                <span class="pagination_arrow disabled">→</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="empty_state">
                <h3><?= htmlspecialchars($empty_text) ?></h3>
                <p style="color:#888; margin-bottom:24px;"><?= htmlspecialchars($empty_hint) ?></p>
                <?php if ($tab === 'following'): ?>
                <a href="?page=categories" class="btn v1">Найти авторов</a>
                <?php else: ?>
                <a href="?page=create_recipe" class="btn v1">Создать рецепт</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>