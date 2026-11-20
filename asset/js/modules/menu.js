const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const sideMenu = document.getElementById('sideMenu');
const menuOverlay = document.getElementById('menuOverlay');
const sideMenuClose = document.getElementById('sideMenuClose');

function openMenu() {
    if (sideMenu && menuOverlay) {
        sideMenu.classList.add('active');
        menuOverlay.classList.add('active');
        document.body.classList.add('menu-open');
    }
}

function closeMenu() {
    if (sideMenu && menuOverlay) {
        sideMenu.classList.remove('active');
        menuOverlay.classList.remove('active');
        document.body.classList.remove('menu-open');
    }
}

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', openMenu);
}

if (sideMenuClose) {
    sideMenuClose.addEventListener('click', closeMenu);
}

if (menuOverlay) {
    menuOverlay.addEventListener('click', closeMenu);
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
});



const userMenuToggle = document.getElementById('userMenuToggle');
const userMenu = document.getElementById('userMenu');

if (userMenuToggle && userMenu) {
    userMenuToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        userMenu.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
        if (!userMenu.contains(e.target)) {
            userMenu.classList.remove('open');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            userMenu.classList.remove('open');
        }
    });
}