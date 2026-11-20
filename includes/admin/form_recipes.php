<?php
    global $connect;
    $role = $_SESSION['user_role'] ?? 'user';
    if (!in_array($role, ['moderator', 'admin'])) {
        header('Location: ?page=main');
        exit;
    }

    $id = $_GET['id'] ?? null;
    if ($role === 'admin') {
        $back_url = '?page=admin_list&entity=recipes';
    } elseif ($role === 'moderator') {
        $back_url = '?page=mod_list&entity=recipes';
    } else {
        $back_url = '?page=my_recipes';
    }

    $stmt_cats = $connect->query("SELECT id, name FROM categories ORDER BY name ASC");
    $all_categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

    $stmt_users = $connect->query("SELECT id, username FROM users ORDER BY username ASC");
    $all_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

    $recipe = null;
    $recipe_cat_ids = [];
    $recipe_tag_names = [];
    $recipe_ingredients = [];
    $recipe_steps = [];

    if ($id) {
        $stmt = $connect->prepare("SELECT * FROM recipes WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recipe) {
            $_SESSION['error_message'] = 'Рецепт не найден';
            header("Location: $back_url");
            exit;
        }

        $stmt = $connect->prepare("SELECT category_id FROM recipe_categories WHERE recipe_id = ?");
        $stmt->execute([$id]);
        $recipe_cat_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $connect->prepare("
            SELECT t.name FROM tags t 
            INNER JOIN recipe_tags rt ON t.id = rt.tag_id 
            WHERE rt.recipe_id = ?
        ");
        $stmt->execute([$id]);
        $recipe_tag_names = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $connect->prepare("SELECT name, amount FROM recipe_ingredients WHERE recipe_id = ? ORDER BY order_index ASC");
        $stmt->execute([$id]);
        $recipe_ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $connect->prepare("SELECT text, image_url FROM recipe_steps WHERE recipe_id = ? ORDER BY step_number ASC");
        $stmt->execute([$id]);
        $recipe_steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $title = $id ? 'Редактировать рецепт' : 'Новый рецепт';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title_post = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $cooking_time = !empty($_POST['cooking_time']) ? (int)$_POST['cooking_time'] : null;
        $servings = !empty($_POST['servings']) ? (int)$_POST['servings'] : null;
        $calories = !empty($_POST['calories']) ? (int)$_POST['calories'] : null;
        $status = $_POST['status'] ?? 'draft';
        $author_id = $_POST['author_id'] ?? $_SESSION['user_id'];
        $selected_categories = $_POST['categories'] ?? [];
        $tags_input = trim($_POST['tags_input'] ?? '');

        if ($title_post === '') {
            $_SESSION['error_message'] = 'Введите название рецепта';
            $redirect = $id ? "?page=mod_form&entity=recipes&id=$id" : "?page=mod_form&entity=recipes";
            header("Location: $redirect");
            exit;
        }

        if (empty($selected_categories)) {
            $_SESSION['error_message'] = 'Выберите хотя бы одну категорию';
            $redirect = $id ? "?page=mod_form&entity=recipes&id=$id" : "?page=mod_form&entity=recipes";
            header("Location: $redirect");
            exit;
        }

        $allowed_statuses = ['draft', 'moderation', 'published', 'hidden'];
        if (!in_array($status, $allowed_statuses)) {
            $status = 'draft';
        }

        $stmt = $connect->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$author_id]);
        if (!$stmt->fetch()) {
            $author_id = $_SESSION['user_id'];
        }

        $cover_url = $recipe['cover_url'] ?? null;
        $cover_changed = false;
        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['cover_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error_message'] = 'Ошибка загрузки обложки (код: ' . $_FILES['cover_file']['error'] . '). Проверьте лимиты php.ini.';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }

            $file = $_FILES['cover_file'];
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            
            if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/asset/img/recipes/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        $_SESSION['error_message'] = 'Не удалось создать папку: ' . $upload_dir;
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    }
                }
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_name = 'recipe_' . ($id ?: sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
                    mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
                    mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff))) . '_' . time() . '.' . strtolower($ext);
                
                $destination = $upload_dir . $new_name;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $cover_url = 'asset/img/recipes/' . $new_name;
                    $cover_changed = true;
                } else {
                    $_SESSION['error_message'] = 'Не удалось сохранить обложку. Проверьте права на папку: ' . $upload_dir;
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            } else {
                $_SESSION['error_message'] = 'Недопустимый формат или размер обложки (макс. 5 МБ, JPG/PNG/WEBP)';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        }

        $recipe_id = $id;
        if (!$recipe_id) {
            $recipe_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
                mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
                mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
        }

        $connect->beginTransaction();
        try {
            if ($id) {
                $sql = "UPDATE recipes SET title = ?, description = ?, cover_url = ?, 
                        author_id = ?, cooking_time = ?, servings = ?, calories = ?, status = ?
                        WHERE id = ?";
                $stmt = $connect->prepare($sql);
                $stmt->execute([$title_post, $description, $cover_url, $author_id, 
                    $cooking_time, $servings, $calories, $status, $id]);

                if ($cover_changed && !empty($recipe['cover_url']) && $recipe['cover_url'] !== $cover_url) {
                    $old_path = __DIR__ . '/../../../' . $recipe['cover_url'];
                    if (file_exists($old_path)) @unlink($old_path);
                }

                $connect->prepare("DELETE FROM recipe_categories WHERE recipe_id = ?")->execute([$id]);
                $connect->prepare("DELETE FROM recipe_tags WHERE recipe_id = ?")->execute([$id]);
                $connect->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$id]);
                $connect->prepare("DELETE FROM recipe_steps WHERE recipe_id = ?")->execute([$id]);

                foreach ($recipe_steps as $old_step) {
                    if (!empty($old_step['image_url'])) {
                        $old_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $old_step['image_url'];
                        if (file_exists($old_path)) @unlink($old_path);
                    }
                }

                $action_text = 'Рецепт обновлён';
            } else {
                $stmt = $connect->prepare("
                    INSERT INTO recipes (id, title, description, cover_url, author_id, cooking_time, servings, calories, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$recipe_id, $title_post, $description, $cover_url, $author_id, 
                    $cooking_time, $servings, $calories, $status]);

                $action_text = $status === 'published' ? 'Рецепт опубликован' : 'Рецепт создан';
            }

            $stmt_cat = $connect->prepare("INSERT INTO recipe_categories (recipe_id, category_id) VALUES (?, ?)");
            $valid_cat_ids = array_column($all_categories, 'id');
            foreach ($selected_categories as $cat_id) {
                if (in_array($cat_id, $valid_cat_ids)) {
                    $stmt_cat->execute([$recipe_id, $cat_id]);
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
                            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
                            mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
                            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
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
                        $ing_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', 
                            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), 
                            mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, 
                            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
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
                                $_SESSION['error_message'] = 'Ошибка загрузки изображения шага #' . $step_num . ' (код: ' . $_FILES['step_image']['error'][$index] . '). Проверьте лимиты php.ini.';
                                $connect->rollBack();
                                header("Location: " . $_SERVER['REQUEST_URI']);
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
                                        $_SESSION['error_message'] = 'Не удалось создать папку для шагов: ' . $upload_dir . '. Проверьте права.';
                                        $connect->rollBack();
                                        header("Location: " . $_SERVER['REQUEST_URI']);
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
                                    header("Location: " . $_SERVER['REQUEST_URI']);
                                    exit;
                                }
                            } else {
                                $_SESSION['error_message'] = 'Изображение шага #' . $step_num . ': недопустимый формат или размер > 5 МБ';
                                $connect->rollBack();
                                header("Location: " . $_SERVER['REQUEST_URI']);
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
            if ($role === 'admin') {
                $success_redirect = '?page=admin_list&entity=recipes';
            } elseif ($role === 'moderator') {
                $success_redirect = '?page=mod_list&entity=recipes';
            } else {
                $success_redirect = '?page=my_recipes';
            }
            
            header('Location: ' . $success_redirect);
            exit;

        } catch (Exception $e) {
            $connect->rollBack();
            $_SESSION['error_message'] = 'Ошибка при сохранении: ' . $e->getMessage();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    $posted_categories = $_POST['categories'] ?? $recipe_cat_ids;
    $posted_tags = $_POST['tags_input'] ?? implode(', ', $recipe_tag_names);
    $posted_ing_names = $_POST['ing_name'] ?? array_column($recipe_ingredients, 'name') ?: [''];
    $posted_ing_amounts = $_POST['ing_amount'] ?? array_column($recipe_ingredients, 'amount') ?: [''];
    $posted_steps = $_POST['step_text'] ?? array_column($recipe_steps, 'text') ?: [''];
    $posted_step_images = array_column($recipe_steps, 'image_url');

    $error_msg = $_SESSION['error_message'] ?? '';
    unset($_SESSION['error_message']);
?>

<div class="container">
    <div class="admin_box">
        <div class="admin_body">
            <div class="admin_list_header">
                <h2 class="admin_main_categories_title"><?= htmlspecialchars($title) ?></h2>
                <a href="<?= $back_url ?>" class="btn v1 btn_outline">← К списку</a>
            </div>

            <?php if ($error_msg): ?>
            <div class="form_message error"
                style="display:block; margin-bottom:20px; padding:12px; background:#f8d7da; color:#721c24; border-radius:8px;">
                <?= htmlspecialchars($error_msg) ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="admin_form mt-16" id="recipeForm">
                <div class="form_group">
                    <label>Название рецепта <span class="required">*</span></label>
                    <input type="text" name="title" required placeholder="Например: Борщ украинский"
                        value="<?= htmlspecialchars($_POST['title'] ?? $recipe['title'] ?? '') ?>">
                </div>

                <div class="form_group">
                    <label>Автор рецепта</label>
                    <select name="author_id" class="filter-select">
                        <?php foreach ($all_users as $user): ?>
                        <option value="<?= htmlspecialchars($user['id']) ?>"
                            <?= ($recipe['author_id'] ?? $_SESSION['user_id']) === $user['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['username']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form_group">
                    <label>Статус</label>
                    <select name="status" class="filter-select">
                        <option value="draft" <?= ($recipe['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>
                            Черновик</option>
                        <option value="moderation" <?= ($recipe['status'] ?? '') === 'moderation' ? 'selected' : '' ?>>
                            На модерации
                        </option>
                        <option value="published" <?= ($recipe['status'] ?? '') === 'published' ? 'selected' : '' ?>>
                            Опубликован</option>
                        <option value="hidden" <?= ($recipe['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Скрыт
                        </option>
                    </select>
                </div>

                <div class="form_group">
                    <label>Категории <span class="required">*</span></label>
                    <div class="category_tags_selector">
                        <?php foreach ($all_categories as $cat): ?>
                        <label
                            class="category_checkbox_tag <?= in_array($cat['id'], $posted_categories) ? 'selected' : '' ?>">
                            <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($cat['id']) ?>"
                                <?= in_array($cat['id'], $posted_categories) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($cat['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form_group">
                    <label>Описание</label>
                    <textarea name="description" rows="4"
                        placeholder="Краткое описание блюда, история или советы..."><?= htmlspecialchars($_POST['description'] ?? $recipe['description'] ?? '') ?></textarea>
                </div>

                <div class="form_row">
                    <div class="form_group">
                        <label>Время (мин)</label>
                        <input type="number" name="cooking_time" min="1" placeholder="30"
                            value="<?= htmlspecialchars($_POST['cooking_time'] ?? $recipe['cooking_time'] ?? '') ?>">
                    </div>
                    <div class="form_group">
                        <label>Порции</label>
                        <input type="number" name="servings" min="1" placeholder="4"
                            value="<?= htmlspecialchars($_POST['servings'] ?? $recipe['servings'] ?? '') ?>">
                    </div>
                    <div class="form_group">
                        <label>Калории (ккал)</label>
                        <input type="number" name="calories" min="0" placeholder="250"
                            value="<?= htmlspecialchars($_POST['calories'] ?? $recipe['calories'] ?? '') ?>">
                    </div>
                </div>

                <div class="form_group">
                    <label>Обложка рецепта</label>
                    <?php if (!empty($recipe['cover_url'])): ?>
                    <div style="margin-bottom:12px;">
                        <img src="<?= htmlspecialchars($recipe['cover_url']) ?>" alt="Текущая обложка"
                            style="max-width:200px; border-radius:8px; border:1px solid #ddd;">
                    </div>
                    <?php endif; ?>
                    <div class="cover_file_wrapper">
                        <button type="button" class="btn_file" id="coverBtn">
                            <?= $recipe ? 'Заменить файл' : 'Выберите файл' ?>
                        </button>
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
                        <?php foreach ($posted_ing_names as $index => $name): ?>
                        <div class="ingredient-row form_row">
                            <div class="form_group" style="flex:2;">
                                <input type="text" name="ing_name[]" placeholder="Название (напр. Картофель)"
                                    value="<?= htmlspecialchars($name) ?>">
                            </div>
                            <div class="form_group" style="flex:1;">
                                <input type="text" name="ing_amount[]" placeholder="Кол-во (напр. 500 г)"
                                    value="<?= htmlspecialchars($posted_ing_amounts[$index] ?? '') ?>">
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
                        <?php foreach ($posted_steps as $index => $text): ?>
                        <div class="step-row"
                            style="position:relative; padding: 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 15px; background: #fafafa;">
                            <div class="form_group">
                                <label>Шаг <?= $index + 1 ?></label>
                                <textarea name="step_text[]" rows="3" placeholder="Опишите действие..."
                                    style="width: 100%;"><?= htmlspecialchars($text) ?></textarea>
                            </div>
                            <?php if (!empty($posted_step_images[$index])): ?>
                            <div style="margin: 8px 0;">
                                <img src="<?= htmlspecialchars($posted_step_images[$index]) ?>"
                                    alt="Шаг <?= $index + 1 ?>"
                                    style="max-width:150px; border-radius:6px; border:1px solid #ddd;">
                            </div>
                            <?php endif; ?>
                            <div class="form_group" style="margin-top: 12px;">
                                <label style="font-size: 13px; color: #666; margin-bottom: 4px; display: block;">
                                    <?= !empty($posted_step_images[$index]) ? 'Заменить изображение шага' : 'Изображение шага (необязательно)' ?>
                                </label>
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
                    <a href="<?= $back_url ?>" class="btn v1 btn_outline">Отмена</a>
                    <button type="submit" class="btn v1">
                        <?= $id ? 'Сохранить изменения' : 'Создать рецепт' ?>
                    </button>
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