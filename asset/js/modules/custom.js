initIngredientsAccordion();
initRecipeCards();

const newsletterForm = document.querySelector('.newsletter_form');
if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const email = newsletterForm.querySelector('.newsletter_input').value.trim();

        console.log('Подписка на email:', email);
        newsletterForm.reset();
    });
}

function initIngredientsAccordion() {
    const toggle = document.getElementById('ingredientsToggle');
    const list = document.getElementById('ingredientsList');

    if (!toggle || !list) return;

    toggle.addEventListener('click', () => {
        toggle.classList.toggle('collapsed');
        list.classList.toggle('collapsed');
    });
}

function initRecipeCards() {
    const cards = document.querySelectorAll('.recipe_card');
    if (!cards.length) return;

    function closeAllCards(exceptCard = null) {
        cards.forEach(card => {
            if (card !== exceptCard) {
                card.classList.remove('expanded');
            }
        });
    }

    document.querySelectorAll('[data-toggle="details"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const card = btn.closest('.recipe_card');
            if (!card) return;

            const isExpanded = card.classList.contains('expanded');

            if (isExpanded) {
                card.classList.remove('expanded');
            } else {
                closeAllCards(card);
                card.classList.add('expanded');
            }
        });
    });

    cards.forEach(card => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('button') || e.target.closest('a')) {
                return;
            }
            
            const url = card.dataset.url;
            if (url) {
                window.location.href = url;
            }
        });
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.recipe_card')) {
            closeAllCards();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllCards();
        }
    });
}

document.addEventListener('DOMContentLoaded', initRecipeCards);