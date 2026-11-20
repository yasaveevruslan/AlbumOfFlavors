// ОТКРЫТИЕ И ЗАКРЫТИЕ МОДАЛЬНЫХ ОКОН
function openAlbumModal(overlayId, modalId) {
    const overlay = document.getElementById(overlayId);
    const modal = document.getElementById(modalId);

    if (!overlay || !modal) {
        console.error('Не найдено модальное окно:', overlayId, modalId);
        return;
    }

    overlay.classList.add('active');
    modal.classList.add('active');
    document.body.classList.add('modal-open');
}

function closeAlbumModal(overlayId, modalId) {
    const overlay = document.getElementById(overlayId);
    const modal = document.getElementById(modalId);

    if (overlay) overlay.classList.remove('active');
    if (modal) modal.classList.remove('active');

    if (!document.querySelector('.modal.active')) {
        document.body.classList.remove('modal-open');
    }
}
// Закрытие всех меню
function closeAllAlbumMenus() {
    document.querySelectorAll('.album_actions_menu').forEach(menu => {
        menu.classList.remove('active');
    });
}

// Открытие и переключение меню
document.addEventListener('click', function (e) {

    // Нажатие на кнопку с тремя точками
    const moreBtn = e.target.closest('.album_more');

    if (moreBtn) {
        e.preventDefault();
        e.stopPropagation();

        const albumCard = moreBtn.closest('.album_card');

        if (!albumCard) return;

        const menu = albumCard.querySelector('.album_actions_menu');

        if (!menu) return;

        const isOpen = menu.classList.contains('active');

        closeAllAlbumMenus();

        if (!isOpen) {
            menu.classList.add('active');
        }
        return;
    }

    // Нажатие на крестик закрытия
    const closeBtn = e.target.closest('.menu_close');

    if (closeBtn) {
        const menu = closeBtn.closest('.album_actions_menu');
        menu.classList.remove('active');
        return;
    }

    // Нажатие на действие меню
    const menuItem = e.target.closest('.menu_item');

    if (menuItem) {
        const action = menuItem.dataset.action;
        const albumId = menuItem.dataset.albumId;
        const albumCard = menuItem.closest('.album_card');

        if (!albumCard || !albumId) return;

        if (action === 'rename') {

            const albumName = albumCard.querySelector('.album_name');

            document.getElementById('rename_album_id').value = albumId;
            document.getElementById('rename_album_name').value =
                albumName ? albumName.textContent.trim() : '';

            document.getElementById('rename_error').style.display = 'none';

            closeAllAlbumMenus();

            openAlbumModal('renameOverlay', 'renameModal');

        } else if (action === 'change_cover') {

            document.getElementById('change_cover_album_id').value = albumId;
            document.getElementById('change_cover_error').style.display = 'none';
            document.getElementById('change_selected_cover').value = '';
            document.getElementById('change_cover_name').textContent = 'Файл не выбран';

            closeAllAlbumMenus();

            openAlbumModal('changeCoverOverlay', 'changeCoverModal');

        } else if (action === 'delete') {

            closeAllAlbumMenus();

            if (confirm('Удалить этот альбом? Это действие нельзя отменить.')) {
                deleteAlbum(albumId);
            }
        }

        return;
    }

    if (!e.target.closest('.album_actions_menu')) {
        closeAllAlbumMenus();
    }
});



const renameForm = document.getElementById('renameForm');
renameForm?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const submitBtn = this.querySelector('button[type="submit"]');
    const errorEl = document.getElementById('rename_error');
    const originalText = submitBtn.innerText;

    errorEl.style.display = 'none';
    submitBtn.disabled = true;
    submitBtn.innerText = 'Сохранение...';

    const formData = new FormData(this);

    try {
        const response = await fetch('php/album_rename.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            const albumCard = document.querySelector(`.album_card[data-album-id="${formData.get('album_id')}"]`);
            if (albumCard) {
                albumCard.querySelector('.album_name').textContent = result.new_title;
            }
            closeAlbumModal('renameOverlay', 'renameModal');
        } else {
            errorEl.innerText = result.message;
            errorEl.style.display = 'block';
        }
    } catch (error) {
        console.error('Ошибка:', error);
        errorEl.innerText = 'Ошибка соединения с сервером';
        errorEl.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = originalText;
    }
});

document.getElementById('rename_cancel')?.addEventListener('click', () => {
    closeAlbumModal('renameOverlay', 'renameModal');
});

const changeCoverBtn = document.getElementById('change_cover_btn');
const changeCoverInput = document.getElementById('change_cover_input');
const changeCoverName = document.getElementById('change_cover_name');
const changeSelectedCover = document.getElementById('change_selected_cover');

changeCoverBtn?.addEventListener('click', () => changeCoverInput?.click());
changeCoverInput?.addEventListener('change', function () {
    if (this.files.length > 0) {
        changeCoverName.textContent = this.files[0].name;
        changeSelectedCover.value = '';
        document.querySelectorAll('#changeGalleryTrack .gallery_item').forEach(item => {
            item.classList.remove('selected');
        });
    } else {
        changeCoverName.textContent = 'Файл не выбран';
    }
});

document.querySelectorAll('#changeGalleryTrack .gallery_item').forEach(item => {
    item.addEventListener('click', function () {
        document.querySelectorAll('#changeGalleryTrack .gallery_item').forEach(i => i.classList.remove('selected'));
        this.classList.add('selected');
        changeSelectedCover.value = this.dataset.cover;
        changeCoverName.textContent = 'Выбрано из галереи';
        changeCoverInput.value = '';
    });
});

const changeCoverForm = document.getElementById('changeCoverForm');
changeCoverForm?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const submitBtn = this.querySelector('button[type="submit"]');
    const errorEl = document.getElementById('change_cover_error');
    const originalText = submitBtn.innerText;

    errorEl.style.display = 'none';
    submitBtn.disabled = true;
    submitBtn.innerText = 'Сохранение...';

    const formData = new FormData(this);

    try {
        const response = await fetch('php/album_change_cover.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            const albumCard = document.querySelector(`.album_card[data-album-id="${formData.get('album_id')}"]`);
            if (albumCard) {
                const coverImg = albumCard.querySelector('.album_cover_img');
                if (coverImg) {
                    coverImg.src = result.cover_url;
                } else {
                    const coverLink = albumCard.querySelector('.album_cover');
                    const placeholder = coverLink.querySelector('.album_cover_placeholder');
                    if (placeholder) placeholder.remove();

                    const img = document.createElement('img');
                    img.src = result.cover_url;
                    img.className = 'album_cover_img';
                    coverLink.appendChild(img);
                }
            }
            closeAlbumModal('changeCoverOverlay', 'changeCoverModal');
        } else {
            errorEl.innerText = result.message;
            errorEl.style.display = 'block';
        }
    } catch (error) {
        console.error('Ошибка:', error);
        errorEl.innerText = 'Ошибка соединения с сервером';
        errorEl.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = originalText;
    }
});

document.getElementById('change_cover_cancel')?.addEventListener('click', () => {
    closeAlbumModal('changeCoverOverlay', 'changeCoverModal');
});

async function deleteAlbum(albumId) {
    const formData = new FormData();
    formData.append('album_id', albumId);

    try {
        const response = await fetch('php/album_delete.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            const albumCard = document.querySelector(`.album_card[data-album-id="${albumId}"]`);
            if (albumCard) {
                albumCard.style.transition = 'opacity 0.3s, transform 0.3s';
                albumCard.style.opacity = '0';
                albumCard.style.transform = 'scale(0.9)';
                setTimeout(() => albumCard.remove(), 300);
            }
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Ошибка:', error);
        alert('Ошибка соединения с сервером');
    }
}

function setupGalleryScroll(trackId, leftBtnId, rightBtnId) {
    const track = document.getElementById(trackId);
    const leftBtn = document.getElementById(leftBtnId);
    const rightBtn = document.getElementById(rightBtnId);

    if (!track || !leftBtn || !rightBtn) return;

    leftBtn.addEventListener('click', () => {
        track.scrollBy({ left: -200, behavior: 'smooth' });
    });

    rightBtn.addEventListener('click', () => {
        track.scrollBy({ left: 200, behavior: 'smooth' });
    });
}

setupGalleryScroll('addGalleryTrack', 'addGalleryLeft', 'addGalleryRight');
setupGalleryScroll('changeGalleryTrack', 'changeGalleryLeft', 'changeGalleryRight');



// СОЗДАНИЕ АЛЬБОМА
const addAlbumBtn = document.getElementById('openAddAlbumBtn');
const addAlbumCard = document.getElementById('addAlbumCard');

const addAlbumOverlay = document.getElementById('addAlbumOverlay');
const addAlbumModal = document.getElementById('addAlbumModal');

const addAlbumForm = document.getElementById('addAlbumForm');

const addCoverBtn = document.getElementById('add_cover_btn');
const addCoverInput = document.getElementById('add_cover_input');
const addCoverName = document.getElementById('add_cover_name');

const addSelectedTemplate = document.getElementById('add_selected_template');
const addGalleryTrack = document.getElementById('addGalleryTrack');

// Открытие модального окна
function openAddAlbumModal(e) {
    if (e) e.preventDefault();

    openAlbumModal('addAlbumOverlay', 'addAlbumModal');

    closeAllAlbumMenus();
}

addAlbumBtn?.addEventListener('click', openAddAlbumModal);
addAlbumCard?.addEventListener('click', openAddAlbumModal);

// Закрытие модального окна
document.getElementById('add_album_cancel')?.addEventListener('click', () => {
    closeAlbumModal('addAlbumOverlay', 'addAlbumModal');
});

addAlbumModal?.querySelector('.modal_close')?.addEventListener('click', () => {
    closeAlbumModal('addAlbumOverlay', 'addAlbumModal');
});

addAlbumOverlay?.addEventListener('click', () => {
    closeAlbumModal('addAlbumOverlay', 'addAlbumModal');
});

// Открытие выбора собственного файла
addCoverBtn?.addEventListener('click', () => {
    addCoverInput?.click();
});

// Выбор собственного файла
addCoverInput?.addEventListener('change', function () {
    if (this.files.length > 0) {
        addCoverName.textContent = this.files[0].name;

        // Сбрасываем выбранный шаблон
        addSelectedTemplate.value = '';

        // Снимаем выделение с галереи
        addGalleryTrack?.querySelectorAll('.gallery_item').forEach(item => {
            item.classList.remove('selected');
        });
    } else {
        addCoverName.textContent = 'Файл не выбран';
    }
});

// Выбор готовой обложки из галереи
addGalleryTrack?.querySelectorAll('.gallery_item').forEach(item => {
    item.addEventListener('click', function () {

        const templateId = this.dataset.templateId;

        if (!templateId) {
            console.error('У выбранной обложки отсутствует data-template-id');
            return;
        }

        addGalleryTrack.querySelectorAll('.gallery_item').forEach(el => {
            el.classList.remove('selected');
        });

        this.classList.add('selected');

        addSelectedTemplate.value = templateId;

        addCoverInput.value = '';
        addCoverName.textContent = 'Выбрана обложка из галереи';

        console.log('Выбран ID шаблона:', templateId);
    });
});

// Отправка формы создания альбома
addAlbumForm?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const submitBtn = this.querySelector('button[type="submit"]');
    const errorEl = document.getElementById('add_album_error');

    const originalText = submitBtn.innerText;

    errorEl.style.display = 'none';

    const hasTemplate = addSelectedTemplate.value !== '';
    const hasFile = addCoverInput.files.length > 0;

    if (!hasTemplate && !hasFile) {
        errorEl.textContent = 'Выберите обложку альбома';
        errorEl.style.display = 'block';
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerText = 'Создание...';

    const formData = new FormData(this);

    try {
        const response = await fetch('php/album_create.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = result.redirect;
        } else {
            errorEl.textContent = result.message;
            errorEl.style.display = 'block';
        }

    } catch (error) {
        console.error('Ошибка создания альбома:', error);

        errorEl.textContent = 'Ошибка соединения с сервером';
        errorEl.style.display = 'block';

    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = originalText;
    }
});
