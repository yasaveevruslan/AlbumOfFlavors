<?php
    global $connect;
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: ?page=main&auth_required=1');
        exit;
    }

    $recipe_id = $_GET['id'] ?? '';
    
    $stmt = $connect->prepare("SELECT * FROM recipes WHERE id = ? AND author_id = ? AND deleted_at IS NULL");
    $stmt->execute([$recipe_id, $_SESSION['user_id']]);
    $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipe) {
        $_SESSION['error_message'] = 'Рецепт не найден или у вас нет прав на его редактирование';
        header('Location: ?page=my_recipes');
        exit;
    }
    
    $active_nav = 'my_recipes';

    $stmt_cats = $connect->prepare("SELECT category_id FROM recipe_categories WHERE recipe_id = ?");
    $stmt_cats->execute([$recipe_id]);
    $recipe_cat_ids = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);

    $stmt_all_cats = $connect->query("SELECT id, name FROM categories ORDER BY name ASC");
    $all_categories = $stmt_all_cats->fetchAll(PDO::FETCH_ASSOC);

    $stmt_tags = $connect->prepare("SELECT t.name FROM tags t INNER JOIN recipe_tags rt ON t.id = rt.tag_id WHERE rt.recipe_id = ?");
    $stmt_tags->execute([$recipe_id]);
    $recipe_tags = $stmt_tags->fetchAll(PDO::FETCH_COLUMN);
    $tags_string = implode(', ', $recipe_tags);

    $stmt_ing = $connect->prepare("SELECT name, amount FROM recipe_ingredients WHERE recipe_id = ? ORDER BY order_index ASC");
    $stmt_ing->execute([$recipe_id]);
    $ingredients = $stmt_ing->fetchAll(PDO::FETCH_ASSOC);

    $stmt_steps = $connect->prepare("SELECT text, image_url FROM recipe_steps WHERE recipe_id = ? ORDER BY step_number ASC");
    $stmt_steps->execute([$recipe_id]);
    $steps = $stmt_steps->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $cooking_time = !empty($_POST['cooking_time']) ? (int)$_POST['cooking_time'] : null;
        $servings = !empty($_POST['servings']) ? (int)$_POST['servings'] : null;
        $calories = !empty($_POST['calories']) ? (int)$_POST['calories'] : null;
        $action = $_POST['action'] ?? $recipe['status'];
        $selected_categories = $_POST['categories'] ?? [];
        $tags_input = trim($_POST['tags_input'] ?? '');

        if ($title === '') {
            $_SESSION['error_message'] = 'Введите название рецепта';
            header("Location: ?page=edit_recipe&id=$recipe_id");
            exit;
        }

        if (empty($selected_categories)) {
            $_SESSION['error_message'] = 'Выберите хотя бы одну категорию';
            header("Location: ?page=edit_recipe&id=$recipe_id");
            exit;
        }

        if (!in_array($action, ['draft', 'published', 'moderation', 'hidden'])) {
            $action = $recipe['status'];
        }

        $cover_url = $recipe['cover_url'];
        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['cover_file'];
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $upload_dir = __DIR__ . '/../../asset/img/recipes/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_name = 'recipe_' . $recipe_id . '_' . time() . '.' . strtolower($ext);
                
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                    $cover_url = 'asset/img/recipes/' . $new_name;
                }
            }
        }

        $connect->beginTransaction();
        try {
            $stmt = $connect->prepare("
                UPDATE recipes 
                SET title = ?, description = ?, cover_url = ?, cooking_time = ?, servings = ?, calories = ?, status = ?
                WHERE id = ? AND author_id = ?
            ");
            $stmt->execute([$title, $description, $cover_url, $cooking_time, $servings, $calories, $action, $recipe_id, $_SESSION['user_id']]);

            $stmt_del_cat = $connect->prepare("DELETE FROM recipe_categories WHERE recipe_id = ?");
            $stmt_del_cat->execute([$recipe_id]);
            
            $stmt_ins_cat = $connect->prepare("INSERT INTO recipe_categories (recipe_id, category_id) VALUES (?, ?)");
            $valid_cat_ids = array_column($all_categories, 'id');
            foreach ($selected_categories as $cat_id) {
                if (in_array($cat_id, $valid_cat_ids)) {
                    $stmt_ins_cat->execute([$recipe_id, $cat_id]);
                }
            }

            $stmt_del_tags = $connect->prepare("DELETE FROM recipe_tags WHERE recipe_id = ?");
            $stmt_del_tags->execute([$recipe_id]);

            if ($tags_input !== '') {
                $tag_names = array_unique(array_map('trim', explode(',', $tags_input)));
                $tag_names = array_filter($tag_names);

                $stmt_tag_check = $connect->prepare("SELECT id FROM tags WHERE name = ?");
                $stmt_tag_insert = $connect->prepare("INSERT INTO tags (id, name) VALUES (?, ?)");
                $stmt_recipe_tag = $connect->prepare("INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (?, ?)");

                foreach ($tag_names as $tag_name) {
                    if (mb_strlen($tag_name) >= 2) {
                        $stmt_tag_check->execute([$tag_name]);
                        $existing_tag = $stmt_tag_check->fetch(PDO::FETCH_ASSOC);

                        if ($existing_tag) {
                            $tag_id = $existing_tag['id'];
                        } else {
                            $tag_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
                            $stmt_tag_insert->execute([$tag_id, $tag_name]);
                        }
                        $stmt_recipe_tag->execute([$recipe_id, $tag_id]);
                    }
                }
            }

            $stmt_del_ing = $connect->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?");
            $stmt_del_ing->execute([$recipe_id]);

            if (!empty($_POST['ing_name']) && is_array($_POST['ing_name'])) {
                $stmt_ins_ing = $connect->prepare("INSERT INTO recipe_ingredients (id, recipe_id, name, amount, order_index) VALUES (?, ?, ?, ?, ?)");
                foreach ($_POST['ing_name'] as $index => $name) {
                    $name = trim($name);
                    if ($name !== '') {
                        $amount = trim($_POST['ing_amount'][$index] ?? '');
                        $ing_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
                        $stmt_ins_ing->execute([$ing_id, $recipe_id, $name, $amount, $index + 1]);
                    }
                }
            }

            $stmt_del_steps = $connect->prepare("DELETE FROM recipe_steps WHERE recipe_id = ?");
            $stmt_del_steps->execute([$recipe_id]);

            if (!empty($_POST['step_text']) && is_array($_POST['step_text'])) {
                $stmt_ins_step = $connect->prepare("INSERT INTO recipe_steps (id, recipe_id, step_number, text, image_url) VALUES (?, ?, ?, ?, ?)");
                $step_num = 1;
                
                foreach ($_POST['step_text'] as $index => $text) {
                    $text = trim($text);
                    if ($text !== '') {
                        $step_image_url = trim($_POST['step_image_old'][$index] ?? '');
                        
                        if (!empty($_FILES['step_image']['name'][$index]) && $_FILES['step_image']['error'][$index] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['step_image']['name'][$index],
                                'type' => $_FILES['step_image']['type'][$index],
                                'tmp_name' => $_FILES['step_image']['tmp_name'][$index],
                                'size' => $_FILES['step_image']['size'][$index]
                            ];
                            
                            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                            if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                                $upload_dir = __DIR__ . '/../../asset/img/recipes/steps/';
                                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                                
                                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                                $new_name = 'step_' . $recipe_id . '_' . $step_num . '_' . time() . '.' . strtolower($ext);
                                
                                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                                    $step_image_url = 'asset/img/recipes/steps/' . $new_name;
                                }
                            }
                        }

                        $step_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
                        $stmt_ins_step->execute([$step_id, $recipe_id, $step_num, $text, $step_image_url]);
                        $step_num++;
                    }
                }
            }

            $connect->commit();

            $_SESSION['success_message'] = 'Рецепт успешно обновлен!';
            header('Location: ?page=my_recipes');
            exit;

        } catch (Exception $e) {
            $connect->rollBack();
            $_SESSION['error_message'] = 'Ошибка при сохранении: ' . $e->getMessage();
            header("Location: ?page=edit_recipe&id=$recipe_id");
            exit;
        }
    }
?>

<div class="container">
    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <a href="?page=profile">Личный кабинет</a>
        <a href="?page=my_recipes">Мои рецепты</a>
        <span>Редактирование рецепта</span>
    </nav>

    <div class="profile_container">
        <?php include __DIR__ . '/../../includes/profile_sidebar.php'; ?>

        <div class="profile_content">
            <div class="my_recipes_header">
                <h2 class="section_title">Редактирование рецепта</h2>
                <a href="?page=my_recipes" class="btn v1 btn_outline">Отмена</a>
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
                    <input type="text" name="title" required value="<?= htmlspecialchars($recipe['title']) ?>">
                </div>

                <div class="form_group">
                    <label>Категории <span class="required">*</span></label>
                    <div class="category_tags_selector">
                        <?php foreach ($all_categories as $cat): ?>
                            <label class="category_checkbox_tag <?= in_array($cat['id'], $recipe_cat_ids) ? 'selected' : '' ?>">
                                <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($cat['id']) ?>"
                                    <?= in_array($cat['id'], $recipe_cat_ids) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($cat['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form_group">
                    <label>Описание</label>
                    <textarea name="description" rows="4"><?= htmlspecialchars($recipe['description'] ?? '') ?></textarea>
                </div>

                <div class="form_row">
                    <div class="form_group">
                        <label>Время (мин)</label>
                        <input type="number" name="cooking_time" min="1" value="<?= htmlspecialchars($recipe['cooking_time'] ?? '') ?>">
                    </div>
                    <div class="form_group">
                        <label>Порции</label>
                        <input type="number" name="servings" min="1" value="<?= htmlspecialchars($recipe['servings'] ?? '') ?>">
                    </div>
                    <div class="form_group">
                        <label>Калории (ккал)</label>
                        <input type="number" name="calories" min="0" value="<?= htmlspecialchars($recipe['calories'] ?? '') ?>">
                    </div>
                </div>

                <div class="form_group">
                    <label>Обложка рецепта</label>
                    <?php if ($recipe['cover_url']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?= htmlspecialchars($recipe['cover_url']) ?>" alt="Текущая обложка" style="max-width: 200px; border-radius: 8px; border: 1px solid #ddd;">
                        </div>
                    <?php endif; ?>
                    <div class="cover_file_wrapper">
                        <button type="button" class="btn_file" id="coverBtn">Заменить файл</button>
                        <span class="file_name" id="coverName">Файл не выбран</span>
                        <input type="file" id="coverInput" name="cover_file" accept="image/*" hidden>
                    </div>
                </div>

                <div class="form_card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <h3 class="form_card_title" style="margin:0;">Ингредиенты</h3>
                        <button type="button" class="btn v1" id="addIngredientBtn" style="padding: 6px 12px; font-size: 13px;">+ Добавить</button>
                    </div>
                    <div id="ingredientsContainer">
                        <?php foreach ($ingredients as $ing): ?>
                        <div class="ingredient-row form_row">
                            <div class="form_group" style="flex:2;">
                                <input type="text" name="ing_name[]" value="<?= htmlspecialchars($ing['name']) ?>" required>
                            </div>
                            <div class="form_group" style="flex:1;">
                                <input type="text" name="ing_amount[]" value="<?= htmlspecialchars($ing['amount'] ?? '') ?>">
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
                        <input type="text" id="tagInput" placeholder="Введите тег и нажмите Enter..." autocomplete="off">
                    </div>
                    <input type="hidden" name="tags_input" id="tagsHiddenInput" value="<?= htmlspecialchars($tags_string) ?>">
                    <small style="color:#888; display:block; margin-top:6px;">Нажмите Enter или запятую, чтобы добавить тег.</small>
                </div>

                <div class="form_card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <h3 class="form_card_title" style="margin:0;">Шаги приготовления</h3>
                        <button type="button" class="btn v1" id="addStepBtn" style="padding: 6px 12px; font-size: 13px;">+ Добавить шаг</button>
                    </div>
                    <div id="stepsContainer">
                        <?php foreach ($steps as $index => $step): ?>
                        <div class="step-row" style="position:relative; padding: 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 15px; background: #fafafa;">
                            <div class="form_group">
                                <label>Шаг <?= $index + 1 ?></label>
                                <textarea name="step_text[]" rows="3" required style="width: 100%;"><?= htmlspecialchars($step['text']) ?></textarea>
                            </div>
                            
                            <?php if (!empty($step['image_url'])): ?>
                                <div style="margin: 8px 0;">
                                    <img src="<?= htmlspecialchars($step['image_url']) ?>" alt="Шаг <?= $index + 1 ?>" style="max-width: 150px; border-radius: 6px; border: 1px solid #ddd;">
                                </div>
                            <?php endif; ?>

                            <div class="form_group" style="margin-top: 12px;">
                                <label style="font-size: 13px; color: #666; margin-bottom: 4px; display: block;">Заменить изображение шага (необязательно)</label>
                                <div class="cover_file_wrapper">
                                    <button type="button" class="btn_file step-file-btn">Выберите файл</button>
                                    <span class="file_name step-file-name">Файл не выбран</span>
                                    <input type="file" name="step_image[]" accept="image/*" class="step-file-input" hidden>
                                    <input type="hidden" name="step_image_old[]" value="<?= htmlspecialchars($step['image_url'] ?? '') ?>">
                                </div>
                            </div>

                            <button type="button" class="btn_remove_row" title="Удалить" style="top: 15px; right: 15px;">×</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form_actions">
                    <a href="?page=my_recipes" class="btn v1 btn_outline">Отмена</a>
                    <button type="submit" class="btn v1">Сохранить изменения</button>
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
        div.style.cssText = 'position:relative; padding: 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 15px; background: #fafafa;';
        div.innerHTML = `
            <div class="form_group"><label>Шаг ${stepCount}</label><textarea name="step_text[]" rows="3" placeholder="Опишите действие..." required style="width: 100%;"></textarea></div>
            <div class="form_group" style="margin-top: 12px;">
                <label style="font-size: 13px; color: #666; margin-bottom: 4px; display: block;">Изображение шага (необязательно)</label>
                <div class="cover_file_wrapper">
                    <button type="button" class="btn_file step-file-btn">Выберите файл</button>
                    <span class="file_name step-file-name">Файл не выбран</span>
                    <input type="file" name="step_image[]" accept="image/*" class="step-file-input" hidden>
                    <input type="hidden" name="step_image_old[]" value="">
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
            e.target.previousElementSibling.textContent = e.target.files.length > 0 ? e.target.files[0].name : 'Файл не выбран';
        }
    });

    const tagInput = document.getElementById('tagInput');
    const tagsDisplay = document.getElementById('tagsDisplay');
    const tagsHiddenInput = document.getElementById('tagsHiddenInput');
    let tags = [];

    const initialTagsStr = "<?= htmlspecialchars($tags_string) ?>";
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
            chip.innerHTML = `${tag} <button type="button" class="tag-remove" data-tag="${tag}">×</button>`;
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