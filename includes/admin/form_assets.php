<?php
    global $connect;

    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: ?page=main');
        exit;
    }

    $id = $_GET['id'] ?? null;
    $back_url = '?page=admin_list&entity=assets';

    $item = null;
    if ($id) {
        $stmt = $connect->prepare("SELECT * FROM assets WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            $_SESSION['error_message'] = 'Ассет не найден';
            header("Location: $back_url");
            exit;
        }
    }

    $title = $id ? 'Редактировать ассет' : 'Новый ассет';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'sticker';
        $category = $_POST['category'] ?? 'decor';
        $width = !empty($_POST['width']) ? (int)$_POST['width'] : null;
        $height = !empty($_POST['height']) ? (int)$_POST['height'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $allowed_types = ['sticker', 'tape', 'stamp', 'frame'];
        if (!in_array($type, $allowed_types)) $type = 'sticker';

        $allowed_categories = ['desserts', 'vegetables', 'meat', 'drinks', 'decor', 'seasonal', 'fruits', 'herbs'];
        if (!in_array($category, $allowed_categories)) $category = 'decor';

        if ($name === '') {
            $_SESSION['error_message'] = 'Введите название ассета';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
        $image_url = $item['image_url'] ?? null;
        $image_changed = false;

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error_message'] = 'Ошибка загрузки изображения (код: ' . $_FILES['image_file']['error'] . ').';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }

            $file = $_FILES['image_file'];
            $allowed = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];
            
            if (in_array($file['type'], $allowed) && $file['size'] <= 10 * 1024 * 1024) {
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/asset/img/assets/';
                
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_name = 'asset_' . ($id ?: bin2hex(random_bytes(8))) . '_' . time() . '.' . strtolower($ext);
                $destination = $upload_dir . $new_name;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $image_url = 'asset/img/assets/' . $new_name;
                    $image_changed = true;
                } else {
                    $_SESSION['error_message'] = 'Не удалось сохранить изображение. Права на папку: ' . $upload_dir;
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            } else {
                $_SESSION['error_message'] = 'Ошибка: Формат не поддерживается или размер > 10 МБ';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        }

        $pattern_url = $item['pattern_url'] ?? null;
        $pattern_changed = false;

        if (isset($_FILES['pattern_file']) && $_FILES['pattern_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['pattern_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['warning_message'] = 'Паттерн не загружен (ошибка кода: ' . $_FILES['pattern_file']['error'] . ')';
            } else {
                $file = $_FILES['pattern_file'];
                $allowed = ['image/png', 'image/jpeg', 'image/webp'];
                
                if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/asset/img/assets/patterns/';
                    
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $new_name = 'pattern_' . ($id ?: bin2hex(random_bytes(8))) . '_' . time() . '.' . strtolower($ext);
                    $destination = $upload_dir . $new_name;

                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $pattern_url = 'asset/img/assets/patterns/' . $new_name;
                        $pattern_changed = true;
                    } else {
                        $_SESSION['error_message'] = 'Ошибка записи паттерна. Папка: ' . $upload_dir;
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    }
                }
            }
        }

        try {
            $connect->beginTransaction();

            if ($id) {
                $sql = "UPDATE assets SET name = ?, type = ?, category = ?, width = ?, height = ?, is_active = ?" . 
                       ($image_changed ? ", image_url = ?" : "") . 
                       ($pattern_changed ? ", pattern_url = ?" : "") . 
                       " WHERE id = ?";
                
                $params = [$name, $type, $category, $width, $height, $is_active];
                if ($image_changed) $params[] = $image_url;
                if ($pattern_changed) $params[] = $pattern_url;
                $params[] = $id;
                
                $stmt = $connect->prepare($sql);
                $stmt->execute($params);

                if ($image_changed && !empty($item['image_url']) && $item['image_url'] !== $image_url) {
                    $old_path = __DIR__ . '/../../../' . $item['image_url'];
                    if (file_exists($old_path)) @unlink($old_path);
                }
                if ($pattern_changed && !empty($item['pattern_url']) && $item['pattern_url'] !== $pattern_url) {
                    $old_path = __DIR__ . '/../../../' . $item['pattern_url'];
                    if (file_exists($old_path)) @unlink($old_path);
                }

                $_SESSION['success_message'] = 'Ассет обновлён';
            } else {
                $stmt = $connect->prepare("
                    INSERT INTO assets (id, name, type, category, image_url, pattern_url, width, height, is_active)
                    VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $type, $category, $image_url, $pattern_url, $width, $height, $is_active]);
                $_SESSION['success_message'] = 'Ассет создан';
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

    $posted_name = $_POST['name'] ?? $item['name'] ?? '';
    $posted_type = $_POST['type'] ?? $item['type'] ?? 'sticker';
    $posted_category = $_POST['category'] ?? $item['category'] ?? 'decor';
    $posted_width = $_POST['width'] ?? $item['width'] ?? '';
    $posted_height = $_POST['height'] ?? $item['height'] ?? '';
    $posted_is_active = isset($_POST['is_active']) ? 1 : ($item['is_active'] ?? 1);

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

            <form method="POST" enctype="multipart/form-data" class="admin_form mt-16" id="assetForm">

                <div class="form_card">
                    <h3 class="form_card_title">Основная информация</h3>
                    <div class="form_group">
                        <label>Название <span class="required">*</span></label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($posted_name) ?>"
                            placeholder="Например: Лимоны">
                    </div>

                    <div class="form_row">
                        <div class="form_group">
                            <label>Тип <span class="required">*</span></label>
                            <select name="type" class="filter-select" required>
                                <option value="sticker" <?= $posted_type === 'sticker' ? 'selected' : '' ?>>Стикер
                                </option>
                                <option value="tape" <?= $posted_type === 'tape' ? 'selected' : '' ?>>Лента (скотч)
                                </option>
                                <option value="stamp" <?= $posted_type === 'stamp' ? 'selected' : '' ?>>Штамп</option>
                                <option value="frame" <?= $posted_type === 'frame' ? 'selected' : '' ?>>Рамка</option>
                            </select>
                        </div>

                        <div class="form_group">
                            <label>Категория</label>
                            <select name="category" class="filter-select">
                                <option value="desserts" <?= $posted_category === 'desserts' ? 'selected' : '' ?>>
                                    Десерты</option>
                                <option value="vegetables" <?= $posted_category === 'vegetables' ? 'selected' : '' ?>>
                                    Овощи</option>
                                <option value="fruits" <?= $posted_category === 'fruits' ? 'selected' : '' ?>>Фрукты
                                </option>
                                <option value="meat" <?= $posted_category === 'meat' ? 'selected' : '' ?>>Мясо</option>
                                <option value="drinks" <?= $posted_category === 'drinks' ? 'selected' : '' ?>>Напитки
                                </option>
                                <option value="herbs" <?= $posted_category === 'herbs' ? 'selected' : '' ?>>Зелень и
                                    травы</option>
                                <option value="decor" <?= $posted_category === 'decor' ? 'selected' : '' ?>>Декор
                                </option>
                                <option value="seasonal" <?= $posted_category === 'seasonal' ? 'selected' : '' ?>>
                                    Сезонное</option>
                            </select>
                        </div>
                    </div>

                    <div class="form_group">
                        <label class="checkbox_label">
                            <input type="checkbox" name="is_active" value="1" <?= $posted_is_active ? 'checked' : '' ?>>
                            <span>Активный ассет (доступен пользователям)</span>
                        </label>
                    </div>
                </div>

                <div class="form_card">
                    <h3 class="form_card_title">Файлы</h3>

                    <div class="form_group">
                        <label>Основное изображение <span class="required">*</span> <small style="color:#888;">(PNG с
                                прозрачностью рекомендуется)</small></label>
                        <?php if (!empty($item['image_url'])): ?>
                        <div style="margin-bottom:12px;">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="Текущее изображение"
                                id="currentImage"
                                style="max-width:200px; max-height:200px; border-radius:8px; border:1px solid #ddd; background: repeating-conic-gradient(#eee 0% 25%, #fff 0% 50%) 50% / 20px 20px;">
                        </div>
                        <?php endif; ?>
                        <div class="cover_file_wrapper">
                            <button type="button" class="btn_file" id="imageBtn">
                                <?= $item ? 'Заменить файл' : 'Выберите файл' ?>
                            </button>
                            <span class="file_name" id="imageName">Файл не выбран</span>
                            <input type="file" id="imageInput" name="image_file" accept="image/*" hidden>
                        </div>
                        <div id="imagePreview" style="margin-top:12px;"></div>
                    </div>

                    <div class="form_group">
                        <label>Паттерн/текстура <small style="color:#888;">(необязательно, для лент и
                                рамок)</small></label>
                        <?php if (!empty($item['pattern_url'])): ?>
                        <div style="margin-bottom:12px;">
                            <img src="<?= htmlspecialchars($item['pattern_url']) ?>" alt="Текущий паттерн"
                                id="currentPattern"
                                style="max-width:200px; max-height:100px; border-radius:8px; border:1px solid #ddd;">
                        </div>
                        <?php endif; ?>
                        <div class="cover_file_wrapper">
                            <button type="button" class="btn_file" id="patternBtn">
                                <?= !empty($item['pattern_url']) ? 'Заменить файл' : 'Выберите файл' ?>
                            </button>
                            <span class="file_name" id="patternName">Файл не выбран</span>
                            <input type="file" id="patternInput" name="pattern_file" accept="image/*" hidden>
                        </div>
                        <div id="patternPreview" style="margin-top:12px;"></div>
                    </div>
                </div>

                <div class="form_card">
                    <h3 class="form_card_title">Размеры</h3>
                    <p style="color:#666; font-size:14px; margin-bottom:16px;">
                        Укажите размеры в пикселях. Если не заполнить — будут использованы реальные размеры изображения.
                    </p>

                    <div class="form_row">
                        <div class="form_group">
                            <label>Ширина (px)</label>
                            <input type="number" name="width" min="1" max="2000"
                                value="<?= htmlspecialchars($posted_width) ?>" placeholder="200">
                        </div>
                        <div class="form_group">
                            <label>Высота (px)</label>
                            <input type="number" name="height" min="1" max="2000"
                                value="<?= htmlspecialchars($posted_height) ?>" placeholder="200">
                        </div>
                    </div>

                    <div class="form_group">
                        <label>Пропорции</label>
                        <div>
                            <label class="checkbox_label">
                                <input type="checkbox" id="keepRatio" checked>
                                Сохранять пропорции
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form_actions">
                    <a href="<?= $back_url ?>" class="btn v1 btn_outline">Отмена</a>
                    <button type="submit" class="btn v1">
                        <?= $id ? 'Сохранить изменения' : 'Создать ассет' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const imageBtn = document.getElementById('imageBtn');
    const imageInput = document.getElementById('imageInput');
    const imageName = document.getElementById('imageName');
    const imagePreview = document.getElementById('imagePreview');
    const currentImage = document.getElementById('currentImage');

    imageBtn?.addEventListener('click', () => imageInput?.click());
    imageInput?.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            imageName.textContent = file.name;

            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.innerHTML = `<img src="${e.target.result}" alt="Предпросмотр">`;
                if (currentImage) currentImage.style.display = 'none';

                const img = new Image();
                img.onload = function() {
                    const widthInput = document.querySelector('input[name="width"]');
                    const heightInput = document.querySelector('input[name="height"]');
                    if (widthInput && !widthInput.value) widthInput.value = this.width;
                    if (heightInput && !heightInput.value) heightInput.value = this.height;
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            imageName.textContent = 'Файл не выбран';
            imagePreview.innerHTML = '';
            if (currentImage) currentImage.style.display = 'block';
        }
    });

    const patternBtn = document.getElementById('patternBtn');
    const patternInput = document.getElementById('patternInput');
    const patternName = document.getElementById('patternName');
    const patternPreview = document.getElementById('patternPreview');
    const currentPattern = document.getElementById('currentPattern');

    patternBtn?.addEventListener('click', () => patternInput?.click());
    patternInput?.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            patternName.textContent = file.name;

            const reader = new FileReader();
            reader.onload = (e) => {
                patternPreview.innerHTML =
                    `<img src="${e.target.result}" alt="Предпросмотр паттерна">`;
                if (currentPattern) currentPattern.style.display = 'none';
            };
            reader.readAsDataURL(file);
        } else {
            patternName.textContent = 'Файл не выбран';
            patternPreview.innerHTML = '';
            if (currentPattern) currentPattern.style.display = 'block';
        }
    });

    const widthInput = document.querySelector('input[name="width"]');
    const heightInput = document.querySelector('input[name="height"]');
    const keepRatio = document.getElementById('keepRatio');
    let originalRatio = null;

    widthInput?.addEventListener('input', function() {
        if (keepRatio?.checked && originalRatio && this.value) {
            heightInput.value = Math.round(this.value / originalRatio);
        }
    });

    heightInput?.addEventListener('input', function() {
        if (keepRatio?.checked && originalRatio && this.value) {
            widthInput.value = Math.round(this.value * originalRatio);
        }
    });

    imageInput?.addEventListener('change', function() {
        if (this.files.length > 0) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = function() {
                    originalRatio = this.width / this.height;
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>