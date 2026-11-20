function setupLiveSearch(config) {
    const form = document.getElementById(config.formId);
    const input = document.getElementById(config.inputId);
    const dropdown = document.getElementById(config.dropdownId);
    const list = document.getElementById(config.listId);
    const footer = document.getElementById(config.footerId);
    const seeAllLink = document.getElementById(config.seeAllId);

    if (!input || !form) return;

    let debounceTimer;

    async function performSearch(query) {
        if (query.length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        try {
            const response = await fetch(`php/hero_search.php?q=${encodeURIComponent(query)}`);
            const contentType = response.headers.get("content-type");

            if (!contentType || !contentType.includes("application/json")) {
                console.error("Сервер вернул не JSON");
                return;
            }

            const data = await response.json();
            if (data.error) {
                console.error("Ошибка PHP:", data.message);
                return;
            }

            list.innerHTML = '';

            if (data.results.length > 0) {
                data.results.forEach(item => {
                    const link = document.createElement('a');
                    link.className = 'search_result_item';
                    link.href = item.type === 'category'
                        ? `?page=category&slug=${encodeURIComponent(item.link_slug)}`
                        : `?page=recipe&id=${item.id}`;

                    const img = document.createElement('img');
                    img.src = item.cover_url || 'asset/img/placeholder.png';
                    img.alt = item.title;

                    const infoDiv = document.createElement('div');
                    infoDiv.className = 'search_result_info';

                    const titleSpan = document.createElement('span');
                    titleSpan.className = 'search_result_title';
                    titleSpan.textContent = item.title;

                    const metaSpan = document.createElement('span');
                    metaSpan.className = 'search_result_meta';

                    if (item.type === 'category') {
                        metaSpan.textContent = 'Категория';
                        metaSpan.classList.add('meta-category');
                    } else {
                        metaSpan.textContent = `${item.cooking_time || '?'} мин • ${item.calories || '?'} ккал`;
                    }

                    infoDiv.appendChild(titleSpan);
                    infoDiv.appendChild(metaSpan);
                    link.appendChild(img);
                    link.appendChild(infoDiv);
                    list.appendChild(link);
                });

                if (data.has_more) {
                    seeAllLink.href = `?page=search_page&q=${encodeURIComponent(query)}`;
                    footer.style.display = 'block';
                } else {
                    footer.style.display = 'none';
                }
                dropdown.style.display = 'block';
            } else {
                list.innerHTML = '<div class="search_no_results">Ничего не найдено</div>';
                footer.style.display = 'none';
                dropdown.style.display = 'block';
            }
        } catch (error) {
            console.error('Ошибка поиска:', error);
        }
    }

    input.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            performSearch(e.target.value);
        }, 300);
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const query = input.value.trim();
        if (query) {
            window.location.href = `?page=search_page&q=${encodeURIComponent(query)}`;
        }
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest(`#${config.wrapperId}`)) {
            dropdown.style.display = 'none';
        }
    });
}

//  Инициализация на ГЛАВНОЙ странице (v1)
setupLiveSearch({
    wrapperId: 'heroSearchWrapper',
    formId: 'heroSearchForm',
    inputId: 'heroSearchInput',
    dropdownId: 'searchResultsDropdown',
    listId: 'searchResultsList',
    footerId: 'searchResultsFooter',
    seeAllId: 'searchSeeAllLink'
});

// Инициализация в ШАПКЕ внутренних страниц (v2)
setupLiveSearch({
    wrapperId: 'headerSearchWrapper',
    formId: 'headerSearchForm',
    inputId: 'headerSearchInput',
    dropdownId: 'headerSearchResultsDropdown',
    listId: 'headerSearchResultsList',
    footerId: 'headerSearchResultsFooter',
    seeAllId: 'headerSearchSeeAllLink'
});


const searchForm = document.getElementById('searchForm');
const searchInput = document.querySelector('.search_input input');
const panelOverlay = document.getElementById('panelOverlay');
const filterFormContent = document.getElementById('filterFormContent');

document.querySelectorAll('[data-panel]').forEach(btn => {
    btn.addEventListener('click', () => {
        const panelId = `panel-${btn.dataset.panel}`;
        document.getElementById(panelId)?.classList.add('active');
        panelOverlay?.classList.add('active');
        document.body.classList.add('modal-open');
    });
});

document.querySelectorAll('.panel-close, #panelOverlay').forEach(el => {
    el.addEventListener('click', () => {
        document.querySelectorAll('.side-panel').forEach(p => p.classList.remove('active'));
        panelOverlay?.classList.remove('active');
        document.body.classList.remove('modal-open');
    });
});

document.getElementById('applyFilters')?.addEventListener('click', () => {
    const formData = new FormData(filterFormContent);
    const currentQuery = searchInput?.value.trim() || '';
    
    const params = new URLSearchParams();
    params.append('page', 'search_page');
    if (currentQuery) params.append('q', currentQuery);
    
    formData.forEach((value, key) => {
        params.append(key, value);
    });
    
    document.querySelectorAll('.side-panel').forEach(p => p.classList.remove('active'));
    panelOverlay?.classList.remove('active');
    document.body.classList.remove('modal-open');
    
    window.location.href = 'index.php?' + params.toString();
});

document.getElementById('resetFilters')?.addEventListener('click', () => {
    window.location.href = 'index.php?page=search_page';
});

function loadSearchHistory() {
    const history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
    const historyBlock = document.getElementById('historyBlock');
    const historyTags = document.getElementById('historyTags');
    
    if (history.length > 0 && searchInput) {
        historyBlock.style.display = 'block';
        historyTags.innerHTML = '';
        history.forEach(term => {
            const tag = document.createElement('span');
            tag.className = 'tag';
            tag.textContent = term;
            tag.style.cursor = 'pointer';
            tag.addEventListener('click', () => {
                searchInput.value = term;
                searchForm.submit();
            });
            historyTags.appendChild(tag);
        });
    }
}

function saveSearchQuery(query) {
    if (!query) return;
    let history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
    history = history.filter(item => item !== query);
    history.unshift(query);
    history = history.slice(0, 7);
    localStorage.setItem('searchHistory', JSON.stringify(history));
}

searchForm?.addEventListener('submit', () => {
    saveSearchQuery(searchInput?.value.trim());
});

document.getElementById('clearHistoryBtn')?.addEventListener('click', () => {
    localStorage.removeItem('searchHistory');
    document.getElementById('historyBlock').style.display = 'none';
});

loadSearchHistory();

const openFiltersBtn = document.getElementById('openFiltersBtn');
const closeFiltersBtn = document.getElementById('closeFiltersBtn');
const filterPanel = document.getElementById('filterPanel');
const filterOverlay = document.getElementById('filterOverlay');
const filterForm = document.getElementById('filterForm');
const resetFiltersBtn = document.getElementById('resetFiltersBtn');

openFiltersBtn?.addEventListener('click', () => {
    filterPanel?.classList.add('active');
    filterOverlay?.classList.add('active');
    document.body.classList.add('modal-open');
});

function closeFilterPanel() {
    filterPanel?.classList.remove('active');
    filterOverlay?.classList.remove('active');
    document.body.classList.remove('modal-open');
}

closeFiltersBtn?.addEventListener('click', closeFilterPanel);
filterOverlay?.addEventListener('click', closeFilterPanel);

filterForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const formData = new FormData(filterForm);
    const params = new URLSearchParams();
    
    params.append('page', 'category');
    params.append('slug', formData.get('slug'));
    
    formData.getAll('time[]').forEach(val => params.append('time[]', val));
    
    formData.getAll('calories[]').forEach(val => params.append('calories[]', val));
    
    const sort = formData.get('sort');
    if (sort && sort !== 'newest') {
        params.append('sort', sort);
    }
    
    closeFilterPanel();
    window.location.href = 'index.php?' + params.toString();
});

resetFiltersBtn?.addEventListener('click', () => {
    if (filterForm) filterForm.reset();
    
    const slug = filterForm?.querySelector('input[name="slug"]')?.value || '';
    
    closeFilterPanel();
    window.location.href = `index.php?page=category&slug=${encodeURIComponent(slug)}`;
});