<?php
    global $connect;
    
    if (!isset($_SESSION['user_id'])) {
        header('Location: ?page=main&auth_required=1');
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    $stmt = $connect->prepare("
        SELECT a.id, a.title, a.cover_url, a.pages_count, a.created_at,
               t.title AS template_name
        FROM albums a
        LEFT JOIN album_templates t ON a.template_id = t.id
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_templates = $connect->query("
        SELECT id, title, cover_url 
        FROM album_templates 
        WHERE is_active = 1 
        ORDER BY created_at ASC
    ");
    $templates = $stmt_templates->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <nav class="breadcrumbs">
        <a href="?page=main">Главная</a>
        <a href="?page=profile">Личный кабинет</a>
        <span>Мои альбомы</span>
    </nav>

    <div class="albums_page">
        <div class="albums_header">
            <a href="?page=profile" class="btn_back">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Назад
            </a>
            <h1 class="albums_title">Мои альбомы</h1>
            <button class="btn v1" id="openAddAlbumBtn">
                <img src="asset/img/icon_add.svg" alt="">
                Добавить альбом
            </button>
        </div>

        <div class="albums_grid">
            <a href="#" class="album_card album_card--new" id="addAlbumCard">
                <div class="album_cover album_cover--new">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.5"
                        stroke-linecap="round">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                </div>
                <span class="album_name">Новый альбом</span>
            </a>

            <?php if (!empty($albums)): ?>
            <?php foreach ($albums as $album): ?>
            <div class="album_card" data-album-id="<?= htmlspecialchars($album['id']) ?>">
                <a class="album_cover" href="?page=album_editor&id=<?= htmlspecialchars($album['id']) ?>">
                    <div class="album_spine"></div>
                    <?php if (!empty($album['cover_url'])): ?>
                    <img src="<?= htmlspecialchars($album['cover_url']) ?>"
                        alt="<?= htmlspecialchars($album['title']) ?>" class="album_cover_img">
                    <?php else: ?>
                    <div class="album_cover_placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <circle cx="8.5" cy="8.5" r="1.5" />
                            <path d="M21 15l-5-5L5 21" />
                        </svg>
                    </div>
                    <?php endif; ?>
                </a>
                <div class="album_footer">
                    <span class="album_name"><?= htmlspecialchars($album['title']) ?></span>
                    <button class="album_more" aria-label="Меню" data-album-id="<?= htmlspecialchars($album['id']) ?>">
                        <img src="asset/img/icon_more.svg" alt="">
                    </button>
                </div>

                <div class="album_actions_menu" data-album-id="<?= htmlspecialchars($album['id']) ?>">
                    <button class="menu_close" aria-label="Закрыть">
                        <img src="asset/img/icon_close.svg" alt="">
                    </button>
                    <button class="menu_item" data-action="rename"
                        data-album-id="<?= htmlspecialchars($album['id']) ?>">
                        <img src="asset/img/albums/icon_edit.svg" alt="">
                        <span>Переименовать</span>
                    </button>
                    <button class="menu_item" data-action="change_cover"
                        data-album-id="<?= htmlspecialchars($album['id']) ?>">
                        <img src="asset/img/albums/icon_cover-edit.svg" alt="">
                        <span>Изменить обложку</span>
                    </button>
                    <button class="menu_item menu_item--danger" data-action="delete"
                        data-album-id="<?= htmlspecialchars($album['id']) ?>">
                        <img src="asset/img/albums/icon_trash.svg" alt="">
                        <span>Удалить</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal-overlay" id="addAlbumOverlay"></div>
<div class="modal album-modal" id="addAlbumModal">
    <button class="modal_close" aria-label="Закрыть"></button>

    <h2 class="modal_title">Добавление альбома</h2>
    <form class="modal_form album_form" id="addAlbumForm">
        <div class="form_group">
            <label>Название альбома <span class="required">*</span></label>
            <input type="text" id="add_album_name" name="title" placeholder="Введите название альбома" required>
            <p class="error-msg" id="add_album_error" style="display:none; color:red; font-size:0.9em;"></p>
        </div>

        <div class="form_group">
            <label>Обложка альбома</label>
            <div class="cover_file_wrapper">
                <button type="button" class="btn_file" id="add_cover_btn">Выберите файл</button>
                <span class="file_name" id="add_cover_name">Файл не выбран</span>
                <input type="file" id="add_cover_input" name="cover_file" accept="image/*" hidden>
            </div>
        </div>

        <div class="cover_gallery">
            <button type="button" class="gallery_arrow gallery_arrow--left" id="addGalleryLeft">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6" />
                </svg>
            </button>
            <div class="gallery_track" id="addGalleryTrack">
                <?php foreach ($templates as $tmpl): ?>
                <div class="gallery_item" data-template-id="<?= htmlspecialchars($tmpl['id'], ENT_QUOTES, 'UTF-8') ?>"
                    data-cover="<?= htmlspecialchars($tmpl['cover_url'], ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars($tmpl['cover_url'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($tmpl['title'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="gallery_arrow gallery_arrow--right" id="addGalleryRight">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6" />
                </svg>
            </button>
        </div>
        <input type="hidden" name="template_id" id="add_selected_template" value="">

        <div class="modal_actions">
            <button type="button" class="btn_modal btn_cancel" id="add_album_cancel">Отмена</button>
            <button type="submit" class="btn_modal btn v1">Создать альбом</button>
        </div>
    </form>
</div>

<div class="modal-overlay" id="renameOverlay"></div>
<div class="modal album-modal album-modal--small" id="renameModal">
    <button class="modal_close" aria-label="Закрыть"></button>

    <h2 class="modal_title">Переименовать альбом</h2>
    <form class="modal_form album_form" id="renameForm">
        <input type="hidden" name="album_id" id="rename_album_id">
        <div class="form_group">
            <label>Название альбома <span class="required">*</span></label>
            <input type="text" id="rename_album_name" name="title" placeholder="Введите название альбома" required>
            <p class="error-msg" id="rename_error" style="display:none; color:red; font-size:0.9em;"></p>
        </div>

        <div class="modal_actions">
            <button type="button" class="btn_modal btn_cancel" id="rename_cancel">Отмена</button>
            <button type="submit" class="btn_modal btn v1">Сохранить</button>
        </div>
    </form>
</div>

<div class="modal-overlay" id="changeCoverOverlay"></div>
<div class="modal album-modal" id="changeCoverModal">
    <button class="modal_close" aria-label="Закрыть"></button>

    <h2 class="modal_title">Изменить обложку</h2>
    <form class="modal_form album_form" id="changeCoverForm">
        <input type="hidden" name="album_id" id="change_cover_album_id">

        <div class="form_group">
            <label>Загрузить свою обложку</label>
            <div class="cover_file_wrapper">
                <button type="button" class="btn_file" id="change_cover_btn">Выберите файл</button>
                <span class="file_name" id="change_cover_name">Файл не выбран</span>
                <input type="file" id="change_cover_input" name="cover_file" accept="image/*" hidden>
            </div>
        </div>

        <div class="cover_gallery">
            <button type="button" class="gallery_arrow gallery_arrow--left" id="changeGalleryLeft">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6" />
                </svg>
            </button>
            <div class="gallery_track" id="changeGalleryTrack">
                <?php foreach ($templates as $tmpl): ?>
                <div class="gallery_item" data-template-id="<?= htmlspecialchars($tmpl['id']) ?>"
                    data-cover="<?= htmlspecialchars($tmpl['cover_url']) ?>">
                    <img src="<?= htmlspecialchars($tmpl['cover_url']) ?>"
                        alt="<?= htmlspecialchars($tmpl['title']) ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="gallery_arrow gallery_arrow--right" id="changeGalleryRight">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6" />
                </svg>
            </button>
        </div>
        <input type="hidden" name="cover_url" id="change_selected_cover" value="">

        <p class="error-msg" id="change_cover_error" style="display:none; color:red; font-size:0.9em;"></p>

        <div class="modal_actions">
            <button type="button" class="btn_modal btn_cancel" id="change_cover_cancel">Отмена</button>
            <button type="submit" class="btn_modal btn v1">Сохранить</button>
        </div>
    </form>
</div>