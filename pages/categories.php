<?php
    global $connect;
    $sql = " SELECT c.id, c.name, c.slug, c.image_url, COUNT(r.id) AS recipe_count 
        FROM categories c
        LEFT JOIN recipe_categories rc ON c.id = rc.category_id
        LEFT JOIN recipes r ON rc.recipe_id = r.id 
            AND r.deleted_at IS NULL 
            AND r.status = 'published'
        GROUP BY c.id, c.name, c.slug, c.image_url
        ORDER BY c.id ASC
    ";

    $stmt = $connect->query($sql);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <span>Каталог рецептов</span>
    </div>
    <div class="main_categories">
        <h3>Каталог рецептов</h3>
        <div class="main_categories_list">
            <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
            <div class="main_category_item">
                <div class="main_category_body">
                    <h6><?= htmlspecialchars($cat['name']) ?></h6>
                    <p><?= (int)$cat['recipe_count'] ?> рецептов</p>
                </div>
                <div class="main_category_img">
                    <img src="<?= htmlspecialchars($cat['image_url']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>"
                        loading="lazy">
                </div>
                <a title="<?= htmlspecialchars($cat['name']) ?>"
                    href="?page=category&slug=<?= urlencode($cat['slug']) ?>"></a>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p>Категории пока не добавлены.</p>
            <?php endif; ?>
        </div>
    </div>
</div>