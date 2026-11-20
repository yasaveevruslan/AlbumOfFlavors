<?php
    global $connect;

    $role = $_SESSION['user_role'] ?? 'user';
    if (!in_array($role, ['moderator', 'admin'])) {
        header('Location: ?page=main');
        exit;
    }

    $id = $_GET['id'] ?? null;
    
    if ($role === 'admin') {
        $back_url = '?page=admin_list&entity=categories';
        $form_url_base = '?page=admin_form&entity=categories';
    } else {
        $back_url = '?page=mod_list&entity=categories';
        $form_url_base = '?page=mod_form&entity=categories';
    }

    function slugify($text) {
        $translit = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh',
            'з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
            'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts',
            'ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya'
        ];
        $text = mb_strtolower(trim($text));
        $result = '';
        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            $result .= $translit[$char] ?? $char;
        }
        $result = preg_replace('/[^a-z0-9\s\-]/', '', $result);
        $result = preg_replace('/[\s\-]+/', '-', $result);
        return trim($result, '-');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image_url = null;

        if ($name === '') {
            $_SESSION['error_message'] = 'Введите название категории';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        if ($slug === '') {
            $slug = slugify($name);
        } else {
            $slug = slugify($slug);
        }

        $check_sql = "SELECT id FROM categories WHERE slug = ?" . ($id ? " AND id != ?" : "");
        $check_params = $id ? [$slug, $id] : [$slug];
        
        $stmt = $connect->prepare($check_sql);
        $stmt->execute($check_params);
        
        if ($stmt->fetch()) {
            $counter = 2;
            $base_slug = $slug;
            while (true) {
                $slug = $base_slug . '-' . $counter;
                $check_params = $id ? [$slug, $id] : [$slug];
                $stmt->execute($check_params);
                if (!$stmt->fetch()) {
                    break;
                }
                $counter++;
            }
        }

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error_message'] = 'Ошибка загрузки изображения (код: ' . $_FILES['image_file']['error'] . '). Проверьте лимиты php.ini.';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }

            $file = $_FILES['image_file'];
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            
            if (in_array($file['type'], $allowed) && $file['size'] <= 3 * 1024 * 1024) {
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/asset/img/categories/';
                
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        $_SESSION['error_message'] = 'Не удалось создать папку: ' . $upload_dir . '. Проверьте права.';
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    }
                }
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_name = 'cat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                $destination = $upload_dir . $new_name;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $image_url = 'asset/img/categories/' . $new_name;
                } else {
                    $_SESSION['error_message'] = 'Не удалось сохранить изображение. Проверьте права на папку: ' . $upload_dir;
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            } else {
                $_SESSION['error_message'] = 'Недопустимый формат или размер изображения (макс. 3 МБ, JPG/PNG/WEBP/GIF)';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        }

        try {
            $connect->beginTransaction();

            if ($id) {
                if ($image_url) {
                    $stmt_old = $connect->prepare("SELECT image_url FROM categories WHERE id = ?");
                    $stmt_old->execute([$id]);
                    $old = $stmt_old->fetchColumn();
                    if ($old && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $old)) {
                        @unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $old);
                    }
                }

                $sql = "UPDATE categories SET name = ?, slug = ?, description = ?" . 
                       ($image_url ? ", image_url = ?" : "") . 
                       " WHERE id = ?";
                $params = $image_url 
                    ? [$name, $slug, $description ?: null, $image_url, $id]
                    : [$name, $slug, $description ?: null, $id];
                $stmt = $connect->prepare($sql);
                $stmt->execute($params);

                $_SESSION['success_message'] = 'Категория обновлена';
            } else {
                $sql = "INSERT INTO categories (id, name, slug, description, image_url) VALUES (UUID(), ?, ?, ?, ?)";
                $stmt = $connect->prepare($sql);
                $stmt->execute([$name, $slug, $description ?: null, $image_url]);

                $_SESSION['success_message'] = 'Категория создана';
            }

            $connect->commit();
            header('Location: ' . $back_url);
            exit;

        } catch (Exception $e) {
            $connect->rollBack();
            $_SESSION['error_message'] = 'Ошибка БД: ' . $e->getMessage();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    $item = null;
    if ($id) {
        $stmt = $connect->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            $_SESSION['error_message'] = 'Категория не найдена';
            header("Location: " . $back_url);
            exit;
        }
    }

    $title = $id ? 'Редактировать категорию' : 'Новая категория';
    $error_msg = $_SESSION['error_message'] ?? '';
    unset($_SESSION['error_message']);
?>

<div class="container">
    <div class="admin_box">
        <div class="admin_body">
            <?php if ($error_msg): ?>
            <div class="form_message error"
                style="display:block; margin-bottom:20px; padding:12px; background:#f8d7da; color:#721c24; border-radius:8px;">
                <?= htmlspecialchars($error_msg) ?>
            </div>
            <?php endif; ?>
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

            <form method="POST" enctype="multipart/form-data" class="admin_form mt-16" id="categoryForm">
                <div class="form_group">
                    <label>Название <span class="required">*</span></label>
                    <input type="text" name="name" id="cat_name"
                        value="<?= htmlspecialchars($_POST['name'] ?? $item['name'] ?? '') ?>"
                        placeholder="Например: Супы">
                </div>

                <div class="form_group">
                    <label>Slug <small style="color:#888;">(если пусто — сгенерируется автоматически)</small></label>
                    <input type="text" name="slug" id="cat_slug"
                        value="<?= htmlspecialchars($_POST['slug'] ?? $item['slug'] ?? '') ?>" placeholder="supy">
                </div>

                <div class="form_group">
                    <label>Описание</label>
                    <textarea name="description" rows="4"
                        placeholder="Краткое описание категории..."><?= htmlspecialchars($_POST['description'] ?? $item['description'] ?? '') ?></textarea>
                </div>

                <div class="form_group">
                    <label>Изображение категории</label>
                    <?php if (!empty($item['image_url'])): ?>
                    <div style="margin-bottom:12px;">
                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="Текущее"
                            style="max-width:200px; border-radius:8px; border:1px solid #ddd;">
                    </div>
                    <?php endif; ?>
                    <div class="cover_file_wrapper">
                        <button type="button" class="btn_file" id="catImageBtn">
                            <?= $item ? 'Заменить файл' : 'Выберите файл' ?>
                        </button>
                        <span class="file_name" id="catImageName">Файл не выбран</span>
                        <input type="file" id="catImageInput" name="image_file" accept="image/*" hidden>
                    </div>
                    <div id="catImagePreview" style="margin-top:12px;"></div>
                </div>

                <div class="form_actions">
                    <a href="<?= $back_url ?>" class="btn v1 btn_outline">Отмена</a>
                    <button type="submit" class="btn v1">
                        <?= $id ? 'Сохранить изменения' : 'Создать категорию' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('cat_name');
    const slugInput = document.getElementById('cat_slug');
    const imageBtn = document.getElementById('catImageBtn');
    const imageInput = document.getElementById('catImageInput');
    const imageName = document.getElementById('catImageName');
    const imagePreview = document.getElementById('catImagePreview');

    let slugManuallyEdited = <?= ($item && !empty($item['slug'])) ? 'true' : 'false' ?>;

    nameInput.addEventListener('input', function() {
        if (!slugManuallyEdited) {
            slugInput.value = slugifyJS(this.value);
        }
    });

    slugInput.addEventListener('input', function() {
        slugManuallyEdited = true;
    });

    function slugifyJS(text) {
        const translit = {
            'а': 'a',
            'б': 'b',
            'в': 'v',
            'г': 'g',
            'д': 'd',
            'е': 'e',
            'ё': 'e',
            'ж': 'zh',
            'з': 'z',
            'и': 'i',
            'й': 'y',
            'к': 'k',
            'л': 'l',
            'м': 'm',
            'н': 'n',
            'о': 'o',
            'п': 'p',
            'р': 'r',
            'с': 's',
            'т': 't',
            'у': 'u',
            'ф': 'f',
            'х': 'h',
            'ц': 'ts',
            'ч': 'ch',
            'ш': 'sh',
            'щ': 'sch',
            'ъ': '',
            'ы': 'y',
            'ь': '',
            'э': 'e',
            'ю': 'yu',
            'я': 'ya'
        };
        let result = '';
        const lower = text.toLowerCase();
        for (let i = 0; i < lower.length; i++) {
            const ch = lower[i];
            result += translit[ch] !== undefined ? translit[ch] : ch;
        }
        result = result.replace(/[^a-z0-9\s\-]/g, '');
        result = result.replace(/[\s\-]+/g, '-');
        return result.replace(/^-+|-+$/g, '');
    }

    imageBtn?.addEventListener('click', () => imageInput?.click());
    imageInput?.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            imageName.textContent = file.name;

            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview" 
                    style="max-width:200px; border-radius:8px; border:1px solid #ddd;">`;
            };
            reader.readAsDataURL(file);
        } else {
            imageName.textContent = 'Файл не выбран';
            imagePreview.innerHTML = '';
        }
    });
});
</script>