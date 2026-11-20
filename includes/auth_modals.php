<div class="modal-overlay" id="modalOverlay"></div>
<div class="modal" id="loginModal">
    <button class="modal_close" aria-label="Закрыть"></button>

    <h2 class="modal_title">Войдите в свой аккаунт</h2>
    <form class="modal_form" id="loginForm">
        <div class="form_group">
            <label>Электронная почта <span class="required">*</span></label>
            <input type="email" name="email" placeholder="Введите электронную почту">
            <p class="error-msg" id="login-error-email" style="display:none; color:red; font-size:0.9em;"></p>
        </div>
        <div class="form_group">
            <label>Пароль <span class="required">*</span></label>
            <div class="input_with_icon">
                <input type="password" name="password" placeholder="Введите пароль">
                <button type="button" class="toggle_password eye-close" aria-label="Показать пароль">
                    <span class="eye-icon"></span>
                </button>
            </div>
            <p class="error-msg" id="login-error-password" style="display:none; color:red; font-size:0.9em;"></p>
        </div>

        <button type="submit" id="loginSubmitBtn" class="btn v1">Войти в аккаунт</button>
    </form>
    <p class="modal_switch">
        Нет аккаунта? <a href="#" data-switch="register">Зарегистрируйтесь</a>
    </p>
</div>

<div class="modal" id="registerModal">
    <button class="modal_close" aria-label="Закрыть"></button>

    <h2 class="modal_title">Регистрация</h2>
    <form class="modal_form" id="registerForm">
        <div class="form_group">
            <label>Имя пользователя <span class="required">*</span></label>
            <input type="text" name="username" placeholder="Введите имя пользователя">
            <p class="error-msg" id="reg-error-username" style="display:none; color:red; font-size:0.9em;"></p>
        </div>
        <div class="form_group">
            <label>Электронная почта <span class="required">*</span></label>
            <input type="email" name="email" placeholder="Введите электронную почту">
            <p class="error-msg" id="reg-error-email" style="display:none; color:red; font-size:0.9em;"></p>
        </div>
        <div class="form_group">
            <label>Пароль <span class="required">*</span></label>
            <div class="input_with_icon">
                <input type="password" name="password" placeholder="Введите пароль">
                <button type="button" class="toggle_password eye-close" aria-label="Показать пароль">
                    <span class="eye-icon"></span>
                </button>
            </div>
            <p class="error-msg" id="reg-error-password" style="display:none; color:red; font-size:0.9em;"></p>
        </div>
        <div class="form_group">
            <label>Подтверждение пароля <span class="required">*</span></label>
            <div class="input_with_icon">
                <input type="password" name="password_confirm" placeholder="Повторите пароль">
                <button type="button" class="toggle_password eye-close" aria-label="Показать пароль">
                    <span class="eye-icon"></span>
                </button>
            </div>
            <p class="error-msg" id="reg-error-password_confirm" style="display:none; color:red; font-size:0.9em;"></p>
        </div>
        <button type="submit" id="regSubmitBtn" class="btn v1">Зарегистрироваться</button>
    </form>
    <p class="modal_switch">
        Уже есть аккаунт? <a href="#" data-switch="login">Войти</a>
    </p>
</div>


<div class="modal-overlay" id="logoutOverlay"></div>
<div class="modal confirm-modal" id="logoutModal">
    <button class="modal_close" aria-label="Закрыть"></button>

    <h2 class="modal_title">Подтверждение действия</h2>
    <p class="confirm_text">Вы действительно хотите выйти из аккаунта?</p>

    <div class="confirm_actions">
        <button type="button" class="btn v1 btn_cancel">Нет, отмена</button>
        <button type="button" class="btn v1 btn_confirm">Да, подтвердить</button>
    </div>
</div>