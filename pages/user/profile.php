<?php
    $active_nav = 'profile';
?>

<div class="container">
    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <span>Личный кабинет</span>
    </nav>

    <?php include __DIR__ . '/../../includes/profile_header.php'; ?>

    <div class="profile_container">
        <?php include __DIR__ . '/../../includes/profile_sidebar.php'; ?>

        <div class="profile_content">
            <form class="profile_form" id="profileForm">
                <div class="forms_row">
                    <div class="form_section">
                        <h2 class="section_title">Основная информация</h2>
                        <div class="form_group">
                            <label>Имя пользователя <span class="required">*</span></label>
                            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"
                                placeholder="Введите имя пользователя">
                            <p class="error-msg" id="profile-error-username"
                                style="display:none; color:red; font-size:0.9em;"></p>
                        </div>
                        <div class="form_group">
                            <label>Электронная почта <span class="required">*</span></label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                                placeholder="Введите email">
                            <p class="error-msg" id="profile-error-email"
                                style="display:none; color:red; font-size:0.9em;"></p>
                        </div>
                        <p class="form-success" id="profile-success"
                            style="display:none; color:green; font-size:0.9em; margin-top:8px;"></p>
                        <button type="submit" class="btn v1">Сохранить</button>
                    </div>

                    <div class="form_section">
                        <h2 class="section_title">Смена пароля</h2>
                        <div class="form_group">
                            <label>Текущий пароль <span class="required">*</span></label>
                            <div class="input_with_icon">
                                <input type="password" name="current_password" placeholder="Введите текущий пароль">
                                <button type="button" class="toggle_password eye-close" aria-label="Показать пароль">
                                    <span class="eye-icon"></span>
                                </button>
                            </div>
                        </div>
                        <div class="form_group">
                            <label>Новый пароль <span class="required">*</span></label>
                            <div class="input_with_icon">
                                <input type="password" name="new_password" placeholder="Введите новый пароль">
                                <button type="button" class="toggle_password eye-close" aria-label="Показать пароль">
                                    <span class="eye-icon"></span>
                                </button>
                            </div>
                        </div>
                        <div class="form_group">
                            <label>Подтверждение пароля <span class="required">*</span></label>
                            <div class="input_with_icon">
                                <input type="password" name="confirm_password" placeholder="Повторите новый пароль">
                                <button type="button" class="toggle_password eye-close" aria-label="Показать пароль">
                                    <span class="eye-icon"></span>
                                </button>
                            </div>
                        </div>
                        <p class="error-msg" id="password-error" style="display:none; color:red; font-size:0.9em;"></p>
                        <p class="form-success" id="password-success"
                            style="display:none; color:green; font-size:0.9em; margin-top:8px;"></p>
                        <button type="button" class="btn v1" id="changePasswordBtn">Сменить пароль</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>