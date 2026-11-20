<?php
    global $connect;

    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: ?page=main');
        exit;
    }

    $id = $_GET['id'] ?? null;
    $back_url = '?page=admin_list&entity=album_templates';

    $item = null;
    $usage_count = 0;
    
    if ($id) {
        $stmt = $connect->prepare("SELECT * FROM album_templates WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            $_SESSION['error_message'] = 'Шаблон не найден';
            header("Location: $back_url");
            exit;
        }

        $stmt = $connect->prepare("SELECT COUNT(*) FROM albums WHERE template_id = ?");
        $stmt->execute([$id]);
        $usage_count = (int)$stmt->fetchColumn();
    }

    $title = $id ? 'Редактировать шаблон' : 'Новый шаблон';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $page_layouts_json = trim($_POST['page_layouts'] ?? '[]');

        if ($name === '') {
            $_SESSION['error_message'] = 'Введите название шаблона';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        $page_layouts = json_decode($page_layouts_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $_SESSION['error_message'] = 'Ошибка в JSON: ' . json_last_error_msg();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        if (!is_array($page_layouts)) {
            $_SESSION['error_message'] = 'JSON должен быть массивом (начинаться с [ )';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        foreach ($page_layouts as $index => $page) {
            if (!isset($page['page']) || !isset($page['elements'])) {
                $_SESSION['error_message'] = "Страница #" . ($index + 1) . " должна содержать поля 'page' и 'elements'";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        }

        $cover_url = $item['cover_url'] ?? null;
        $cover_changed = false;

        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['cover_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error_message'] = 'Ошибка загрузки файла (код: ' . $_FILES['cover_file']['error'] . '). Проверьте php.ini (upload_max_filesize).';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }

            $file = $_FILES['cover_file'];
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            
            if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/asset/img/album_templates/';
                
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        $_SESSION['error_message'] = 'Не удалось создать папку. Проверьте права: ' . $upload_dir;
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    }
                }
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_name = 'template_' . ($id ?: bin2hex(random_bytes(8))) . '_' . time() . '.' . strtolower($ext);
                $destination = $upload_dir . $new_name;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $cover_url = 'asset/img/album_templates/' . $new_name;
                    $cover_changed = true;
                } else {
                    $_SESSION['error_message'] = 'Ошибка записи файла на диск. Проверьте права на папку: ' . $upload_dir;
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            } else {
                $_SESSION['error_message'] = 'Недопустимый формат или размер обложки (макс. 5 МБ, JPG/PNG/WEBP)';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        }

        try {
            $connect->beginTransaction();

            if ($id) {
                $sql = "UPDATE album_templates SET title = ?, description = ?, page_layouts = ?, is_active = ?" . 
                       ($cover_changed ? ", cover_url = ?" : "") . 
                       " WHERE id = ?";
                $params = $cover_changed 
                    ? [$name, $description, $page_layouts_json, $is_active, $cover_url, $id]
                    : [$name, $description, $page_layouts_json, $is_active, $id];
                
                $stmt = $connect->prepare($sql);
                $stmt->execute($params);

                if ($cover_changed && !empty($item['cover_url']) && $item['cover_url'] !== $cover_url) {
                    $old_path = __DIR__ . '/../../../' . $item['cover_url'];
                    if (file_exists($old_path)) @unlink($old_path);
                }

                $_SESSION['success_message'] = 'Шаблон обновлён';
            } else {
                $stmt = $connect->prepare("
                    INSERT INTO album_templates (id, title, cover_url, description, page_layouts, is_active)
                    VALUES (UUID(), ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $cover_url, $description, $page_layouts_json, $is_active]);
                $_SESSION['success_message'] = 'Шаблон создан';
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

    $posted_title = $_POST['title'] ?? $item['title'] ?? '';
    $posted_description = $_POST['description'] ?? $item['description'] ?? '';
    $posted_is_active = isset($_POST['is_active']) ? 1 : ($item['is_active'] ?? 1);
    $posted_layouts = $_POST['page_layouts'] ?? ($item['page_layouts'] ?? '[]');

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

            <?php if ($id && $usage_count > 0): ?>
            <div class="message_info">
                Этот шаблон используется в <strong><?= $usage_count ?></strong> альбомах.
                Отключение шаблона (снятие галочки "Активный") скроет его из списка доступных, но не удалит из
                существующих альбомов.
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="admin_form mt-16" id="templateForm">
                <div class="form_group">
                    <label>Название шаблона <span class="required">*</span></label>
                    <input type="text" name="title" required value="<?= htmlspecialchars($posted_title) ?>"
                        placeholder="Например: Классический скрапбукинг">
                </div>

                <div class="form_group">
                    <label>Описание</label>
                    <textarea name="description" rows="3"
                        placeholder="Краткое описание шаблона..."><?= htmlspecialchars($posted_description) ?></textarea>
                </div>

                <div class="form_group">
                    <label>Обложка шаблона</label>
                    <?php if (!empty($item['cover_url'])): ?>
                    <div style="margin-bottom:12px;">
                        <img src="<?= htmlspecialchars($item['cover_url']) ?>" alt="Текущая обложка" id="currentCover"
                            style="max-width:200px; border-radius:8px; border:1px solid #ddd;">
                    </div>
                    <?php endif; ?>
                    <div class="cover_file_wrapper">
                        <button type="button" class="btn_file" id="coverBtn">
                            <?= $item ? 'Заменить файл' : 'Выберите файл' ?>
                        </button>
                        <span class="file_name" id="coverName">Файл не выбран</span>
                        <input type="file" id="coverInput" name="cover_file" accept="image/*" hidden>
                    </div>
                    <div id="coverPreview" style="margin-top:12px;"></div>
                </div>

                <div class="form_group">
                    <label class="checkbox_label">
                        <input type="checkbox" name="is_active" value="1" <?= $posted_is_active ? 'checked' : '' ?>>
                        <span class="checkbox_custom"></span>
                        <span>Активный шаблон (доступен пользователям)</span>
                    </label>
                </div>

                <div class="form_actions">
                    <a href="<?= $back_url ?>" class="btn v1 btn_outline">Отмена</a>
                    <button type="submit" class="btn v1">
                        <?= $id ? 'Сохранить изменения' : 'Создать шаблон' ?>
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
    const coverPreview = document.getElementById('coverPreview');
    const currentCover = document.getElementById('currentCover');

    coverBtn?.addEventListener('click', () => coverInput?.click());
    coverInput?.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            coverName.textContent = file.name;

            const reader = new FileReader();
            reader.onload = (e) => {
                coverPreview.innerHTML = `<img src="${e.target.result}" alt="Предпросмотр">`;
                if (currentCover) currentCover.style.display = 'none';
            };
            reader.readAsDataURL(file);
        } else {
            coverName.textContent = 'Файл не выбран';
            coverPreview.innerHTML = '';
            if (currentCover) currentCover.style.display = 'block';
        }
    });
});
</script>