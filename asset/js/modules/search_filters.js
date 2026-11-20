document.addEventListener('DOMContentLoaded', () => {
    const panelOverlay = document.getElementById('panelOverlay');
    const openPanelBtns = document.querySelectorAll('[data-panel]');
    const closePanelBtns = document.querySelectorAll('.panel-close');
    const panels = document.querySelectorAll('.side-panel');

    function openPanel(panelId) {
        const panel = document.getElementById(`panel-${panelId}`);
        if (panel) {
            panel.classList.add('active');
            panelOverlay.classList.add('active');
            document.body.classList.add('panel-open');
        }
    }

    function closeAllPanels() {
        panels.forEach(panel => panel.classList.remove('active'));
        panelOverlay.classList.remove('active');
        document.body.classList.remove('panel-open');
    }

    openPanelBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-panel');
            openPanel(target);
        });
    });

    closePanelBtns.forEach(btn => {
        btn.addEventListener('click', closeAllPanels);
    });

    panelOverlay.addEventListener('click', closeAllPanels);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAllPanels();
    });

    const resetFiltersBtn = document.getElementById('resetFilters');
    const applyFiltersBtn = document.getElementById('applyFilters');

    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', () => {
            document.querySelectorAll('#panel-filters input[type="checkbox"]').forEach(cb => {
                cb.checked = false;
            });
            document.querySelector('#panel-filters .filter-select').value = 'relevance';
        });
    }

    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', () => {
            const filters = {
                time: Array.from(document.querySelectorAll('input[name="time"]:checked')).map(cb => cb.value),
                calories: Array.from(document.querySelectorAll('input[name="calories"]:checked')).map(cb => cb.value),
                sort: document.querySelector('#panel-filters .filter-select').value
            };

            closeAllPanels();
        });
    }

    const clearHistoryBtn = document.querySelector('.clear_history');
    const historyTagsContainer = document.querySelector('.history_tags');

    if (clearHistoryBtn && historyTagsContainer) {
        clearHistoryBtn.addEventListener('click', (e) => {
            e.preventDefault();
            historyTagsContainer.style.opacity = '0';
            historyTagsContainer.style.transition = 'opacity 0.3s';

            setTimeout(() => {
                historyTagsContainer.innerHTML = '<p style="color: #999; font-size: 14px; margin: 0;">История поиска пуста</p>';
                historyTagsContainer.style.opacity = '1';
                clearHistoryBtn.style.display = 'none';
            }, 300);
        });
    }
});