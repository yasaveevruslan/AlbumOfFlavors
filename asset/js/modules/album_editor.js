
(() => {
    'use strict';

    // 1. ПОЛУЧЕНИЕ ЭЛЕМЕНТОВ ИНТЕРФЕЙСА
    const verticalTabs = document.querySelectorAll('.sidebar_tab_vertical');
    const sidebarContents = document.querySelectorAll('.sidebar_content');

    const contentTabs = document.querySelectorAll('.content_tab');
    const contentPanels = document.querySelectorAll('.content_panel');

    const zoomLevelEl = document.querySelector('.zoom_level');
    const zoomInBtn = document.getElementById('zoom_in');
    const zoomOutBtn = document.getElementById('zoom_out');

    const bookSpread = document.querySelector('.book_spread');

    const prevSpreadBtn = document.getElementById('prev_spread');
    const nextSpreadBtn = document.getElementById('next_spread');
    const spreadInfoEl = document.querySelector('.spread_info');

    const saveBtn = document.querySelector('.btn_save');
    const uploadPhotoBtn = document.querySelector('.btn_upload_photo');

    const undoBtn = document.querySelector('.canvas_tool[title="Отменить"]');
    const redoBtn = document.querySelector('.canvas_tool[title="Повторить"]');

    const editorPages = document.querySelectorAll('.book_page');


    const canvasToolButtons = document.querySelectorAll('.canvas_tool_text');

    let currentCanvasMode = 'select';

    function setCanvasMode(mode) {

        currentCanvasMode = mode;

        canvasToolButtons.forEach(button => {
            button.classList.remove('active');
        });

        const activeButton = [...canvasToolButtons].find(button => {
            return button.textContent.trim().includes(
                mode === 'hand' ? 'Рука' : 'Выбор'
            );
        });

        if (activeButton) {
            activeButton.classList.add('active');
        }

        editorPages.forEach(page => {
            page.classList.toggle('hand_mode', mode === 'hand');
        });
    }

    // Переключение кнопок
    canvasToolButtons.forEach(button => {

        button.addEventListener('click', () => {

            const isHand = button.textContent.trim().includes('Рука');

            setCanvasMode(isHand ? 'hand' : 'select');

        });

    });

    // СОСТОЯНИЕ РЕДАКТОРА
    let draggedAsset = null;
    let selectedElement = null;
    let pointerAction = null;


    // ИСТОРИЯ ДЕЙСТВИЙ: UNDO / REDO
    const MAX_HISTORY = 100;

    let editorHistory = [];
    let historyIndex = -1;

    // Снимаем снимок всех страниц и их элементов.
    function captureEditorState() {

        return [...editorPages].map(page => ({

            pageId: page.dataset.pageId,

            elements: [...page.querySelectorAll('.placed_element')]
                .map(element => element.cloneNode(true))

        }));

    }


    // Сравниваем два состояния.
    function getHistorySignature(state) {

        return JSON.stringify(
            state.map(page => ({
                pageId: page.pageId,
                elements: page.elements.map(element => element.outerHTML)
            }))
        );

    }

    // Обновляем доступность кнопок.
    function updateHistoryButtons() {

        if (undoBtn) {
            undoBtn.disabled = historyIndex <= 0;
        }

        if (redoBtn) {
            redoBtn.disabled =
                historyIndex >= editorHistory.length - 1;
        }

    }

    // Записываем новое действие.
    function recordHistory() {

        const newState = captureEditorState();

        const currentState = editorHistory[historyIndex];

        if (
            currentState &&
            getHistorySignature(currentState) ===
            getHistorySignature(newState)
        ) {
            return;
        }

        // После отмены новое действие удаляет будущую историю.
        editorHistory = editorHistory.slice(0, historyIndex + 1);

        editorHistory.push(newState);

        // Ограничиваем историю последними 100 состояниями.
        if (editorHistory.length > MAX_HISTORY) {
            editorHistory.shift();
        }

        historyIndex = editorHistory.length - 1;

        updateHistoryButtons();

        // После изменения редактора запускаем автосохранение.
        scheduleAutoSave();

    }

    // Восстанавливаем состояние редактора.
    function restoreEditorState(state) {

        state.forEach(savedPage => {

            const page = [...editorPages].find(
                item => item.dataset.pageId === savedPage.pageId
            );

            if (!page) {
                return;
            }

            // Удаляем текущие элементы страницы.
            page.querySelectorAll('.placed_element').forEach(element => {
                element.remove();
            });

            // Возвращаем элементы из снимка.
            savedPage.elements.forEach(savedElement => {

                const element = savedElement.cloneNode(true);

                element.classList.remove('selected');

                page.appendChild(element);

            });

        });

        selectEditorElement(null);

        pointerAction = null;

    }

    // Отменить действие.
    function undoEditorAction() {

        if (historyIndex <= 0) {
            return;
        }

        historyIndex--;

        restoreEditorState(editorHistory[historyIndex]);

        updateHistoryButtons();
        scheduleAutoSave();
    }

    // Повторить действие.
    function redoEditorAction() {

        if (historyIndex >= editorHistory.length - 1) {
            return;
        }

        historyIndex++;

        restoreEditorState(editorHistory[historyIndex]);

        updateHistoryButtons();
        scheduleAutoSave();

    }

    // Подключаем кнопки.
    if (undoBtn) {
        undoBtn.addEventListener('click', undoEditorAction);
    }

    if (redoBtn) {
        redoBtn.addEventListener('click', redoEditorAction);
    }

    // Начальное состояние редактора.
    editorHistory.push(captureEditorState());
    historyIndex = 0;

    updateHistoryButtons();


    // 2. ПЕРЕКЛЮЧЕНИЕ БОКОВЫХ ВКЛАДОК
    verticalTabs.forEach(tab => {
        tab.addEventListener('click', () => {

            verticalTabs.forEach(item => {
                item.classList.remove('active');
            });

            sidebarContents.forEach(item => {
                item.classList.remove('active');
            });

            tab.classList.add('active');

            const target = tab.dataset.tab;
            const content = document.getElementById(`tab-${target}`);

            if (content) {
                content.classList.add('active');
            }
        });
    });

    // 3. ПЕРЕКЛЮЧЕНИЕ ВКЛАДОК КОНТЕНТА
    contentTabs.forEach(tab => {
        tab.addEventListener('click', () => {

            contentTabs.forEach(item => {
                item.classList.remove('active');
            });

            contentPanels.forEach(item => {
                item.classList.remove('active');
            });

            tab.classList.add('active');

            const target = tab.dataset.contentTab;
            const panel = document.getElementById(`content-panel-${target}`);

            if (panel) {
                panel.classList.add('active');
            }
        });
    });

    // 4. ВЫБОР ЭЛЕМЕНТОВ В БИБЛИОТЕКЕ
    function setupSelection(selector, activeClass) {

        document.querySelectorAll(selector).forEach(item => {

            item.addEventListener('click', () => {

                document.querySelectorAll(selector).forEach(other => {
                    other.classList.remove(activeClass);
                });

                item.classList.add(activeClass);
            });

        });
    }

    setupSelection('.photo_item', 'photo_item--selected');
    setupSelection('.card_template', 'card_template--selected');
    setupSelection('.sticker_item', 'sticker_item--selected');

    // 5. МАСШТАБИРОВАНИЕ РАЗВОРОТА
    let currentZoom = 85;

    function updateZoom() {

        if (zoomLevelEl) {
            zoomLevelEl.textContent = `${currentZoom}%`;
        }

        if (bookSpread) {
            bookSpread.style.transform = `scale(${currentZoom / 100})`;
        }
    }

    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', () => {

            if (currentZoom < 150) {
                currentZoom += 5;
                updateZoom();
            }

        });
    }

    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', () => {

            if (currentZoom > 50) {
                currentZoom -= 5;
                updateZoom();
            }

        });
    }

    updateZoom();

    // 6. НАВИГАЦИЯ ПО РАЗВОРОТАМ
    const spreadIndexes = [...editorPages]
        .map(page => Number(page.dataset.spreadIndex))
        .filter(Number.isFinite);

    const totalSpreads = spreadIndexes.length
        ? Math.max(...spreadIndexes) + 1
        : 0;

    const requestedSpread = Number(
        new URLSearchParams(window.location.search).get('spread')
    );

    let currentSpreadIndex =
        Number.isInteger(requestedSpread) &&
            requestedSpread >= 0 &&
            requestedSpread < totalSpreads
            ? requestedSpread
            : 0;

    function updateSpreadInfo() {

        editorPages.forEach(page => {

            const pageSpreadIndex = Number(page.dataset.spreadIndex);

            page.style.display =
                pageSpreadIndex === currentSpreadIndex ? 'block' : 'none';

        });

        const firstPageNumber = currentSpreadIndex * 2 + 2;
        const secondPageNumber = firstPageNumber + 1;

        if (spreadInfoEl) {
            const totalPages = editorPages.length + 1;

            spreadInfoEl.textContent =
                `${firstPageNumber}–${secondPageNumber} из ${totalPages}`;
        }

        if (prevSpreadBtn) {
            prevSpreadBtn.disabled = currentSpreadIndex <= 0;
        }

        if (nextSpreadBtn) {
            nextSpreadBtn.disabled =
                currentSpreadIndex >= totalSpreads - 1;
        }

        // Снимаем выделение при переходе на другой разворот.
        selectEditorElement(null);
    }


    if (prevSpreadBtn) {

        prevSpreadBtn.addEventListener('click', async () => {

            if (currentSpreadIndex <= 0) {
                return;
            }

            // Сначала сохраняем изменения.
            if (changeVersion !== savedVersion) {

                const saved = await saveAlbum(false);

                if (!saved) {
                    return;
                }

            }

            currentSpreadIndex--;

            updateSpreadInfo();

        });

    }

    if (nextSpreadBtn) {

        nextSpreadBtn.addEventListener('click', async () => {

            if (currentSpreadIndex >= totalSpreads - 1) {
                return;
            }

            // Сначала сохраняем изменения.
            if (changeVersion !== savedVersion) {

                const saved = await saveAlbum(false);

                if (!saved) {
                    return;
                }

            }

            currentSpreadIndex++;

            updateSpreadInfo();

        });

    }


    updateSpreadInfo();


    // 7. СОХРАНЕНИЕ И АВТОСОХРАНЕНИЕ
    let autosaveTimer = null;
    let isSaving = false;

    // Версия изменений в редакторе.
    let changeVersion = 0;
    let savedVersion = 0;

    const AUTOSAVE_DELAY = 1200;

    const statusText = document.querySelector('.status_text');

    function updateSaveStatus(message) {
        if (statusText) {
            statusText.textContent = message;
        }
    }

    // Вызывается после любого изменения редактора.
    function scheduleAutoSave() {

        changeVersion++;

        updateSaveStatus('Есть несохранённые изменения');

        clearTimeout(autosaveTimer);

        autosaveTimer = setTimeout(() => {
            saveAlbum(false);
        }, AUTOSAVE_DELAY);
    }

    // Собираем страницы, группируя их по разворотам.
    function collectPagesForSave() {

        const pagesBySpread = new Map();

        editorPages.forEach(page => {

            const pageId = page.dataset.pageId;
            const spreadIndex = Number(page.dataset.spreadIndex);

            if (!pageId || !Number.isFinite(spreadIndex)) {
                return;
            }

            if (!pagesBySpread.has(spreadIndex)) {
                pagesBySpread.set(spreadIndex, []);
            }

            const elements = [];

            page.querySelectorAll('.placed_element').forEach(element => {
                console.log('Данные карточки перед сохранением:', {
                    type: element.dataset.type,
                    template: element.dataset.template,
                    recipeId: element.dataset.recipeId,
                    recipeUrl: element.dataset.recipeUrl,
                    calories: element.dataset.recipeCalories
                });
                elements.push({
                    asset_id: element.dataset.type === 'card'
                        ? null
                        : (element.dataset.assetId || null),

                    type: element.dataset.type || '',
                    template: element.dataset.template || '',

                    x: Number(element.dataset.x),
                    y: Number(element.dataset.y),
                    width: Number(element.dataset.width),
                    height: Number(element.dataset.height),
                    rotation: Number(element.dataset.rotation) || 0,
                    z_index: Number(element.style.zIndex) || 1,


                    data: {
                        template: element.dataset.template || '',

                        title: element.querySelector('.recipe_card_title')?.innerText.trim() || '',

                        text: element.querySelector('.recipe_card_subtitle')?.innerText.trim() || '',

                        time: element.querySelector('.recipe_card_time')?.innerText.trim() || '',

                        servings: element.querySelector('.recipe_card_servings')?.innerText.trim() || '',

                        recipe_id: element.dataset.recipeId || '',
                        cover_url: element.dataset.recipeUrl || '',
                        calories: element.dataset.recipeCalories || ''
                    }

                });

            });

            pagesBySpread.get(spreadIndex).push({
                page_id: pageId,
                elements: elements
            });

        });

        return [...pagesBySpread.entries()]
            .sort((a, b) => a[0] - b[0]);
    }

    // Основная функция сохранения.
    async function saveAlbum(manual = false) {

        clearTimeout(autosaveTimer);

        if (isSaving) {
            return false;
        }

        // Нечего сохранять.
        if (changeVersion === savedVersion) {
            if (manual) {
                updateSaveStatus('Все изменения сохранены');
            }

            return true;
        }

        isSaving = true;

        const versionToSave = changeVersion;

        if (manual && saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Сохранение...';
        }

        updateSaveStatus('Сохранение...');

        try {

            const pagesBySpread = collectPagesForSave();

            if (pagesBySpread.length === 0) {
                throw new Error('Не удалось определить страницы альбома.');
            }

            const url = new URL(window.location.href);
            url.searchParams.set('action', 'save_elements');

            for (const [spreadIndex, pages] of pagesBySpread) {

                const response = await fetch(url.toString(), {

                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },

                    body: JSON.stringify({
                        csrf_token: album_config.csrf_token,
                        pages: pages
                    })

                });

                const responseText = await response.text();

                let result;

                try {
                    result = JSON.parse(responseText);
                } catch (error) {
                    throw new Error(
                        'Сервер вернул некорректный ответ. Проверь PHP-обработчик.'
                    );
                }

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message ||
                        `Ошибка сохранения разворота ${spreadIndex + 1}.`
                    );
                }

            }

            savedVersion = versionToSave;

            const now = new Date();

            const date = now.toLocaleDateString('ru-RU');

            const time = now.toLocaleTimeString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit'
            });

            updateSaveStatus(`Сохранено ${date} ${time}`);

            if (saveBtn) {
                saveBtn.textContent = 'Сохранено';
            }

            return true;

        } catch (error) {

            console.error('Ошибка сохранения альбома:', error);

            updateSaveStatus('Ошибка сохранения');

            if (manual) {
                alert(error.message || 'Не удалось сохранить альбом.');
            }

            return false;

        } finally {

            isSaving = false;

            if (saveBtn) {
                saveBtn.disabled = false;

                if (manual) {
                    setTimeout(() => {
                        saveBtn.textContent = 'Сохранить';
                    }, 1500);
                }
            }

            if (changeVersion > savedVersion && savedVersion === versionToSave) {

                clearTimeout(autosaveTimer);

                autosaveTimer = setTimeout(() => {
                    saveAlbum(false);
                }, 500);

            }

        }

    }

    // Сохранение по кнопке.
    if (saveBtn) {

        saveBtn.addEventListener('click', () => {
            saveAlbum(true);
        });

    }

    // Загрузка фотографий.
    if (uploadPhotoBtn) {

        // Создаём скрытый input для выбора фотографии.
        const photoInput = document.createElement('input');

        photoInput.type = 'file';
        photoInput.accept = 'image/jpeg,image/png,image/webp';
        photoInput.hidden = true;

        document.body.appendChild(photoInput);

        // Открываем выбор файла.
        uploadPhotoBtn.addEventListener('click', () => {

            photoInput.click();

        });

        // Загружаем выбранную фотографию.
        photoInput.addEventListener('change', async () => {

            const file = photoInput.files[0];

            if (!file) {
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                alert('Размер фотографии не должен превышать 10 МБ.');
                photoInput.value = '';
                return;
            }

            // Сначала дожидаемся завершения текущего сохранения.
            let attempts = 0;

            while (isSaving && attempts < 100) {
                await new Promise(resolve => setTimeout(resolve, 100));
                attempts++;
            }

            if (isSaving) {
                alert('Дождитесь завершения сохранения альбома.');
                photoInput.value = '';
                return;
            }

            // Если есть несохранённые изменения, сохраняем их.
            if (changeVersion !== savedVersion) {

                const saved = await saveAlbum(false);

                if (!saved) {
                    alert('Сначала необходимо сохранить изменения альбома.');
                    photoInput.value = '';
                    return;
                }

            }

            const originalButtonText = uploadPhotoBtn.innerHTML;

            uploadPhotoBtn.disabled = true;
            uploadPhotoBtn.textContent = 'Загрузка...';

            try {

                const formData = new FormData();

                formData.append('photo', file);
                formData.append('csrf_token', album_config.csrf_token);

                const url = new URL(window.location.href);

                url.searchParams.set('action', 'upload_photo');

                const response = await fetch(url.toString(), {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();

                let result;

                try {
                    result = JSON.parse(responseText);
                } catch (error) {
                    throw new Error('Сервер вернул некорректный ответ.');
                }

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Не удалось загрузить фотографию.'
                    );
                }

                // Добавляем новую фотографию в библиотеку.
                const photo = result.photo;

                const photosGrid = document.querySelector('.photos_grid');

                if (!photosGrid) {
                    throw new Error('Не найдена библиотека фотографий.');
                }

                // Убираем сообщение о пустой библиотеке.
                const emptyMessage = photosGrid.querySelector('.photos_empty');

                if (emptyMessage) {
                    emptyMessage.remove();
                }

                const photoItem = document.createElement('div');

                photoItem.className = 'photo_item';
                photoItem.draggable = true;

                photoItem.dataset.id = photo.id;
                photoItem.dataset.type = photo.type;
                photoItem.dataset.url = photo.url;

                // Рассчитываем размеры с сохранением пропорций.
                const imageWidth = Number(photo.width) || 200;
                const imageHeight = Number(photo.height) || 200;

                const scale = Math.min(
                    200 / imageWidth,
                    200 / imageHeight,
                    1
                );

                photoItem.dataset.width = Math.round(imageWidth * scale);
                photoItem.dataset.height = Math.round(imageHeight * scale);

                const image = document.createElement('img');

                image.src = photo.url;
                image.alt = photo.name || 'Моя фотография';
                image.draggable = false;

                const check = document.createElement('div');

                check.className = 'photo_check';

                const checkImage = document.createElement('img');

                checkImage.src = '../asset/img/icon_check.svg';
                checkImage.alt = '';

                check.appendChild(checkImage);

                photoItem.appendChild(image);
                photoItem.appendChild(check);

                photosGrid.prepend(photoItem);

                // Подключаем перетаскивание для новой фотографии.
                setupDraggableItem(photoItem);

                // Подключаем выделение новой фотографии.
                photoItem.addEventListener('click', () => {

                    document.querySelectorAll('.photo_item').forEach(item => {
                        item.classList.remove('photo_item--selected');
                    });

                    photoItem.classList.add('photo_item--selected');

                });

                photoItem.click();

                // Обновляем количество фотографий.
                const countElement = document.querySelector(
                    '#content-panel-images .content_section_count'
                );

                if (countElement) {
                    countElement.textContent =
                        `${photosGrid.querySelectorAll('.photo_item').length} фото`;
                }

                alert('Фотография успешно загружена! Теперь её можно перетащить на страницу.');

            } catch (error) {

                console.error('Ошибка загрузки фотографии:', error);

                alert(error.message || 'Не удалось загрузить фотографию.');

            } finally {

                uploadPhotoBtn.disabled = false;
                uploadPhotoBtn.innerHTML = originalButtonText;

                photoInput.value = '';

            }

        });

    }


    // Предупреждаем о несохранённых изменениях при закрытии страницы.
    window.addEventListener('beforeunload', event => {

        if (changeVersion !== savedVersion) {

            event.preventDefault();
            event.returnValue = '';

        }

    });

    // ДОБАВЛЕНИЕ НОВОГО РАЗВОРОТА
    const addSpreadBtn = document.querySelector('.btn_add_spread');

    if (addSpreadBtn) {

        addSpreadBtn.addEventListener('click', async () => {

            if (addSpreadBtn.disabled) {
                return;
            }

            addSpreadBtn.disabled = true;
            addSpreadBtn.textContent = 'Добавление...';

            try {

                // Если сейчас выполняется сохранение, ждём его завершения.
                let attempts = 0;

                while (isSaving && attempts < 100) {
                    await new Promise(resolve => setTimeout(resolve, 100));
                    attempts++;
                }

                if (isSaving) {
                    throw new Error('Дождитесь завершения сохранения.');
                }

                // Сначала сохраняем текущие изменения.
                const saved = await saveAlbum(false);

                if (!saved) {
                    throw new Error(
                        'Не удалось сохранить изменения. Новый разворот не добавлен.'
                    );
                }

                // Отправляем запрос на создание разворота.
                const url = new URL(window.location.href);

                url.searchParams.set('action', 'add_spread');

                const response = await fetch(url.toString(), {

                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },

                    body: JSON.stringify({
                        csrf_token: album_config.csrf_token
                    })

                });

                const responseText = await response.text();

                let result;

                try {
                    result = JSON.parse(responseText);
                } catch (error) {
                    throw new Error('Сервер вернул некорректный ответ.');
                }

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Не удалось добавить разворот.'
                    );
                }

                // Открываем только что созданный разворот.
                const redirectUrl = new URL(window.location.href);

                redirectUrl.searchParams.delete('action');
                redirectUrl.searchParams.set('spread', result.spread_index);

                window.location.href = redirectUrl.toString();

            } catch (error) {

                console.error('Ошибка добавления разворота:', error);

                alert(error.message || 'Не удалось добавить разворот.');

                addSpreadBtn.disabled = false;
                addSpreadBtn.innerHTML = `
                <img src="../asset/img/icon_plus.svg">
                Добавить разворот
            `;

            }

        });

    }

    // 9. МАСШТАБ СТРАНИЦЫ
    function getPageScale(page) {

        const rect = page.getBoundingClientRect();

        return {
            x: rect.width / page.offsetWidth,
            y: rect.height / page.offsetHeight
        };
    }

    // 10. КООРДИНАТЫ ВНУТРИ СТРАНИЦЫ
    function getLocalPosition(event, page) {

        const rect = page.getBoundingClientRect();
        const scale = getPageScale(page);

        return {
            x: (event.clientX - rect.left) / scale.x,
            y: (event.clientY - rect.top) / scale.y
        };
    }

    // 11. ВЫДЕЛЕНИЕ ЭЛЕМЕНТА
    function selectEditorElement(element) {

        if (selectedElement) {
            selectedElement.classList.remove('selected');
        }

        selectedElement = element;

        if (selectedElement) {
            selectedElement.classList.add('selected');
        }
    }

    window.selectEditorElement = selectEditorElement;

    // 12. СОЗДАНИЕ ЭЛЕМЕНТА НА СТРАНИЦЕ
    function createPlacedElement(page, asset, x, y) {

        const width = Math.max(20, Number(asset.width) || 100);
        const height = Math.max(20, Number(asset.height) || 100);

        const element = document.createElement('div');

        element.className = 'placed_element';

        element.dataset.id = '';
        element.dataset.assetId = asset.id;
        element.dataset.type = asset.type;
        element.dataset.url = asset.url;

        element.dataset.x = x;
        element.dataset.y = y;
        element.dataset.width = width;
        element.dataset.height = height;
        element.dataset.rotation = 0;

        element.style.left = `${x}px`;
        element.style.top = `${y}px`;
        element.style.width = `${width}px`;
        element.style.height = `${height}px`;
        element.style.transform = 'rotate(0deg)';

        const image = document.createElement('img');

        image.src = asset.url;
        image.alt = '';
        image.draggable = false;

        element.appendChild(image);

        // Угловые маркеры и маркер поворота
        ['nw', 'ne', 'sw', 'se', 'rotate'].forEach(type => {

            const handle = document.createElement('div');

            handle.className = `element_handle element_handle--${type}`;
            handle.dataset.action = type;

            element.appendChild(handle);
        });

        const existingElements = page.querySelectorAll('.placed_element');

        let maxZIndex = 0;

        existingElements.forEach(item => {
            maxZIndex = Math.max(
                maxZIndex,
                Number(item.style.zIndex) || 0
            );
        });

        element.style.zIndex = maxZIndex + 1;
        page.appendChild(element);

        console.log('ЭЛЕМЕНТ ДОБАВЛЕН В DOM:', element);
        console.log('РОДИТЕЛЬ ЭЛЕМЕНТА:', element.parentElement);
        console.log('ПОЗИЦИЯ:', {
            x: element.dataset.x,
            y: element.dataset.y,
            width: element.dataset.width,
            height: element.dataset.height
        });

        selectEditorElement(element);
        recordHistory();
        return element;
    }



    function createPlacedCardElement(page, card, x, y) {

        const width = Number(card.width) || 240;
        const height = Number(card.height) || 190;

        const element = document.createElement('div');

        element.className = 'placed_element placed_card';

        element.dataset.id = '';
        element.dataset.assetId = '';
        element.dataset.type = 'card';
        element.dataset.template = card.template;
        element.dataset.recipeId =
            card.type === 'favorite_recipe' ? (card.id || '') : '';

        element.dataset.recipeUrl =
            card.type === 'favorite_recipe' ? (card.url || '') : '';

        element.dataset.recipeCalories =
            card.type === 'favorite_recipe' ? (card.calories || '') : '';
        element.dataset.x = x;
        element.dataset.y = y;
        element.dataset.width = width;
        element.dataset.height = height;
        element.dataset.rotation = 0;

        element.style.left = `${x}px`;
        element.style.top = `${y}px`;
        element.style.width = `${width}px`;
        element.style.height = `${height}px`;
        element.style.zIndex = '1';

        const content = document.createElement('div');
        content.className = `recipe_card recipe_card--${card.template}`;

        const titles = {
            recipe: 'Название блюда',
            photo: 'Вкусные моменты',
            ingredients: 'Ингредиенты',
            steps: 'Шаги приготовления',
            tip: 'Совет повара',
            rating: 'Моя оценка'
        };

        // Название карточки.
        const title = document.createElement('div');
        title.className = 'recipe_card_title';
        title.contentEditable = 'true';
        title.spellcheck = false;
        title.textContent = card.title || titles[card.template] || 'Моя карточка';

        // Основной текст карточки.
        const subtitle = document.createElement('div');
        subtitle.className = 'recipe_card_subtitle';
        subtitle.contentEditable = 'true';
        subtitle.spellcheck = false;
        subtitle.textContent = card.description || 'Добавьте свой текст';

        content.appendChild(title);
        content.appendChild(subtitle);

        // Дополнительные поля только для карточки рецепта.
        if (card.template === 'recipe') {

            const meta = document.createElement('div');
            meta.className = 'recipe_card_editor_meta';

            const time = document.createElement('div');
            time.className = 'recipe_card_time';
            time.contentEditable = 'true';
            time.spellcheck = false;
            time.textContent = card.time ? `${card.time} мин` : '';

            const servings = document.createElement('div');
            servings.className = 'recipe_card_servings';
            servings.contentEditable = 'true';
            servings.spellcheck = false;
            servings.textContent = card.servings ? `${card.servings} порц.` : '';

            meta.appendChild(time);
            meta.appendChild(servings);

            content.appendChild(meta);
        }

        element.appendChild(content);

        // Маркеры изменения размера и поворота.
        ['nw', 'ne', 'sw', 'se', 'rotate'].forEach(type => {

            const handle = document.createElement('div');

            handle.className = `element_handle element_handle--${type}`;
            handle.dataset.action = type;

            element.appendChild(handle);

        });

        page.appendChild(element);

        selectEditorElement(element);
        recordHistory();

        return element;
    }


    // 13. ПЕРЕТАСКИВАНИЕ ИЗ БИБЛИОТЕКИ
    function setupDraggableItem(item) {

        if (item.dataset.dragInitialized === 'true') {
            return;
        }

        item.dataset.dragInitialized = 'true';
        item.draggable = true;

        item.addEventListener('dragstart', event => {

            // Если перетаскиваем избранный рецепт
            if (item.classList.contains('favorite_recipe_item')) {

                draggedAsset = {
                    type: 'favorite_recipe',
                    id: item.dataset.recipeId,
                    template: 'recipe',
                    title: item.dataset.title || '',
                    description: item.dataset.description || '',
                    url: item.dataset.url || '',
                    time: item.dataset.time || '',
                    servings: item.dataset.servings || '',
                    calories: item.dataset.calories || '',
                    width: 240,
                    height: 190
                };

            } else {

                // Обычные фотографии, материалы и шаблоны
                draggedAsset = {
                    id: item.dataset.id,
                    type: item.dataset.type,
                    template: item.dataset.template || '',
                    url: item.dataset.url || '',
                    width: item.dataset.width || 200,
                    height: item.dataset.height || 200
                };

            }

            setCanvasMode('hand');

            event.dataTransfer.effectAllowed = 'copy';
            event.dataTransfer.setData('text/plain', draggedAsset.id || '');

        });

        item.addEventListener('dragend', () => {
            draggedAsset = null;
            setCanvasMode('select');
        });

    }


    // Подключаем материалы и фотографии, которые уже есть в HTML.
    document.querySelectorAll(
        '.sticker_item[draggable="true"], ' +
        '.photo_item[draggable="true"], ' +
        '.card_template[draggable="true"], ' +
        '.favorite_recipe_item'
    ).forEach(setupDraggableItem);

    // 14. ПЕРЕНОС МАТЕРИАЛА НА СТРАНИЦУ
    editorPages.forEach(page => {

        page.addEventListener('dragover', event => {

            event.preventDefault();

            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'copy';
            }

        });

        page.addEventListener('drop', event => {
            console.log('ОТПУСКАНИЕ НА СТРАНИЦЕ:', page.dataset.pageNumber);
            console.log('ВЫБРАННЫЙ МАТЕРИАЛ:', draggedAsset);
            event.preventDefault();

            if (!draggedAsset) {
                return;
            }

            const position = getLocalPosition(event, page);

            const width = Number(draggedAsset.width) || 100;
            const height = Number(draggedAsset.height) || 100;

            const x = position.x - width / 2;
            const y = position.y - height / 2;

            if (draggedAsset.type === 'favorite_recipe') {

                createPlacedCardElement(
                    page,
                    {
                        ...draggedAsset,
                        template: 'recipe'
                    },
                    x,
                    y
                );

            } else if (draggedAsset.type === 'card') {

                createPlacedCardElement(page, draggedAsset, x, y);

            } else {

                createPlacedElement(page, draggedAsset, x, y);

            }
            console.log(
                'КОЛИЧЕСТВО ЭЛЕМЕНТОВ НА СТРАНИЦЕ:',
                page.querySelectorAll('.placed_element').length
            );
            draggedAsset = null;

        });

    });

    // Сохраняем изменения текста карточки после завершения редактирования.
    editorPages.forEach(page => {

        page.addEventListener('focusout', event => {

            const editable = event.target.closest('[contenteditable="true"]');

            if (!editable) {
                return;
            }

            const card = editable.closest('.placed_card');

            if (!card) {
                return;
            }

            recordHistory();

        });

    });

    // 15. НАЧАЛО ПЕРЕМЕЩЕНИЯ И РЕДАКТИРОВАНИЯ
    editorPages.forEach(page => {

        page.addEventListener('pointerdown', event => {

            const element = event.target.closest('.placed_element');
            if (event.target.closest('[contenteditable="true"]')) {
                return;
            }
            if (!element || !page.contains(element)) {
                selectEditorElement(null);
                return;
            }

            selectEditorElement(element);

            const handle = event.target.closest('.element_handle');

            const action = handle
                ? handle.dataset.action
                : 'move';

            const position = getLocalPosition(event, page);

            let startAngle = 0;

            if (action === 'rotate') {

                const centerX =
                    Number(element.dataset.x) +
                    Number(element.dataset.width) / 2;

                const centerY =
                    Number(element.dataset.y) +
                    Number(element.dataset.height) / 2;

                startAngle = Math.atan2(
                    position.y - centerY,
                    position.x - centerX
                ) * 180 / Math.PI;
            }

            pointerAction = {

                type: action,

                element: element,
                page: page,

                pointerId: event.pointerId,

                startX: position.x,
                startY: position.y,

                originalX: Number(element.dataset.x),
                originalY: Number(element.dataset.y),

                originalWidth: Number(element.dataset.width),
                originalHeight: Number(element.dataset.height),

                originalRotation: Number(element.dataset.rotation) || 0,

                startAngle: startAngle,

                // Состояние до начала изменения.
                beforeState: captureEditorState()

            };

            element.setPointerCapture(event.pointerId);

            event.preventDefault();

        });

    });

    // 16. ПЕРЕМЕЩЕНИЕ ЭЛЕМЕНТА
    function moveElement(action, dx, dy) {

        const element = action.element;

        const x = action.originalX + dx;
        const y = action.originalY + dy;

        element.style.left = `${x}px`;
        element.style.top = `${y}px`;

        element.dataset.x = x;
        element.dataset.y = y;
    }

    // 17. ИЗМЕНЕНИЕ РАЗМЕРА
    function resizeElement(action, dx, dy) {

        const element = action.element;

        let width = action.originalWidth;
        let height = action.originalHeight;

        if (action.type.includes('e')) {
            width += dx;
        }

        if (action.type.includes('w')) {
            width -= dx;
        }

        if (action.type.includes('s')) {
            height += dy;
        }

        if (action.type.includes('n')) {
            height -= dy;
        }

        width = Math.max(20, width);
        height = Math.max(20, height);

        element.style.width = `${width}px`;
        element.style.height = `${height}px`;

        element.dataset.width = width;
        element.dataset.height = height;

        if (action.type.includes('w')) {

            const x =
                action.originalX +
                action.originalWidth -
                width;

            element.style.left = `${x}px`;
            element.dataset.x = x;
        }

        if (action.type.includes('n')) {

            const y =
                action.originalY +
                action.originalHeight -
                height;

            element.style.top = `${y}px`;
            element.dataset.y = y;
        }
    }

    // 18. ПОВОРОТ ЭЛЕМЕНТА
    function rotateElement(action, position) {

        const element = action.element;

        const centerX =
            Number(element.dataset.x) +
            Number(element.dataset.width) / 2;

        const centerY =
            Number(element.dataset.y) +
            Number(element.dataset.height) / 2;

        const currentAngle = Math.atan2(
            position.y - centerY,
            position.x - centerX
        ) * 180 / Math.PI;

        const rotation =
            action.originalRotation +
            currentAngle -
            action.startAngle;

        element.style.transform = `rotate(${rotation}deg)`;
        element.dataset.rotation = rotation;
    }

    // 19. ОБРАБОТКА ДВИЖЕНИЯ УКАЗАТЕЛЯ
    document.addEventListener('pointermove', event => {

        if (!pointerAction) {
            return;
        }

        if (event.pointerId !== pointerAction.pointerId) {
            return;
        }

        const action = pointerAction;

        const position = getLocalPosition(event, action.page);

        const dx = position.x - action.startX;
        const dy = position.y - action.startY;

        if (action.type === 'move') {

            moveElement(action, dx, dy);

        } else if (['nw', 'ne', 'sw', 'se'].includes(action.type)) {

            resizeElement(action, dx, dy);

        } else if (action.type === 'rotate') {

            rotateElement(action, position);

        }

    });

    // 20. ЗАВЕРШЕНИЕ ПЕРЕМЕЩЕНИЯ
    function stopPointerAction(event) {

        if (!pointerAction) {
            return;
        }

        if (
            event.pointerId !== undefined &&
            event.pointerId !== pointerAction.pointerId
        ) {
            return;
        }

        const action = pointerAction;

        pointerAction = null;

        // Записываем изменение только после завершения действия.
        if (
            ['move', 'nw', 'ne', 'sw', 'se', 'rotate'].includes(action.type)
        ) {

            recordHistory();

        }

    }

    document.addEventListener('pointerup', stopPointerAction);
    document.addEventListener('pointercancel', stopPointerAction);

    // 21. УДАЛЕНИЕ ВЫДЕЛЕННОГО ЭЛЕМЕНТА
    document.addEventListener('keydown', event => {

        if (!selectedElement) return;

        if (event.key !== 'Delete' && event.key !== 'Backspace') {
            return;
        }

        const target = event.target;

        // Не удаляем карточку, если пользователь редактирует её текст.
        if (
            target.isContentEditable ||
            target.closest('[contenteditable="true"]') ||
            target.matches('input, textarea, select')
        ) {
            return;
        }

        event.preventDefault();

        const elementToRemove = selectedElement;

        selectEditorElement(null);

        elementToRemove.remove();

        pointerAction = null;

        recordHistory();

    });
    
    // ПОИСК И ФИЛЬТРАЦИЯ МАТЕРИАЛОВ
    const materialsSearch = document.querySelector('.materials_search input');
    const materialCategories = document.querySelectorAll('.material_cat');
    const materialsGrid = document.querySelector('.stickers_grid');

    let activeMaterialType = 'stickers';

    const materialTypeMap = {
        stickers: 'sticker',
        tape: 'tape',
        stamps: 'stamp',
        frames: 'frame'
    };

    function filterMaterials() {

        if (!materialsGrid) return;

        const searchText = materialsSearch
            ? materialsSearch.value.trim().toLowerCase()
            : '';

        const materials = materialsGrid.querySelectorAll('.sticker_item');

        materials.forEach(material => {

            const type = material.dataset.type || '';
            const name = material.dataset.name || '';
            const category = material.dataset.category || '';

            const selectedType = materialTypeMap[activeMaterialType];

            // Проверяем выбранную категорию
            const matchesType = type === selectedType;

            // Проверяем поисковый запрос
            const searchContent = (
                name + ' ' + category + ' ' + type
            ).toLowerCase();

            const matchesSearch = searchContent.includes(searchText);

            // Показываем только подходящие материалы
            material.style.display =
                matchesType && matchesSearch ? '' : 'none';
        });
    }

    // Переключение категорий
    materialCategories.forEach(button => {

        button.addEventListener('click', () => {

            materialCategories.forEach(item => {
                item.classList.remove('active');
            });

            button.classList.add('active');

            activeMaterialType = button.dataset.cat;

            // Меняем заголовок категории
            const title = document.querySelector('.stickers_title');

            if (title) {
                const categoryNames = {
                    stickers: 'Стикеры',
                    tape: 'Скотч',
                    stamps: 'Штампы',
                    frames: 'Рамки'
                };

                title.textContent = categoryNames[activeMaterialType] || 'Материалы';
            }

            filterMaterials();
        });
    });

    // Поиск при вводе текста
    if (materialsSearch) {
        materialsSearch.addEventListener('input', filterMaterials);
    }

    // Первоначальная фильтрация
    filterMaterials();

})();
