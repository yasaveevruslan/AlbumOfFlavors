const overlay = document.getElementById('modalOverlay');
const loginModal = document.getElementById('loginModal');
const registerModal = document.getElementById('registerModal');
const loginBtn = document.querySelector('.auth-btn.active');
const registerBtn = document.querySelector('.auth-btn:not(.active)');

function openModal(modal) {
    if (overlay) overlay.classList.add('active');
    if (modal) modal.classList.add('active');
    document.body.classList.add('modal-open');
}

function closeModal() {
    if (overlay) overlay.classList.remove('active');
    if (loginModal) loginModal.classList.remove('active');
    if (registerModal) registerModal.classList.remove('active');
    document.body.classList.remove('modal-open');
    
    document.querySelectorAll('.modal_form').forEach(form => form.reset());
    document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');
}

function switchModal(target) {
    closeModal();
    setTimeout(() => {
        openModal(target === 'login' ? loginModal : registerModal);
    }, 150);
}

loginBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    openModal(loginModal);
});

registerBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    openModal(registerModal);
});

document.querySelectorAll('.modal_close').forEach(btn => {
    if (btn.closest('.album-modal')) return;
    btn.addEventListener('click', closeModal);
});

if (overlay) {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeModal();
    });
}

document.querySelectorAll('[data-switch]').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        switchModal(link.dataset.switch);
    });
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay?.classList.contains('active')) {
        closeModal();
    }
});


async function handleAuthSubmit(form, endpoint, errorPrefix) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerText;
    submitBtn.disabled = true;
    submitBtn.innerText = 'Обработка...';
    form.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');

    const formData = new FormData(form);

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();

        if (result.success) {
            window.location.href = result.redirect;
        } else {
            for (const [field, message] of Object.entries(result.errors)) {
                const errorEl = document.getElementById(`${errorPrefix}-error-${field}`);
                if (errorEl) {
                    errorEl.innerText = message;
                    errorEl.style.display = 'block';
                }
            }
        }
    } catch (error) {
        console.error('Ошибка сети:', error);
        alert('Произошла ошибка соединения с сервером. Попробуйте позже.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = originalText;
    }
}

const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await handleAuthSubmit(loginForm, 'php/auth_login.php', 'login');
    });
}

const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await handleAuthSubmit(registerForm, 'php/auth_register.php', 'reg');
    });
}


document.querySelectorAll('.toggle_password').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = btn.previousElementSibling;
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.classList.toggle('eye-open', isPassword);
        btn.classList.toggle('eye-close', !isPassword);
    });
});


const logoutBtn = document.querySelector('.profile_sidebar .btn.v1');
const logoutModal = document.getElementById('logoutModal');
const logoutOverlay = document.getElementById('logoutOverlay');

function openLogoutModal() {
    if (!logoutOverlay || !logoutModal) return;
    logoutOverlay.classList.add('active');
    logoutModal.classList.add('active');
    document.body.classList.add('modal-open');
}

function closeLogoutModal() {
    if (!logoutOverlay || !logoutModal) return;
    logoutOverlay.classList.remove('active');
    logoutModal.classList.remove('active');
    document.body.classList.remove('modal-open');
}

if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        openLogoutModal();
    });
}

const logoutClose = logoutModal?.querySelector('.modal_close');
if (logoutClose) logoutClose.addEventListener('click', closeLogoutModal);
if (logoutOverlay) logoutOverlay.addEventListener('click', closeLogoutModal);

const logoutCancel = logoutModal?.querySelector('.btn_cancel');
if (logoutCancel) logoutCancel.addEventListener('click', closeLogoutModal);

const logoutConfirm = logoutModal?.querySelector('.btn_confirm');
if (logoutConfirm) {
    logoutConfirm.addEventListener('click', () => {
        closeLogoutModal();
        setTimeout(() => { window.location.href = 'php/logout.php'; }, 300);
    });
}

const deleteOverlay = document.getElementById('deleteOverlay');
const deleteModal = document.getElementById('deleteModal');
const deleteBtn = document.querySelector('.btn_delete');
const deleteCancel = deleteModal?.querySelector('.btn_cancel');
const deleteConfirm = deleteModal?.querySelector('.btn_confirm');

function openDeleteModal() {
    if (!deleteOverlay || !deleteModal) return;
    deleteOverlay.classList.add('active');
    deleteModal.classList.add('active');
    document.body.classList.add('modal-open');
}

function closeDeleteModal() {
    if (!deleteOverlay || !deleteModal) return;
    deleteOverlay.classList.remove('active');
    deleteModal.classList.remove('active');
    document.body.classList.remove('modal-open');
}

if (deleteBtn) deleteBtn.addEventListener('click', openDeleteModal);

const deleteClose = deleteModal?.querySelector('.modal_close');
if (deleteClose) deleteClose.addEventListener('click', closeDeleteModal);
if (deleteOverlay) deleteOverlay.addEventListener('click', closeDeleteModal);
if (deleteCancel) deleteCancel.addEventListener('click', closeDeleteModal);
if (deleteConfirm) {
    deleteConfirm.addEventListener('click', () => {
        closeDeleteModal();
        setTimeout(() => { alert('Аккаунт удалён'); }, 300);
    });
}

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;

    if (logoutModal?.classList.contains('active')) {
        closeLogoutModal();
    } else if (deleteModal?.classList.contains('active')) {
        closeDeleteModal();
    } else if (overlay?.classList.contains('active')) {
        closeModal();
    }
});

const heroCtaLoginBtn = document.getElementById('heroCtaLoginBtn');

if (heroCtaLoginBtn) {
    heroCtaLoginBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        if (typeof openModal === 'function' && typeof loginModal !== 'undefined') {
            openModal(loginModal);
        } else {
            console.warn('Функция openModal или элемент loginModal не найдены');
        }
    });
}

const mobileLoginBtn = document.getElementById('mobileLoginBtn');
const mobileRegisterBtn = document.getElementById('mobileRegisterBtn');

if (mobileLoginBtn) {
    mobileLoginBtn.addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('sideMenu')?.classList.remove('active');
        document.getElementById('menuOverlay')?.classList.remove('active');
        if (typeof openModal === 'function' && typeof loginModal !== 'undefined') {
            openModal(loginModal);
        }
    });
}

if (mobileRegisterBtn) {
    mobileRegisterBtn.addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('sideMenu')?.classList.remove('active');
        document.getElementById('menuOverlay')?.classList.remove('active');
        if (typeof openModal === 'function' && typeof registerModal !== 'undefined') {
            openModal(registerModal);
        }
    });
}

const profileMenuItem = document.querySelector('.side_menu_item[href="#"]');
if (profileMenuItem && loginModal) {
    profileMenuItem.addEventListener('click', (e) => {
        e.preventDefault();
        setTimeout(() => {
            openModal(loginModal);
        }, 300);
    });
}