<?php
    global $connect;

    $is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    $username = '';
    $user_role = 'guest';
    $avatar_url = '';
    $avatar_letter = '';
    $user = null;

    if ($is_logged_in) {
        $stmt = $connect->prepare("
            SELECT username, avatar_url, role 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $username = $user['username'];
            $user_role = $user['role'] ?? 'user';
            $avatar_url = $user['avatar_url'] ?? '';
            $avatar_letter = mb_strtoupper(mb_substr($username, 0, 1));
        } else {
            $is_logged_in = false;
            session_unset();
            session_destroy();
        }
    }
?>

<header class="v2 desktop_header">
    <div class="header_container">
        <a href="?page=main">
            <div class="logo_container"></div>
        </a>

        <div class="search_block">
            <a href="?page=categories"><button class="menu_btn" aria-label="Категории"></button></a>

            <div class="search_input" id="headerSearchWrapper">
                <form class="header_search_form" id="headerSearchForm">
                    <input type="text" name="q" placeholder="Введите, например, бульон..." id="headerSearchInput"
                        autocomplete="off">
                </form>
                <div class="search_results_dropdown" id="headerSearchResultsDropdown" style="display: none;">
                    <div class="search_results_list" id="headerSearchResultsList"></div>
                    <div class="search_results_footer" id="headerSearchResultsFooter" style="display: none;">
                        <a href="#" id="headerSearchSeeAllLink" class="search_see_all">Показать все результаты →</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth_buttons">
            <?php if ($is_logged_in): ?>
            <div class="user_menu" id="userMenu">
                <button class="user_menu_toggle" id="userMenuToggle" aria-label="Меню пользователя">
                    <div class="user_avatar_mini">
                        <?php if (!empty($user['avatar_url'])): ?>
                        <img class='profile_avatar' src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Аватар">
                        <?php else: ?>
                        <span><?= htmlspecialchars($avatar_letter) ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="user_name_mini"><?= htmlspecialchars($username) ?></span>
                    <svg class="chevron_mini" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </button>

                <div class="user_menu_dropdown" id="userMenuDropdown">
                    <div class="dropdown_header">
                        <div class="user_avatar_large">
                            <?php if (!empty($user['avatar_url'])): ?>
                            <img class='profile_avatar' src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Аватар">
                            <?php else: ?>
                            <span><?= htmlspecialchars($avatar_letter) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="user_info">
                            <div class="user_name"><?= htmlspecialchars($username) ?></div>
                            <div class="user_role"><?= htmlspecialchars(getUserRoleName($user_role)) ?></div>
                        </div>
                    </div>
                    <?php if ($user_role === 'user'): ?>
                    <div class="dropdown_divider"></div>
                    <a href="?page=profile" class="dropdown_item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <span>Мой профиль</span>
                    </a>
                    <a href="?page=ideas" class="dropdown_item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                        </svg>
                        <span>Избранное</span>
                    </a>
                    <?php elseif ($user_role === 'moderator'): ?>
                    <a href="?page=mod_panel" class="dropdown_item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <circle cx="12" cy="12" r="3" />
                            <path
                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                        <span>Админ-панель</span>
                    </a>
                    <?php elseif ($user_role === 'admin'): ?>
                    <a href="?page=admin_panel" class="dropdown_item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <circle cx="12" cy="12" r="3" />
                            <path
                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                        <span>Админ-панель</span>
                    </a>
                    <?php endif; ?>
                    <div class="dropdown_divider"></div>
                    <a href="php/logout.php" class="dropdown_item dropdown_item_logout" id="logoutLink">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
                        <span>Выйти</span>
                    </a>
                </div>
            </div>
            <?php else: ?>
            <a href="#" class="auth-btn active" id="loginBtn">Войти</a>
            <a href="#" class="auth-btn" id="registerBtn">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<header class="v2 mobile_header">
    <div class="mobile_header_top">
        <a href="?page=main" class="mobile_logo">
            <div class="logo_container"></div>
        </a>

        <button class="menu_btn mobile" id="mobileMenuBtn" aria-label="Открыть меню"></button>
    </div>
    <div class="mobile_search">
        <div class="mobile_search_input">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.35-4.35" />
            </svg>
            <input type="text" placeholder="Введите, например, бульон...">
        </div>
    </div>
</header>

<div class="menu_overlay" id="menuOverlay"></div>
<aside class="side_menu" id="sideMenu">
    <div class="side_menu_header">
        <h3 class="side_menu_title">Меню</h3>
        <button class="side_menu_close" id="sideMenuClose" aria-label="Закрыть меню">
            <img src="asset/img/icon_close.svg" alt="close">
        </button>
    </div>

    <?php if ($is_logged_in): ?>
    <div class="side_menu_user">
        <div class="side_menu_user_avatar">
            <?php if (!empty($user['avatar_url'])): ?>
            <img class='profile_avatar' src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Аватар">
            <?php else: ?>
            <span><?= htmlspecialchars($avatar_letter) ?></span>
            <?php endif; ?>
        </div>
        <div class="side_menu_user_info">
            <div class="side_menu_user_name"><?= htmlspecialchars($user['username']) ?></div>
            <?php if ($user_role === 'user'): ?>
            <a href="?page=profile" class="side_menu_user_link">Перейти в профиль</a>
            <?php else: ?>
            <div class="user_role"><?= htmlspecialchars(getUserRoleName($user_role)) ?></div>
            <?php endif; ?>
        </div>

    </div>
    <?php endif; ?>

    <nav class="side_menu_nav">
        <a href="?page=main" class="side_menu_item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
            <span>Главная</span>
        </a>
        <a href="?page=categories" class="side_menu_item">
            <img src="asset/img/icon_menu_mob.svg" alt="categories">
            <span>Категории</span>
        </a>
        <?php if ($is_logged_in && $user_role === 'user'): ?>
        <a href="?page=profile" class="side_menu_item">
            <img src="asset/img/icon_user.svg" alt="profile">
            <span>Профиль</span>
        </a>
        <a href="?page=ideas" class="side_menu_item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path
                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
            <span>Избранное</span>
        </a>
        <?php elseif ($is_logged_in && $user_role === 'moderator'): ?>
        <a href="?page=mod_panel" class="side_menu_item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3" />
                <path
                    d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
            </svg>
            <span>Админ-панель</span>
        </a>
        <?php elseif ($is_logged_in &&  $user_role === 'admin'): ?>
        <a href="?page=admin_panel" class="side_menu_item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3" />
                <path
                    d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
            </svg>
            <span>Админ-панель</span>
        </a>
        <?php endif; ?>
        <a href="?page=search_page" class="side_menu_item">
            <img src="asset/img/icon_search_mob.svg" alt="search">
            <span>Поиск рецептов</span>
        </a>

        <?php if ($is_logged_in): ?>
        <div class="side_menu_divider"></div>
        <a href="php/logout.php" class="side_menu_item side_menu_logout">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
            <span>Выйти</span>
        </a>
        <?php else: ?>
        <div class="side_menu_divider"></div>
        <a href="#" class="side_menu_item" id="mobileLoginBtn">
            <img src="asset/img/icon_user.svg" alt="login">
            <span>Войти</span>
        </a>
        <a href="#" class="side_menu_item" id="mobileRegisterBtn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="8.5" cy="7" r="4" />
                <line x1="20" y1="8" x2="20" y2="14" />
                <line x1="23" y1="11" x2="17" y2="11" />
            </svg>
            <span>Регистрация</span>
        </a>
        <?php endif; ?>
    </nav>
</aside>

<?php 
    include('auth_modals.php'); 
?>