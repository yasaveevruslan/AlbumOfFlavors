<?php
global $connect;

$album_id = $_GET['id'] ?? '';

if ($album_id === '') {
    header("Location: ?page=albums");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ?page=main&auth_required=1");
    exit;
}

$user_id = $_SESSION['user_id'];


// 1. ПОЛУЧАЕМ АЛЬБОМ
$stmt = $connect->prepare("
    SELECT id, title, updated_at
    FROM albums
    WHERE id = ? AND user_id = ?
");

$stmt->execute([$album_id, $user_id]);

$album = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$album) {
    header("Location: ?page=albums");
    exit;
}


// 2. ПОЛУЧАЕМ СТРАНИЦЫ АЛЬБОМА


$stmt = $connect->prepare("
    SELECT id, page_number, layout
    FROM album_pages
    WHERE album_id = ?
    ORDER BY page_number ASC
");

$stmt->execute([$album_id]);

$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pagesByNumber = [];

foreach ($pages as $index => $pageItem) {
    $pages[$index]['layout'] = json_decode(
        $pageItem['layout'] ?? '{}',
        true
    ) ?? [];

    $pagesByNumber[(int)$pageItem['page_number']] = $pages[$index];
}

// Получаем страницы текущего разворота: 2 и 3
$leftPage = $pagesByNumber[2] ?? ($pages[0] ?? null);
$rightPage = $pagesByNumber[3] ?? ($pages[1] ?? null);


// 3. ПОЛУЧАЕМ МАТЕРИАЛЫ ИЗ БИБЛИОТЕКИ


$stmt = $connect->prepare("
    SELECT
        id,
        type,
        name,
        category,
        image_url,
        pattern_url,
        width,
        height
    FROM assets
    WHERE is_active = 1
    ORDER BY type ASC, name ASC
");

$stmt->execute();

$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $connect->prepare("
    SELECT
        id,
        type,
        name,
        url
    FROM user_assets
    WHERE user_id = ?
      AND type = 'photo'
    ORDER BY created_at DESC
");

$stmt->execute([$user_id]);

$userPhotos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// 3.1. ПОЛУЧАЕМ ИЗБРАННЫЕ РЕЦЕПТЫ


$stmt = $connect->prepare("
    SELECT
        r.id,
        r.title,
        r.description,
        r.cover_url,
        r.cooking_time,
        r.servings,
        r.calories
    FROM user_favorites uf
    INNER JOIN recipes r
        ON r.id = uf.recipe_id
    WHERE uf.user_id = ?
      AND r.status = 'published'
      AND r.deleted_at IS NULL
    ORDER BY uf.created_at DESC
");

$stmt->execute([$user_id]);

$favoriteRecipes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// 4. ПОЛУЧАЕМ СОХРАНЁННЫЕ ЭЛЕМЕНТЫ СТРАНИЦ


$stmt = $connect->prepare("
    SELECT
        pe.id,
        pe.page_id,
        pe.type,
        pe.source_id,
        COALESCE(NULLIF(pe.source_url, ''), a.image_url) AS image_url,
        pe.position_x,
        pe.position_y,
        pe.width,
        pe.height,
        pe.rotation,
        pe.z_index,
        pe.data
    FROM page_elements pe
    INNER JOIN album_pages ap
        ON ap.id = pe.page_id
    INNER JOIN albums al
        ON al.id = ap.album_id
    LEFT JOIN assets a
        ON a.id = pe.source_id
    WHERE al.id = ?
      AND al.user_id = ?
    ORDER BY pe.z_index ASC
");

$stmt->execute([$album_id, $user_id]);

$allElements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Группируем элементы по ID страницы
$elementsByPage = [];

foreach ($allElements as $element) {

    $element['data'] = json_decode(
        $element['data'] ?? '{}',
        true
    ) ?? [];

    $elementsByPage[$element['page_id']][] = $element;
}


if (!$album) {
    header("Location: ?page=albums");
    exit;
}

?>
<?php
if (empty($_SESSION['editor_csrf'])) {
    $_SESSION['editor_csrf'] = bin2hex(random_bytes(32));
}
?>
<div class="container">

    <div class="editor_header">
        <div class="editor_header_left">
            <a href="?page=albums" class="btn_back_editor" aria-label="Назад">
                <img src="../asset/img/btn_back.svg">
            </a>
            <div class="editor_header_info">
                <h1 class="album_title_editor"> <?= htmlspecialchars($album['title']) ?> </h1>
                <p class="album_subtitle_editor">Редактор разворота 2–3</p>
            </div>
        </div>
        <div class="editor_header_actions">
            <div class="editor_status">
                <img src="../asset/img/icon_clock.svg">
                <span class="status_text">Сохранено <?= date('d.m.Y H:i', strtotime($album['updated_at'])) ?></span>
            </div>
            <button class="btn v1 btn_save">Сохранить</button>
        </div>
    </div>

    <div class="editor_main">
        <aside class="editor_sidebar_tabs">
            <button class="sidebar_tab_vertical active" data-tab="content">
                <img src="../asset/img/icon_tab_1.svg">
                <span>Наполнение</span>
            </button>
            <button class="sidebar_tab_vertical" data-tab="materials">
                <img src="../asset/img/icon_tab_2.svg">
                <span>Материалы</span>
            </button>
        </aside>

        <aside class="editor_sidebar_content">

            <div class="sidebar_content active" id="tab-content">
                <div class="content_header">
                    <h2 class="content_title">Наполнение</h2>
                    <p class="content_subtitle">Выберите элемент и перетащите его на страницу</p>
                </div>
                <div class="content_tabs">
                    <button class="content_tab active" data-content-tab="images">
                        <img src="../asset/img/icon_tab_1.svg">
                        Изображения
                    </button>
                    <button class="content_tab" data-content-tab="cards">
                        <img src="../asset/img/icon_layout-template.svg">
                        Карточки
                    </button>
                    <button class="content_tab" data-content-tab="favorites">
                        <img src="../asset/img/icon_heart-active.svg" alt="">
                        Избранное
                    </button>
                </div>

                <div class="content_panel active" id="content-panel-images">
                    <div class="content_section_header">
                        <h3 class="content_section_title">Фотографии блюд</h3>
                        <span class="content_section_count">
                            <?= count($userPhotos) ?> фото
                        </span>
                    </div>
                    <div class="photos_grid">

                        <?php if (!empty($userPhotos)): ?>

                        <?php foreach ($userPhotos as $photo): ?>

                        <div class="photo_item" draggable="true"
                            data-id="<?= htmlspecialchars($photo['id'], ENT_QUOTES, 'UTF-8') ?>" data-type="photo"
                            data-url="<?= htmlspecialchars($photo['url'], ENT_QUOTES, 'UTF-8') ?>" data-width="200"
                            data-height="200">

                            <img src="<?= htmlspecialchars($photo['url'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($photo['name'] ?? 'Моя фотография', ENT_QUOTES, 'UTF-8') ?>"
                                draggable="false">

                            <div class="photo_check">
                                <img src="../asset/img/icon_check.svg" alt="">
                            </div>

                        </div>

                        <?php endforeach; ?>

                        <?php else: ?>

                        <p class="photos_empty" style='font-size:12px;'>
                            Здесь пока нет фотографий. Загрузите своё первое фото.
                        </p>

                        <?php endif; ?>

                    </div>
                    <button class="btn_upload_photo">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="17 8 12 3 7 8" />
                            <line x1="12" y1="3" x2="12" y2="15" />
                        </svg>
                        Загрузить своё фото
                    </button>
                </div>

                <div class="content_panel" id="content-panel-cards">
                    <div class="content_section_header">
                        <h3 class="content_section_title">Карточки для альбома</h3>
                        <span class="content_section_count">6 шаблонов</span>
                    </div>

                    <p class="cards_hint">
                        Выберите карточку и перетащите её на страницу
                    </p>

                    <div class="cards_grid">

                        <div class="card_template" draggable="true" data-id="card-recipe" data-type="card"
                            data-template="recipe" data-width="240" data-height="190">
                            <div class="card_template_preview card_preview_recipe">
                                <span class="card_preview_kicker">МОЙ РЕЦЕПТ</span>
                                <span class="card_preview_title">Название блюда</span>
                                <div class="card_preview_meta">
                                    <span>◷ 40 мин</span>
                                    <span>♧ 2 порции</span>
                                </div>
                                <div class="card_preview_lines">
                                    <i></i><i></i><i></i>
                                </div>
                            </div>
                            <span class="card_template_name">Классический рецепт</span>
                        </div>

                        <div class="card_template" draggable="true" data-id="card-photo" data-type="card"
                            data-template="photo" data-width="240" data-height="210">
                            <div class="card_template_preview card_preview_photo">
                                <div class="card_photo_placeholder">
                                    <span>Фото блюда</span>
                                </div>
                                <span class="card_preview_title">Вкусные моменты</span>
                                <div class="card_preview_lines">
                                    <i></i><i></i>
                                </div>
                            </div>
                            <span class="card_template_name">Фото и описание</span>
                        </div>

                        <div class="card_template" draggable="true" data-id="card-ingredients" data-type="card"
                            data-template="ingredients" data-width="220" data-height="200">
                            <div class="card_template_preview card_preview_ingredients">
                                <span class="card_preview_kicker">ЧТО НУЖНО</span>
                                <span class="card_preview_title">Ингредиенты</span>
                                <div class="card_preview_check">✓ Мука <i>200 г</i></div>
                                <div class="card_preview_check">✓ Молоко <i>150 мл</i></div>
                                <div class="card_preview_check">✓ Яйца <i>2 шт.</i></div>
                            </div>
                            <span class="card_template_name">Ингредиенты</span>
                        </div>

                        <div class="card_template" draggable="true" data-id="card-steps" data-type="card"
                            data-template="steps" data-width="240" data-height="210">
                            <div class="card_template_preview card_preview_steps">
                                <span class="card_preview_kicker">ГОТОВИМ</span>
                                <span class="card_preview_title">Шаги приготовления</span>
                                <div class="card_preview_step"><b>1</b><i></i></div>
                                <div class="card_preview_step"><b>2</b><i></i></div>
                                <div class="card_preview_step"><b>3</b><i></i></div>
                            </div>
                            <span class="card_template_name">По шагам</span>
                        </div>

                        <div class="card_template" draggable="true" data-id="card-tip" data-type="card"
                            data-template="tip" data-width="220" data-height="150">
                            <div class="card_template_preview card_preview_tip">
                                <span class="card_tip_icon">✳</span>
                                <span class="card_preview_kicker">МОЙ СЕКРЕТ</span>
                                <span class="card_preview_title">Совет повара</span>
                                <div class="card_preview_lines"><i></i><i></i></div>
                            </div>
                            <span class="card_template_name">Секрет повара</span>
                        </div>

                        <div class="card_template" draggable="true" data-id="card-rating" data-type="card"
                            data-template="rating" data-width="220" data-height="160">
                            <div class="card_template_preview card_preview_rating">
                                <span class="card_preview_kicker">МОЯ ОЦЕНКА</span>
                                <span class="card_preview_title">Очень вкусно!</span>
                                <div class="card_rating_stars">★★★★★</div>
                                <div class="card_preview_lines"><i></i><i></i></div>
                            </div>
                            <span class="card_template_name">Оценка блюда</span>
                        </div>

                    </div>
                </div>

                <!-- ==========================================
     ИЗБРАННЫЕ РЕЦЕПТЫ
========================================== -->

                <div class="content_panel" id="content-panel-favorites">

                    <div class="content_section_header">
                        <h3 class="content_section_title">Мои избранные рецепты</h3>

                        <span class="content_section_count">
                            <?= count($favoriteRecipes) ?> рецептов
                        </span>
                    </div>

                    <p class="cards_hint">
                        Выберите рецепт для добавления в альбом
                    </p>

                    <div class="favorite_recipes_grid">

                        <?php if (!empty($favoriteRecipes)): ?>

                        <?php foreach ($favoriteRecipes as $recipe): ?>

                        <div class="favorite_recipe_item"
                            data-recipe-id="<?= htmlspecialchars($recipe['id'], ENT_QUOTES, 'UTF-8') ?>"
                            data-title="<?= htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8') ?>"
                            data-description="<?= htmlspecialchars($recipe['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-url="<?= htmlspecialchars($recipe['cover_url'] ?: '../asset/img/placeholder.png', ENT_QUOTES, 'UTF-8') ?>"
                            data-time="<?= (int)($recipe['cooking_time'] ?? 0) ?>"
                            data-servings="<?= (int)($recipe['servings'] ?? 0) ?>"
                            data-calories="<?= (int)($recipe['calories'] ?? 0) ?>">

                            <div class="favorite_recipe_image">
                                <img src="<?= htmlspecialchars($recipe['cover_url'] ?: '../asset/img/placeholder.png', ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8') ?>"
                                    draggable="false">
                            </div>

                            <div class="favorite_recipe_info">

                                <span class="favorite_recipe_title">
                                    <?= htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8') ?>
                                </span>

                                <div class="favorite_recipe_meta">

                                    <?php if (!empty($recipe['cooking_time'])): ?>
                                    <span>
                                        <?= (int)$recipe['cooking_time'] ?> мин
                                    </span>
                                    <?php endif; ?>

                                    <?php if (!empty($recipe['servings'])): ?>
                                    <span>
                                        <?= (int)$recipe['servings'] ?> порц.
                                    </span>
                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                        <?php endforeach; ?>

                        <?php else: ?>

                        <p class="photos_empty">
                            У вас пока нет избранных рецептов.
                            Добавьте рецепты в избранное на сайте.
                        </p>

                        <?php endif; ?>

                    </div>

                </div>
            </div>

            <div class="sidebar_content" id="tab-materials">
                <div class="content_header">
                    <h2 class="content_title">Материалы</h2>
                    <p class="content_subtitle">Добавьте детали для скрапбукинга</p>
                </div>

                <div class="materials_search">
                    <img src="../asset/img/search_icon.svg">
                    <input type="text" placeholder="Поиск материалов">
                </div>

                <div class="materials_categories">
                    <button class="material_cat active" data-cat="stickers">
                        <div class="cat_icon">
                            <img src="../asset/img/icon_tab_2.svg">
                        </div>
                        <span>Стикеры</span>
                    </button>
                    <button class="material_cat" data-cat="tape">
                        <div class="cat_icon">
                            <img src="../asset/img/icon_cat_2.svg">
                        </div>
                        <span>Скотч</span>
                    </button>
                    <button class="material_cat" data-cat="stamps">
                        <div class="cat_icon">
                            <img src="../asset/img/icon_cat_3.svg">
                        </div>
                        <span>Штампы</span>
                    </button>
                    <button class="material_cat" data-cat="frames">
                        <div class="cat_icon">
                            <img src="../asset/img/icon_cat_4.svg">
                        </div>
                        <span>Рамки</span>
                    </button>
                </div>

                <div class="stickers_header">
                    <h3 class="stickers_title">Стикеры</h3>
                </div>

                <div class="stickers_grid">
                    <?php foreach ($assets as $asset): ?>
                    <div class="sticker_item" draggable="true"
                        data-id="<?= htmlspecialchars($asset['id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-type="<?= htmlspecialchars($asset['type'], ENT_QUOTES, 'UTF-8') ?>"
                        data-category="<?= htmlspecialchars($asset['category'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-name="<?= htmlspecialchars($asset['name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-url="<?= htmlspecialchars($asset['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                        data-width="<?= (int)($asset['width'] ?? 100) ?>"
                        data-height="<?= (int)($asset['height'] ?? 100) ?>">
                        <div class="sticker_preview">
                            <img src="<?= htmlspecialchars($asset['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($asset['name'], ENT_QUOTES, 'UTF-8') ?>" draggable="false">
                        </div>
                        <span class="sticker_name">
                            <?= htmlspecialchars($asset['name'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="materials_hint">
                    <img src="../asset/img/materials_hint.svg">
                    Перетащите выбранный элемент на страницу
                </div>
            </div>
        </aside>

        <div class="editor_canvas">
            <div class="canvas_toolbar">
                <div class="canvas_toolbar_left">
                    <button class="canvas_tool" title="Отменить">
                        <img src="../asset/img/icon_undo_left.png">
                    </button>
                    <button class="canvas_tool" title="Повторить">
                        <img src="../asset/img/icon_redo_right.svg">
                    </button>
                </div>
                <div class="canvas_toolbar_center">
                    <button class="canvas_tool_text active">
                        <img src="../asset/img/icon_mouse.svg">
                        Выбор
                    </button>
                    <button class="canvas_tool_text">
                        <img src="../asset/img/icon_hand.svg">
                        Рука
                    </button>
                </div>
                <div class="canvas_zoom">
                    <button class="zoom_btn" id="zoom_out">−</button>
                    <span class="zoom_level">85%</span>
                    <button class="zoom_btn" id="zoom_in">+</button>
                </div>
            </div>

            <div class="canvas_area">
                <div class="book_spread">

                    <?php foreach ($pages as $pageItem): ?>

                    <?php
                        $pageNumber = (int)$pageItem['page_number'];

                        if ($pageNumber < 2) {
                            continue;
                        }

                        $pageSide = ($pageNumber % 2 === 0) ? 'left' : 'right';
                        $spreadIndex = (int)floor(($pageNumber - 2) / 2);
                        ?>

                    <div class="book_page book_page--<?= $pageSide ?>"
                        data-page-id="<?= htmlspecialchars($pageItem['id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-page-number="<?= $pageNumber ?>" data-spread-index="<?= $spreadIndex ?>"
                        style="display: <?= $spreadIndex === 0 ? 'block' : 'none' ?>;">

                        <div class="page_number page_number--<?= $pageSide ?>">
                            <?= $pageNumber ?>
                        </div>


                        <?php foreach (($elementsByPage[$pageItem['id']] ?? []) as $element): ?>

                        <?php
        $elementUrl = $element['image_url'] ?? '';
        $elementType = $element['type'] ?? '';

        // Декодируем сохранённые данные карточки
        $elementData = $element['data'] ?? [];

        if (is_string($elementData)) {
            $elementData = json_decode($elementData, true) ?? [];
        }

        if (!is_array($elementData)) {
            $elementData = [];
        }

        $cardTemplate = $elementData['template'] ?? '';

        $cardTitles = [
            'recipe' => 'Название блюда',
            'photo' => 'Вкусные моменты',
            'ingredients' => 'Ингредиенты',
            'steps' => 'Шаги приготовления',
            'tip' => 'Совет повара',
            'rating' => 'Моя оценка'
        ];

        // Проверяем изображения, но не пропускаем карточки
        if ($elementType !== 'card' && $elementUrl === '') {
            continue;
        }

        // Проверяем шаблон карточки
        if (
            $elementType === 'card' &&
            !isset($cardTitles[$cardTemplate])
        ) {
            continue;
        }

        $isCard = ($elementType === 'card');
    ?>

                        <div class="placed_element <?= $isCard ? 'placed_card' : '' ?>"
                            data-id="<?= htmlspecialchars($element['id'], ENT_QUOTES, 'UTF-8') ?>"
                            data-asset-id="<?= htmlspecialchars($element['source_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-type="<?= htmlspecialchars($elementType, ENT_QUOTES, 'UTF-8') ?>"
                            data-template="<?= htmlspecialchars($cardTemplate, ENT_QUOTES, 'UTF-8') ?>"
                            data-url="<?= htmlspecialchars($elementUrl, ENT_QUOTES, 'UTF-8') ?>"
                            data-recipe-id="<?= htmlspecialchars($elementData['recipe_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-recipe-url="<?= htmlspecialchars($elementData['cover_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-recipe-calories="<?= htmlspecialchars($elementData['calories'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-x="<?= (float)$element['position_x'] ?>" data-y="<?= (float)$element['position_y'] ?>"
                            data-width="<?= (float)$element['width'] ?>" data-height="<?= (float)$element['height'] ?>"
                            data-rotation="<?= (float)$element['rotation'] ?>" style="
                            left: <?= (float)$element['position_x'] ?>px;
                            top: <?= (float)$element['position_y'] ?>px;
                            width: <?= (float)$element['width'] ?>px;
                            height: <?= (float)$element['height'] ?>px;
                            transform: rotate(<?= (float)$element['rotation'] ?>deg);
                            z-index: <?= (int)$element['z_index'] ?>;
                        ">


                            <?php if ($isCard): ?>

                            <div
                                class="recipe_card recipe_card--<?= htmlspecialchars($cardTemplate, ENT_QUOTES, 'UTF-8') ?>">

                                <div class="recipe_card_title" contenteditable="true" spellcheck="false">
                                    <?= htmlspecialchars($elementData['title'] ?? $cardTitles[$cardTemplate], ENT_QUOTES, 'UTF-8') ?>
                                </div>

                                <div class="recipe_card_subtitle" contenteditable="true" spellcheck="false">
                                    <?= htmlspecialchars($elementData['text'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </div>

                                <?php if ($cardTemplate === 'recipe'): ?>

                                <div class="recipe_card_editor_meta">

                                    <div class="recipe_card_time" contenteditable="true" spellcheck="false">
                                        <?= htmlspecialchars($elementData['time'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>

                                    <div class="recipe_card_servings" contenteditable="true" spellcheck="false">
                                        <?= htmlspecialchars($elementData['servings'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </div>

                                </div>

                                <?php endif; ?>

                            </div>

                            <?php else: ?>

                            <img src="<?= htmlspecialchars($elementUrl, ENT_QUOTES, 'UTF-8') ?>" alt=""
                                draggable="false">

                            <?php endif; ?>

                            <div class="element_handle element_handle--nw" data-action="nw"></div>
                            <div class="element_handle element_handle--ne" data-action="ne"></div>
                            <div class="element_handle element_handle--sw" data-action="sw"></div>
                            <div class="element_handle element_handle--se" data-action="se"></div>
                            <div class="element_handle element_handle--rotate" data-action="rotate"></div>

                        </div>

                        <?php endforeach; ?>


                    </div>

                    <?php endforeach; ?>

                </div>

            </div>
        </div>
    </div>

    <div class="editor_footer">
        <button class="nav_btn" id="prev_spread">
            <img src="../asset/img/icon_chevron_right.svg" alt="" srcset="">
        </button>
        <span class="spread_info">2–3 из 3</span>
        <button class="nav_btn" id="next_spread">
            <img src="../asset/img/icon_chevron_left.svg" alt="" srcset="">

        </button>
        <button class="btn_add_spread">
            <img src="../asset/img/icon_plus.svg">
            Добавить разворот
        </button>
    </div>
</div>

<script>
const album_config = <?= json_encode(
    [
        'title' => $album['title'],
        'pages' => $pages,
        'csrf_token' => $_SESSION['editor_csrf']
    ],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
) ?>;
</script>