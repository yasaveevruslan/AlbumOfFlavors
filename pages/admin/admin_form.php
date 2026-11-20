<?php
    global $connect;

    $role = $_SESSION['user_role'] ?? 'user';
    if (!in_array($role, ['moderator', 'admin'])) {
        header('Location: ?page=main');
        exit;
    }

    $entity = $_GET['entity'] ?? '';
    $id = $_GET['id'] ?? null;
    
    $allowed_forms = ['users','categories', 'recipes','ingredients','album_templates','assets'];
    if (!in_array($entity, $allowed_forms)) {
        header('Location: ?page=admin_list&entity=recipes');
        exit;
    }

    $form_file = __DIR__ . '/../../includes/admin/form_' . $entity . '.php';
    if (file_exists($form_file)) {
        include $form_file;
    } else {
        echo "<div class='container'><h1>Форма для сущности '{$entity}' ещё не создана</h1></div>";
    }
?>