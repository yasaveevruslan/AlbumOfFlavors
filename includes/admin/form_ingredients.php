<?php
    global $connect;

    $role = $_SESSION['user_role'] ?? 'user';
    
    if (!in_array($role, ['moderator', 'admin'])) {
        header('Location: ?page=main');
        exit;
    }

    $id = $_GET['id'] ?? null;
    
    if ($role === 'admin') {
        $back_url = '?page=admin_list&entity=ingredients';
    } else {
        $back_url = '?page=mod_list&entity=ingredients';
    }

    if (!$id) {
        $_SESSION['error_message'] = 'ID записи не указан';
        header("Location: $back_url");
        exit;
    }

    $stmt = $connect->prepare("SELECT name FROM recipe_ingredients WHERE id = ?");
    $stmt->execute([$id]);
    $current_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_item) {
        $_SESSION['error_message'] = 'Ингредиент не найден';
        header("Location: $back_url");
        exit;
    }

    $current_name = $current_item['name'];
    $title = 'Редактировать ингредиент';

    $stmt_count = $connect->prepare("SELECT COUNT(*) FROM recipe_ingredients WHERE name = ?");
    $stmt_count->execute([$current_name]);
    $usage_count = (int)$stmt_count->fetchColumn();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_name = trim($_POST['name'] ?? '');

        if ($new_name === '') {
            $_SESSION['error_message'] = 'Название ингредиента не может быть пустым';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        try {
            $connect->beginTransaction();
            
            $stmt_update = $connect->prepare("UPDATE recipe_ingredients SET name = ? WHERE name = ?");
            $stmt_update->execute([$new_name, $current_name]);
            
            $connect->commit();

            $_SESSION['success_message'] = "Название ингредиента успешно изменено (обновлено записей: " . $stmt_update->rowCount() . ")";
            
            header('Location: ' . $back_url);
            exit;

        } catch (Exception $e) {
            $connect->rollBack();
            $_SESSION['error_message'] = 'Ошибка БД при обновлении: ' . $e->getMessage();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    $error_msg = $_SESSION['error_message'] ?? '';
    $success_msg = $_SESSION['success_message'] ?? '';
    unset($_SESSION['error_message'], $_SESSION['success_message']);
?>

<div class="container">
    <?php if ($error_msg): ?>
    <div class="form_message error"
        style="display:block; margin-bottom:20px; padding:12px; background:#f8d7da; color:#721c24; border-radius:8px;">
        <?= htmlspecialchars($error_msg) ?>
    </div>
    <?php endif; ?>

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

            <form method="POST" class="admin_form mt-16">
                <div class="form_group">
                    <div class="message_info">
                        <p><span style="font-weight:500;">Обратите внимание:</span><br>
                            Данное название ("<span
                                style="font-weight:500;"><?= htmlspecialchars($current_name) ?></span>")
                            используется в <span style="font-weight:500;"><?= $usage_count ?></span> рецептах.
                            При сохранении новое название будет автоматически применено ко всем этим рецептам,
                            что поможет поддерживать чистоту справочника и избежать дубликатов.
                        </p>
                    </div>
                </div>

                <div class="form_group">
                    <label>Название ингредиента <span class="required">*</span></label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($current_name) ?>"
                        placeholder="Например: Картофель">
                </div>

                <div class="form_actions">
                    <a href="<?= $back_url ?>" class="btn v1 btn_outline">Отмена</a>
                    <button type="submit" class="btn v1">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
</div>