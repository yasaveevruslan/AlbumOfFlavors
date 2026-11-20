<?php
    global $connect;
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: ?page=main&auth_required=1');
        exit;
    }
    
    $active_nav = 'my_recipes';

    $stmt_cats = $connect->query("SELECT id, name FROM categories ORDER BY name ASC");
    $all_categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $cooking_time = !empty($_POST['cooking_time']) ? (int)$_POST['cooking_time'] : null;
        $servings = !empty($_POST['servings']) ? (int)$_POST['servings'] : null;
        $calories = !empty($_POST['calories']) ? (int)$_POST['calories'] : null;
        $action = $_POST['action'] ?? 'draft';
        $selected_categories = $_POST['categories'] ?? [];
        $tags_input = trim($_POST['tags_input'] ?? '');

        if ($title === '') {
            $_SESSION['error_message'] = 'Введите название рецепта';
            header('Location: ?page=create_recipe');
            exit;
        }

        if (empty($selected_categories)) {
            $_SESSION['error_message'] = 'Выберите хотя бы одну категорию';
            header('Location: ?page=create_recipe');
            exit;
        }

        if (!in_array($action, ['draft', 'moderation'])) {
            $action = 'draft';
        }

        $recipe_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $cover_url = null;
        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['cover_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error_message'] = 'Ошибка загрузки обложки (код: ' . $_FILES['cover_file']['error'] . '). Проверьте лимиты php.ini.';
                header('Location: ?page=create_recipe');
                exit;
            }

            $file = $_FILES['cover_file'];
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            
            if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/asset/img/recipes/';
                
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        $_SESSION['error_message'] = 'Не удалось создать папку: ' . $upload_dir;
                        header('Location: ?page=create_recipe');
                        exit;
                    }
                }
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_name = 'recipe_' . $recipe_id . '_' . time() . '.' . strtolower($ext);
                $destination = $upload_dir . $new_name;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $cover_url = 'asset/img/recipes/' . $new_name;
                } else {
                    $_SESSION['error_message'] = 'Не удалось сохранить обложку. Проверьте права на папку: ' . $upload_dir;
                    header('Location: ?page=create_recipe');
                    exit;
                }
            } else {
                $_SESSION['error_message'] = 'Недопустимый формат или размер обложки (макс. 5 МБ, JPG/PNG/WEBP)';
                header('Location: ?page=create_recipe');
                exit;
            }
        }
        $connect->beginTransaction();
        try {
            $stmt = $connect->prepare("
                INSERT INTO recipes (id, title, description, cover_url, author_id, cooking_time, servings, calories, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$recipe_id, $title, $description, $cover_url, $_SESSION['user_id'], $cooking_time, $servings, $calories, $action]);

            if (!empty($selected_categories)) {
                $stmt_cat = $connect->prepare("INSERT INTO recipe_categories (recipe_id, category_id) VALUES (?, ?)");
                $valid_cat_ids = array_column($all_categories, 'id');
                foreach ($selected_categories as $cat_id) {
                    if (in_array($cat_id, $valid_cat_ids)) {
                        $stmt_cat->execute([$recipe_id, $cat_id]);
                    }
                }
            }

            if ($tags_input !== '') {
                $tag_names = array_unique(array_map('trim', explode(',', $tags_input)));
                $tag_names = array_filter($tag_names);

                $stmt_tag_check = $connect->prepare("SELECT id FROM tags WHERE name = ?");
                $stmt_tag_insert = $connect->prepare("INSERT INTO tags (id, name) VALUES (?, ?)");
                $stmt_recipe_tag = $connect->prepare("INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (?, ?)");

                foreach ($tag_names as $tag_name) {
                    if (mb_strlen($tag_name) < 2) continue;

                    $stmt_tag_check->execute([$tag_name]);
                    $existing_tag = $stmt_tag_check->fetch(PDO::FETCH_ASSOC);

                    if ($existing_tag) {
                        $tag_id = $existing_tag['id'];
                    } else {
                        $tag_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                        $stmt_tag_insert->execute([$tag_id, $tag_name]);
                    }

                    $stmt_recipe_tag->execute([$recipe_id, $tag_id]);
                }
            }

            if (!empty($_POST['ing_name']) && is_array($_POST['ing_name'])) {
                $stmt_ing = $connect->prepare("INSERT INTO recipe_ingredients (id, recipe_id, name, amount, order_index) VALUES (?, ?, ?, ?, ?)");
                foreach ($_POST['ing_name'] as $index => $name) {
                    $name = trim($name);
                    if ($name !== '') {
                        $amount = trim($_POST['ing_amount'][$index] ?? '');
                        $ing_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
                        $stmt_ing->execute([$ing_id, $recipe_id, $name, $amount, $index + 1]);
                    }
                }
            }

            if (!empty($_POST['step_text']) && is_array($_POST['step_text'])) {
                $stmt_step = $connect->prepare("INSERT INTO recipe_steps (id, recipe_id, step_number, text, image_url) VALUES (?, ?, ?, ?, ?)");
                $step_num = 1;
                
                foreach ($_POST['step_text'] as $index => $text) {
                    $text = trim($text);
                    if ($text !== '') {
                        $step_image_url = null;
                        
                        if (!empty($_FILES['step_image']['name'][$index]) && $_FILES['step_image']['error'][$index] !== UPLOAD_ERR_NO_FILE) {
                            
                            if ($_FILES['step_image']['error'][$index] !== UPLOAD_ERR_OK) {
                                $_SESSION['error_message'] = 'Ошибка загрузки изображения шага #' . $step_num . ' (код: ' . $_FILES['step_image']['error'][$index] . '). Возможно, превышен лимит php.ini.';
                                $connect->rollBack();
                                header('Location: ?page=create_recipe');
                                exit;
                            }
                            
                            $file = [
                                'name' => $_FILES['step_image']['name'][$index],
                                'type' => $_FILES['step_image']['type'][$index],
                                'tmp_name' => $_FILES['step_image']['tmp_name'][$index],
                                'size' => $_FILES['step_image']['size'][$index]
                            ];
                            
                            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                            if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/asset/img/recipes/steps/';
                                
                                if (!is_dir($upload_dir)) {
                                    if (!mkdir($upload_dir, 0755, true)) {
                                        $_SESSION['error_message'] = 'Не удалось создать папку для шагов: ' . $upload_dir;
                                        $connect->rollBack();
                                        header('Location: ?page=create_recipe');
                                        exit;
                                    }
                                }
                                
                                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                                $new_name = 'step_' . $recipe_id . '_' . $step_num . '_' . time() . '_' . $index . '.' . strtolower($ext);
                                $destination = $upload_dir . $new_name;
                                
                                if (move_uploaded_file($file['tmp_name'], $destination)) {
                                    $step_image_url = 'asset/img/recipes/steps/' . $new_name;
                                } else {
                                    $_SESSION['error_message'] = 'Не удалось сохранить изображение шага #' . $step_num . '. Проверьте права на папку: ' . $upload_dir;
                                    $connect->rollBack();
                                    header('Location: ?page=create_recipe');
                                    exit;
                                }
                            } else {
                                $_SESSION['error_message'] = 'Изображение шага #' . $step_num . ': недопустимый формат или размер > 5 МБ';
                                $connect->rollBack();
                                header('Location: ?page=create_recipe');
                                exit;
                            }
                        }

                        $step_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', 
                            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), 
                            mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, 
                            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
                        $stmt_step->execute([$step_id, $recipe_id, $step_num, $text, $step_image_url]);
                        $step_num++;
                    }
                }
            }
            $connect->commit();

            $_SESSION['success_message'] = $action === 'draft' ? 'Рецепт успешно сохранен как черновик!' : 'Рецепт успешно отправлен на модерацию!';
            header('Location: ?page=my_recipes');
            exit;

        } catch (Exception $e) {
            $connect->rollBack();
            $_SESSION['error_message'] = 'Ошибка при сохранении: ' . $e->getMessage();
            header('Location: ?page=create_recipe');
            exit;
        }
    }
    
    $posted_categories = $_POST['categories'] ?? [];
    $posted_tags = $_POST['tags_input'] ?? '';
?>

<div class="container">
    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <a href="?page=profile">Личный кабинет</a>
        <a href="?page=my_recipes">Мои рецепты</a>
        <span>Создание рецепта</span>
    </nav>

    <div class="profile_container">
        <?php include __DIR__ . '/../../includes/profile_sidebar.php'; ?>

        <div class="profile_content">
            <div class="my_recipes_header">
                <h2 class="section_title">Новый рецепт</h2>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
            <div class="form_message error" style="display: block; margin-bottom: 20px;">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <form class="admin_form" method="POST" enctype="multipart/form-data">

                <div class="form_group">
                    <label>Название рецепта <span class="required">*</span></label>
                    <input type="text" name="title" placeholder="Например: Борщ украинский"
                        value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>

                <div class="form_group">
                    <label>Категории <span class="required">*</span></label>
                    <div class="category_tags_selector">
                        <?php if (!empty($all_categories)): ?>
                        <?php foreach ($all_categories as $cat): ?>
                        <label
                            class="category_checkbox_tag <?= in_array($cat['id'], $posted_categories) ? 'selected' : '' ?>">
                            <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($cat['id']) ?>"
                                <?= in_array($cat['id'], $posted_categories) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($cat['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form_group">
                    <label>Описание</label>
                    <textarea name="description" rows="4"
                        placeholder="Краткое описание блюда, история или советы..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form_row">
                    <div class="form_group">
                        <label>Время (мин)</label>
                        <input type="number" name="cooking_time" min="1" placeholder="30"
                            value="<?= htmlspecialchars($_POST['cooking_time'] ?? '') ?>">
                    </div>
                    <div class="form_group">
                        <label>Порции</label>
                        <input type="number" name="servings" min="1" placeholder="4"
                            value="<?= htmlspecialchars($_POST['servings'] ?? '') ?>">
                    </div>
                    <div class="form_group">
                        <label>Калории (ккал)</label>
                        <input type="number" name="calories" min="0" placeholder="250"
                            value="<?= htmlspecialchars($_POST['calories'] ?? '') ?>">
                    </div>
                </div>

                <div class="form_group">
                    <label>Обложка рецепта</label>
                    <div class="cover_file_wrapper">
                        <button type="button" class="btn_file" id="coverBtn">Выберите файл</button>
                        <span class="file_name" id="coverName">Файл не выбран</span>
                        <input type="file" id="coverInput" name="cover_file" accept="image/*" hidden>
                    </div>
                </div>

                <div class="form_card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <h3 class="form_card_title" style="margin:0;">Ингредиенты</h3>
                        <button type="button" class="btn v1" id="addIngredientBtn"
                            style="padding: 6px 12px; font-size: 13px;">
                            + Добавить
                        </button>
                    </div>
                    <div id="ingredientsContainer">
                        <?php 
                        $post_ing_names = $_POST['ing_name'] ?? [''];
                        $post_ing_amounts = $_POST['ing_amount'] ?? [''];
                        foreach ($post_ing_names as $index => $name): 
                        ?>
                        <div class="ingredient-row form_row">
                            <div class="form_group" style="flex:2;">
                                <input type="text" name="ing_name[]" placeholder="Название (напр. Картофель)"
                                    value="<?= htmlspecialchars($name) ?>">
                            </div>
                            <div class="form_group" style="flex:1;">
                                <input type="text" name="ing_amount[]" placeholder="Кол-во (напр. 500 г)"
                                    value="<?= htmlspecialchars($post_ing_amounts[$index] ?? '') ?>">
                            </div>
                            <button type="button" class="btn_remove_row" title="Удалить">×</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form_card">
                    <h3 class="form_card_title" style="margin:0 0 16px 0;">Теги</h3>
                    <div class="tag-input-wrapper" id="tagInputWrapper">
                        <div class="tags-display" id="tagsDisplay"></div>
                        <input type="text" id="tagInput" placeholder="Введите тег и нажмите Enter..."
                            autocomplete="off">
                    </div>
                    <input type="hidden" name="tags_input" id="tagsHiddenInput"
                        value="<?= htmlspecialchars($posted_tags) ?>">
                    <small style="color:#888; display:block; margin-top:6px;">Нажмите Enter или запятую, чтобы добавить
                        тег. Нажмите × чтобы удалить.</small>
                </div>

                <div class="form_card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <h3 class="form_card_title" style="margin:0;">Шаги приготовления</h3>
                        <button type="button" class="btn v1" id="addStepBtn"
                            style="padding: 6px 12px; font-size: 13px;">
                            + Добавить шаг
                        </button>
                    </div>
                    <div id="stepsContainer">
                        <?php 
                        $post_steps = $_POST['step_text'] ?? [''];
                        foreach ($post_steps as $index => $text): 
                        ?>
                        <div class="step-row"
                            style="position:relative; padding: 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 15px; background: #fafafa;">
                            <div class="form_group">
                                <label>Шаг <?= $index + 1 ?></label>
                                <textarea name="step_text[]" rows="3" placeholder="Опишите действие..."
                                    style="width: 100%;"><?= htmlspecialchars($text) ?></textarea>
                            </div>
                            <div class="form_group" style="margin-top: 12px;">
                                <label
                                    style="font-size: 13px; color: #666; margin-bottom: 4px; display: block;">Изображение
                                    шага (необязательно)</label>
                                <div class="cover_file_wrapper">
                                    <button type="button" class="btn_file step-file-btn">Выберите файл</button>
                                    <span class="file_name step-file-name">Файл не выбран</span>
                                    <input type="file" name="step_image[]" accept="image/*" class="step-file-input"
                                        hidden>
                                </div>
                            </div>
                            <button type="button" class="btn_remove_row" title="Удалить"
                                style="top: 15px; right: 15px;">×</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form_actions">
                    <button type="submit" class="btn v1 btn_outline" name="action" value="draft">Сохранить как
                        черновик</button>
                    <button type="submit" class="btn v1" name="action" value="moderation">Отправить на
                        модерацию</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const coverBtn = document.getElementById('coverBtn');
    const coverInput = document.getElementById('coverInput');
    const coverName = document.getElementById('coverName');
    coverBtn?.addEventListener('click', () => coverInput?.click());
    coverInput?.addEventListener('change', function() {
        coverName.textContent = this.files.length > 0 ? this.files[0].name : 'Файл не выбран';
    });

    document.querySelectorAll('.category_checkbox_tag input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            this.closest('.category_checkbox_tag').classList.toggle('selected', this.checked);
        });
    });

    document.getElementById('addIngredientBtn')?.addEventListener('click', () => {
        const container = document.getElementById('ingredientsContainer');
        const div = document.createElement('div');
        div.className = 'ingredient-row form_row';
        div.innerHTML = `
            <div class="form_group" style="flex:2;"><input type="text" name="ing_name[]" placeholder="Название" required></div>
            <div class="form_group" style="flex:1;"><input type="text" name="ing_amount[]" placeholder="Кол-во"></div>
            <button type="button" class="btn_remove_row" title="Удалить">×</button>
        `;
        container.appendChild(div);
    });

    document.getElementById('addStepBtn')?.addEventListener('click', () => {
        const container = document.getElementById('stepsContainer');
        const stepCount = container.children.length + 1;
        const div = document.createElement('div');
        div.className = 'step-row';
        div.style.cssText =
            'position:relative; padding: 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 15px; background: #fafafa;';
        div.innerHTML = `
            <div class="form_group"><label>Шаг ${stepCount}</label><textarea name="step_text[]" rows="3" placeholder="Опишите действие..." required style="width: 100%;"></textarea></div>
            <div class="form_group" style="margin-top: 12px;">
                <label style="font-size: 13px; color: #666; margin-bottom: 4px; display: block;">Изображение шага (необязательно)</label>
                <div class="cover_file_wrapper">
                    <button type="button" class="btn_file step-file-btn">Выберите файл</button>
                    <span class="file_name step-file-name">Файл не выбран</span>
                    <input type="file" name="step_image[]" accept="image/*" class="step-file-input" hidden>
                </div>
            </div>
            <button type="button" class="btn_remove_row" title="Удалить" style="top: 15px; right: 15px;">×</button>
        `;
        container.appendChild(div);
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn_remove_row')) {
            const row = e.target.closest('.ingredient-row, .step-row');
            const container = row.parentElement;
            if (row && container.querySelectorAll('.ingredient-row, .step-row').length > 1) {
                row.remove();
                if (container.id === 'stepsContainer') {
                    container.querySelectorAll('.step-row').forEach((stepRow, index) => {
                        const label = stepRow.querySelector('label');
                        if (label) label.textContent = `Шаг ${index + 1}`;
                    });
                }
            }
        }
    });

    document.getElementById('stepsContainer').addEventListener('click', function(e) {
        if (e.target.classList.contains('step-file-btn')) {
            e.target.nextElementSibling.nextElementSibling.click();
        }
    });
    document.getElementById('stepsContainer').addEventListener('change', function(e) {
        if (e.target.classList.contains('step-file-input')) {
            e.target.previousElementSibling.textContent = e.target.files.length > 0 ? e.target.files[0]
                .name : 'Файл не выбран';
        }
    });

    const tagInput = document.getElementById('tagInput');
    const tagsDisplay = document.getElementById('tagsDisplay');
    const tagsHiddenInput = document.getElementById('tagsHiddenInput');
    let tags = [];

    const initialTagsStr = "<?= htmlspecialchars($posted_tags) ?>";
    if (initialTagsStr) {
        initialTagsStr.split(',').forEach(t => {
            if (t.trim()) addTag(t.trim());
        });
    }

    function addTag(name) {
        name = name.trim();
        if (!name || tags.includes(name)) return;
        tags.push(name);
        renderTags();
    }

    function removeTag(name) {
        tags = tags.filter(t => t !== name);
        renderTags();
    }

    function renderTags() {
        tagsDisplay.innerHTML = '';
        tags.forEach(tag => {
            const chip = document.createElement('span');
            chip.className = 'tag-chip';
            chip.innerHTML =
                `${tag} <button type="button" class="tag-remove" data-tag="${tag}">×</button>`;
            tagsDisplay.appendChild(chip);
        });
        tagsHiddenInput.value = tags.join(',');
    }

    tagInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addTag(this.value);
            this.value = '';
        }
        if (e.key === 'Backspace' && this.value === '' && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });

    tagsDisplay.addEventListener('click', function(e) {
        if (e.target.classList.contains('tag-remove')) {
            removeTag(e.target.dataset.tag);
        }
    });
});
</script>