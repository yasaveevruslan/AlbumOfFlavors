<?php
    global $connect;

    $recipe_id = $_GET['id'] ?? '';
    
    if ($recipe_id === '') {
        echo "<div class='container'><h1>Рецепт не найден</h1></div>";
        return;
    }

    $stmt = $connect->prepare("
        SELECT 
            r.id, r.title, r.description, r.cover_url, 
            r.cooking_time, r.servings, r.calories,
            r.created_at,
            u.id AS author_id, u.username AS author_name, u.avatar_url AS author_avatar,
            COUNT(DISTINCT uf.user_id) AS likes_count
        FROM recipes r
        LEFT JOIN users u ON r.author_id = u.id
        LEFT JOIN user_favorites uf ON r.id = uf.recipe_id
        WHERE r.id = ? 
          AND r.status = 'published' 
          AND r.deleted_at IS NULL
        GROUP BY r.id
    ");
    $stmt->execute([$recipe_id]);
    $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipe) {
        echo "<div class='container'><h1>Рецепт не найден или удалён</h1></div>";
        return;
    }

    $stmt = $connect->prepare("
        SELECT c.id, c.name, c.slug 
        FROM categories c
        INNER JOIN recipe_categories rc ON c.id = rc.category_id
        WHERE rc.recipe_id = ?
        LIMIT 1
    ");
    $stmt->execute([$recipe_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $connect->prepare("
        SELECT name, amount 
        FROM recipe_ingredients 
        WHERE recipe_id = ? 
        ORDER BY order_index ASC
    ");
    $stmt->execute([$recipe_id]);
    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $connect->prepare("
        SELECT step_number, text, image_url 
        FROM recipe_steps 
        WHERE recipe_id = ? 
        ORDER BY step_number ASC
    ");
    $stmt->execute([$recipe_id]);
    $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $connect->prepare("
        SELECT 
            c.id, c.text, c.created_at,
            u.username, u.avatar_url
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.recipe_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$recipe_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $is_liked = false;
    if (isset($_SESSION['user_id'])) {
        $stmt = $connect->prepare("
            SELECT 1 FROM user_favorites 
            WHERE user_id = ? AND recipe_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $recipe_id]);
        $is_liked = $stmt->fetch() ? true : false;
    }

    $recipe_date = date('d.m.Y', strtotime($recipe['created_at']));
?>

<div class="container recipe_page">
    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <a href="?page=categories">Каталог рецептов</a>
        <?php if ($category): ?>
        <a href="?page=category&slug=<?= urlencode($category['slug']) ?>">
            <?= htmlspecialchars($category['name']) ?>
        </a>
        <?php endif; ?>
        <span><?= htmlspecialchars($recipe['title']) ?></span>
    </nav>

    <div class="recipe_header">
        <h1 class="recipe_title"><?= htmlspecialchars($recipe['title']) ?></h1>
        <?php if (!empty($recipe['description'])): ?>
        <p class="recipe_description"><?= nl2br(htmlspecialchars($recipe['description'])) ?></p>
        <?php endif; ?>

        <div class="recipe_meta">
            <?php if (!empty($recipe['author_name'])): ?>
            <div class="recipe_author_block">
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
                <div class="author_info">
                    <span class="author_label">Автор рецепта:</span>
                    <span class="author_name"><?= htmlspecialchars($recipe['author_name']) ?></span>
                    <span class="author_date">Опубликовано: <?= $recipe_date ?></span>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($recipe['cooking_time']): ?>
            <div class="meta_item">
                <img src="asset/img/icon_clock.svg" alt="time">
                <span><?= (int)$recipe['cooking_time'] ?> мин</span>
            </div>
            <?php endif; ?>
            <?php if ($recipe['servings']): ?>
            <div class="meta_item">
                <img src="asset/img/icon_people.svg" alt="servings">
                <span><?= (int)$recipe['servings'] ?> порций</span>
            </div>
            <?php endif; ?>
            <?php if ($recipe['calories']): ?>
            <div class="meta_item">
                <img src="asset/img/icon_kkal.svg" alt="kkal">
                <span><?= (int)$recipe['calories'] ?> ккал</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="recipe_top">
        <div class="recipe_gallery">
            <div class="recipe_main_image">
                <img src="<?= htmlspecialchars($recipe['cover_url'] ?: 'asset/img/placeholder.png') ?>"
                    alt="<?= htmlspecialchars($recipe['title']) ?>">
            </div>

            <div class="recipe_likes">
                <button class="heart_icon <?= $is_liked ? 'active' : '' ?>" id="likeBtn"
                    data-recipe-id="<?= $recipe['id'] ?>"
                    title="<?= isset($_SESSION['user_id']) ? 'Нравится' : 'Войдите, чтобы лайкнуть' ?>">
                    <img src="asset/img/icon_heart<?= $is_liked ? '-active' : '' ?>.svg" alt="like">
                </button>
                <span id="likesCount"><?= (int)$recipe['likes_count'] ?></span>
            </div>
        </div>

        <div class="recipe_ingredients_block">
            <button class="ingredients_header" id="ingredientsToggle" type="button">
                <h3 class="ingredients_title">Ингредиенты:</h3>
                <svg class="chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>
            <ul class="ingredients_list" id="ingredientsList">
                <?php if (!empty($ingredients)): ?>
                <?php foreach ($ingredients as $ing): ?>
                <li class="ingredient_item">
                    <span class="ingredient_name"><?= htmlspecialchars($ing['name']) ?></span>
                    <span class="ingredient_amount"><?= htmlspecialchars($ing['amount'] ?? '') ?></span>
                </li>
                <?php endforeach; ?>
                <?php else: ?>
                <li class="ingredient_item">Ингредиенты не указаны</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <?php if (!empty($steps)): ?>
    <section class="recipe_steps">
        <div class="steps_wrapper">
            <h2 class="section_title">Шаги приготовления:</h2>
            <div class="steps_grid">
                <?php foreach ($steps as $step): ?>
                <div class="step_card">
                    <?php if (!empty($step['image_url'])): ?>
                    <div class="step_image">
                        <img src="<?= htmlspecialchars($step['image_url']) ?>"
                            alt="Шаг <?= (int)$step['step_number'] ?>" loading="lazy">
                    </div>
                    <?php endif; ?>
                    <div class="step_body">
                        <span class="step_number"><?= (int)$step['step_number'] ?></span>
                        <p class="step_description"><?= nl2br(htmlspecialchars($step['text'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="recipe_comments">
        <h2 class="section_title">Комментарии: <span>(<?= count($comments) ?>)</span></h2>

        <?php if (isset($_SESSION['user_id'])): ?>
        <form class="comment_form" id="commentForm">
            <h4>Оставить комментарий</h4>
            <input type="hidden" name="recipe_id" value="<?= htmlspecialchars($recipe_id) ?>">
            <textarea name="text" placeholder="Ваш комментарий..." required></textarea>
            <button type="submit" class="btn v1">Отправить</button>
        </form>
        <?php else: ?>
        <p class="comment_login_hint">
            <a href="#" onclick="openModal(loginModal); return false;">Войдите</a>, чтобы оставить комментарий
        </p>
        <?php endif; ?>

        <div class="comments_list" id="commentsList">
            <?php if (!empty($comments)): ?>
            <?php foreach ($comments as $comment): ?>
            <div class="comment_item" data-comment-id="<?= $comment['id'] ?>">
                <div class="comment_avatar">
                    <?php 
                        $avatar_letter = mb_strtoupper(mb_substr($comment['username'] ?? '?', 0, 1));
                        if (!empty($comment['avatar_url'])): 
                    ?>
                    <img src="<?= htmlspecialchars($comment['avatar_url']) ?>" alt="">
                    <?php else: ?>
                    <?= htmlspecialchars($avatar_letter) ?>
                    <?php endif; ?>
                </div>
                <div class="comment_body">
                    <div class="comment_header">
                        <span class="comment_author"><?= htmlspecialchars($comment['username'] ?? 'Аноним') ?></span>
                        <span class="comment_date"><?= date('d.m.Y', strtotime($comment['created_at'])) ?></span>
                    </div>
                    <p class="comment_text"><?= nl2br(htmlspecialchars($comment['text'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p class="no_comments">Пока нет комментариев. Будьте первым!</p>
            <?php endif; ?>
        </div>
    </section>
</div>
<script>
const IS_LOGGED_IN = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>