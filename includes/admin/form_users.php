<?php
    global $connect;
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: ?page=main');
        exit;
    }

    $id = $_GET['id'] ?? null;
    $back_url = '?page=admin_list&entity=users';

    if (!$id) {
        $_SESSION['error_message'] = 'ID пользователя не указан';
        header("Location: $back_url");
        exit;
    }

    $stmt = $connect->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error_message'] = 'Пользователь не найден или уже удален';
        header("Location: $back_url");
        exit;
    }

    $title = 'Редактирование пользователя';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $new_password = trim($_POST['new_password'] ?? '');

        if ($username === '' || $email === '') {
            $_SESSION['error_message'] = 'Имя и Email не могут быть пустыми';
            header("Location: ?page=admin_form&entity=users&id=$id");
            exit;
        }

        if (!in_array($role, ['user', 'moderator', 'admin'])) {
            $role = 'user';
        }

        $stmt_check = $connect->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt_check->execute([$email, $id]);
        if ($stmt_check->fetch()) {
            $_SESSION['error_message'] = 'Этот Email уже используется другим пользователем';
            header("Location: ?page=admin_form&entity=users&id=$id");
            exit;
        }

        $stmt_check = $connect->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt_check->execute([$username, $id]);
        if ($stmt_check->fetch()) {
            $_SESSION['error_message'] = 'Это имя пользователя уже занято';
            header("Location: ?page=admin_form&entity=users&id=$id");
            exit;
        }

        try {
            if ($new_password !== '') {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $connect->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, role = ?, password = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $role, $hashed_password, $id]);
            } else {
                $stmt = $connect->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, role = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $role, $id]);
            }

            header("Location: $back_url");
            exit;

        } catch (Exception $e) {
            header("Location: ?page=admin_form&entity=users&id=$id");
            exit;
        }
    }

    $error_msg = $_SESSION['error_message'] ?? '';
    unset($_SESSION['error_message']);
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
                    <label>Имя пользователя <span class="required">*</span></label>
                    <input type="text" name="username" required value="<?= htmlspecialchars($user['username']) ?>">
                </div>

                <div class="form_group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>">
                </div>

                <div class="form_group">
                    <label>Роль на сайте</label>
                    <select name="role" class="filter-select">
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Пользователь</option>
                        <option value="moderator" <?= $user['role'] === 'moderator' ? 'selected' : '' ?>>Модератор
                        </option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор
                        </option>
                    </select>
                </div>

                <div class="form_group">
                    <label>Новый пароль <small style="color:#888; font-weight:400;">(оставьте пустым, чтобы не
                            менять)</small></label>
                    <input type="password" name="new_password" placeholder="Введите новый пароль">
                </div>

                <div class="form_group">
                    <label>Дата регистрации</label>
                    <input type="text" value="<?= date('d.m.Y H:i', strtotime($user['created_at'])) ?>" disabled
                        style="background:#f5f5f5; color:#888;">
                </div>

                <div class="form_actions">
                    <a href="<?= $back_url ?>" class="btn v1 btn_outline">Отмена</a>
                    <button type="submit" class="btn v1">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
</div>