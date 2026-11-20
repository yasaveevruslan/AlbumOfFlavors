<?php
    ob_start();
    session_start();
    include('php/connect.php');
    require_once 'php/helpers.php';
    
    $page = isset($_GET['page']) ? $_GET['page'] : 'main';
    $user_role = $_SESSION['user_role'] ?? 'public';

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        $page === 'album_editor'
    ) {
        if ($user_role !== 'user') {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success' => false,
                'message' => 'Нет доступа к редактору.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $action = $_GET['action'] ?? '';

        if ($action === 'save_elements') {
            require 'php/save_album_elements.php';
            exit;
        }

        if ($action === 'add_spread') {
            require 'php/add_album_spread.php';
            exit;
        }

        if ($action === 'upload_photo') {
            require 'php/upload_album_photo.php';
            exit;
        }
    }
    
    $permissions = [
        'main' => ['public', 'user', 'moderator', 'admin'],
        'categories' => ['public', 'user', 'moderator', 'admin'],
        'category' => ['public', 'user', 'moderator', 'admin'],
        'recipe' => ['public', 'user', 'moderator', 'admin'],
        'search_page' => ['public', 'user', 'moderator', 'admin'],
        'user_profile' => ['public', 'user', 'moderator', 'admin'],

        'profile' => ['user'],
        'my_recipes' => ['user'],
        'subscriptions'=> ['user'],
        'albums' => ['user'],
        'favorites' => ['user'],
        'album_editor' => ['user'],
        'create_recipe' => ['user'],
        'edit_recipe' => ['user'],
        
        'mod_list' => ['moderator'], 
        'mod_panel' => ['moderator'],
        'mod_form' => ['moderator'],
        'admin_panel' => ['admin'],
        'admin_list' => ['admin'],

    ];

    function includePage($page, $current_role) {
        global $permissions;

        $allowed_roles = $permissions[$page] ?? ['public', 'user', 'moderator', 'admin'];
        if (!in_array($current_role, $allowed_roles)) {
            if ($current_role === 'public') {
                header('Location: ?page=main&auth_required=1');
                exit;
            } else {
                echo "<div class='container'><h1>403 - Доступ запрещён</h1><p>У вас недостаточно прав для просмотра этой страницы.</p></div>";
                return;
            }
        }

        $role_file = null;
        $default_file = "pages/{$page}.php";

        if ($current_role !== 'public') {
            $role_file = "pages/{$current_role}/{$page}.php";
        }

        if ($role_file && file_exists($role_file)) {
            include($role_file);
        } elseif (file_exists($default_file)) {
            include($default_file);
        } else {
            echo "<div class='container'><h1>404 - Страница не найдена</h1></div>";
        }
    }

    function includeHeader($page) {
        $header = match ($page) {
            'main' => 'includes/hero_main.php',
            default => 'includes/header.php',
        };
        include($header);
    }
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta http-equiv='cache-control' content='no-cache'>
    <meta http-equiv='expires' content='0'>
    <meta http-equiv='pragma' content='no-cache'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="asset/img/fav/favicon.svg" />
    <link rel="stylesheet" href="asset/css/style.css">
    <link rel="stylesheet" href="asset/css/media.css">
    <script src="asset/js/app.js" defer></script>
    <title>Альбом Вкусов</title>
</head>

<body>
    <?php includeHeader($page); ?>

    <main class="page-content">
        <?php includePage($page, $user_role); ?>
    </main>

    <?php include('includes/footer.php'); ?>
    <?php ob_end_flush(); ?>
</body>

</html>