<?php
    global $connect;
    $dinner_recipes = getRandomRecipesByCategory('breakfast', 8);

    $liked_recipe_ids = [];
    if (isset($_SESSION['user_id'])) {
        $stmt_likes = $connect->prepare("SELECT recipe_id FROM user_favorites WHERE user_id = ?");
        $stmt_likes->execute([$_SESSION['user_id']]);
        $liked_recipe_ids = $stmt_likes->fetchAll(PDO::FETCH_COLUMN);
    }
?>
<div class="container">
    <section class="audience_section">
        <h2 class="audience_title">Для кого это</h2>

        <div class="audience_grid">
            <div class="audience_card audience_card--text-top" style="background-color: #a8c4b0;">
                <div class="audience_card_text">
                    <span class="audience_card_number">01/</span>
                    <h3 class="audience_card_title">Для любителей уюта</h3>
                    <p class="audience_card_desc">Собирайте рецепты и оформляйте их как маленькие
                        истории</p>
                </div>
                <div class="audience_card_image">
                    <img src="asset/img/audience_1.png" alt="Блюдо на зелёном фоне">
                </div>
            </div>

            <div class="audience_card audience_card--text-top" style="background-color: #b8a9d4;">
                <div class="audience_card_text">
                    <span class="audience_card_number">02/</span>
                    <h3 class="audience_card_title">Для практичных</h3>
                    <p class="audience_card_desc">Ведите список покупок, меню на неделю и заметки о
                        том, что
                        понравилось</p>
                </div>
                <div class="audience_card_image">
                    <img src="asset/img/audience_2.png" alt="Корзина с продуктами">
                </div>
            </div>

            <div class="audience_card audience_card audience_card--hide-mobile">
                <div class="audience_card_image">
                    <img src="asset/img/audience_3.png" alt="Пара готовит вместе">
                </div>
            </div>

            <div class="audience_card audience_card--wide audience_card--hide-mobile">
                <div class="audience_card_image">
                    <img src="asset/img/audience_4.png" alt="Женщина у плиты">
                </div>
            </div>

            <div class="audience_card audience_card--text-bottom" style="background-color: #a8c4b0;">
                <div class="audience_card_image">
                    <img src="asset/img/audience_5.png" alt="Творческое оформление">
                </div>
                <div class="audience_card_text">
                    <span class="audience_card_number">03/</span>
                    <h3 class="audience_card_title">Для творческих</h3>
                    <p class="audience_card_desc">Экспериментируйте с оформлением, создавайте свои
                        шаблоны и
                        делитесь ими</p>
                </div>
            </div>
        </div>
    </section>
    <?php renderRecipeSection('dinner', 'Идеи для ужина', 'Выбор редакции', $dinner_recipes, $liked_recipe_ids); ?>

    <section class="newsletter_section" id="newsletter">
        <div class="newsletter_content">
            <div class="newsletter_icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <polyline points="22,6 12,13 2,6" />
                </svg>
            </div>
            <div class="newsletter_text">
                <h3 class="newsletter_title">Новые рецепты и советы — в вашей почте!</h3>
                <p class="newsletter_description">
                    Нажимая "Подписаться", вы соглашаетесь с политикой конфиденциальности и правилами сайта.
                </p>
            </div>
            <form class="newsletter_form" id="newsletterForm" style="display: flex; flex-direction:column; gap:6px;">
                <div style="display:flex;gap:6px;">
                    <input type="email" name="email" class="newsletter_input" placeholder="Ваша почта">
                    <button type="submit" class="newsletter_btn" id="newsletterSubmitBtn">Подписаться</button>
                </div>

                <p class="newsletter-error" id="newsletter-error"
                    style="display:none;  font-size:0.9em; margin-top:8px;"></p>
                <p class="newsletter-success" id="newsletter-success"
                    style="display:none;font-size:0.9em; margin-top:8px;"></p>
            </form>
        </div>
    </section>
</div>