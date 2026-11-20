const newsletterForm = document.getElementById('newsletterForm');

if (newsletterForm) {
    newsletterForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = document.getElementById('newsletterSubmitBtn');
        const errorEl = document.getElementById('newsletter-error');
        const successEl = document.getElementById('newsletter-success');
        const originalText = submitBtn.innerText;

        errorEl.style.display = 'none';
        successEl.style.display = 'none';

        submitBtn.disabled = true;
        submitBtn.innerText = 'Отправка...';

        const formData = new FormData(newsletterForm);

        try {
            const response = await fetch('php/newsletter_subscribe.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                successEl.innerText = result.message;
                successEl.style.display = 'block';
                newsletterForm.reset();
                setTimeout(() => { successEl.style.display = 'none'; }, 5000);
            } else {
                if (result.errors && result.errors.email) {
                    errorEl.innerText = result.errors.email;
                } else if (result.errors && result.errors.system) {
                    errorEl.innerText = result.errors.system;
                } else {
                    errorEl.innerText = result.message || 'Произошла ошибка';
                }
                errorEl.style.display = 'block';
            }
        } catch (error) {
            console.error('Ошибка сети:', error);
            errorEl.innerText = 'Ошибка соединения с сервером';
            errorEl.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    });
}

const commentForm = document.getElementById('commentForm');
if (commentForm) {
    commentForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;

        submitBtn.disabled = true;
        submitBtn.innerText = 'Отправка...';

        try {
            const response = await fetch('php/recipe_comment.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                const commentsList = document.getElementById('commentsList');
                const noComments = commentsList.querySelector('.no_comments');
                if (noComments) noComments.remove();

                const comment = result.comment;
                const avatarLetter = (comment.username || '?').charAt(0).toUpperCase();
                const avatarHtml = comment.avatar_url
                    ? `<img src="${comment.avatar_url}" alt="">`
                    : avatarLetter;

                const newComment = document.createElement('div');
                newComment.className = 'comment_item';
                newComment.dataset.commentId = comment.id;
                newComment.innerHTML = `
                    <div class="comment_avatar">${avatarHtml}</div>
                    <div class="comment_body">
                        <div class="comment_header">
                            <span class="comment_author">${comment.username || 'Аноним'}</span>
                            <span class="comment_date">${comment.created_at}</span>
                        </div>
                        <p class="comment_text">${comment.text.replace(/\n/g, '<br>')}</p>
                    </div>
                `;
                commentsList.insertBefore(newComment, commentsList.firstChild);

                this.reset();
            } else {
                alert(result.message || 'Ошибка при отправке');
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Ошибка соединения с сервером');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    });
}



const profileForm = document.getElementById('profileForm');
if (profileForm) {
    profileForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;
        const errorUsername = document.getElementById('profile-error-username');
        const errorEmail = document.getElementById('profile-error-email');
        const successMsg = document.getElementById('profile-success');

        errorUsername.style.display = 'none';
        errorEmail.style.display = 'none';
        successMsg.style.display = 'none';

        submitBtn.disabled = true;
        submitBtn.innerText = 'Сохранение...';

        const formData = new FormData(this);

        try {
            const response = await fetch('php/profile_update.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                successMsg.innerText = result.message;
                successMsg.style.display = 'block';

                const usernameEl = document.querySelector('.profile_username');
                if (usernameEl) usernameEl.innerText = formData.get('username');

                const emailEl = document.querySelector('.profile_email');
                if (emailEl) emailEl.innerText = formData.get('email');

                const avatarLetter = document.querySelector('.avatar_letter');
                if (avatarLetter) {
                    avatarLetter.innerText = formData.get('username').charAt(0).toUpperCase();
                }

                const userNameMini = document.querySelector('.user_name_mini');
                if (userNameMini) userNameMini.innerText = formData.get('username');
            } else {
                if (result.errors.username) {
                    errorUsername.innerText = result.errors.username;
                    errorUsername.style.display = 'block';
                }
                if (result.errors.email) {
                    errorEmail.innerText = result.errors.email;
                    errorEmail.style.display = 'block';
                }
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Ошибка соединения с сервером');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    });
}

const changePasswordBtn = document.getElementById('changePasswordBtn');
if (changePasswordBtn) {
    changePasswordBtn.addEventListener('click', async function () {
        const form = this.closest('.form_section');
        const currentPassword = form.querySelector('[name="current_password"]');
        const newPassword = form.querySelector('[name="new_password"]');
        const confirmPassword = form.querySelector('[name="confirm_password"]');
        const errorMsg = document.getElementById('password-error');
        const successMsg = document.getElementById('password-success');

        errorMsg.style.display = 'none';
        successMsg.style.display = 'none';

        const formData = new FormData();
        formData.append('current_password', currentPassword.value);
        formData.append('new_password', newPassword.value);
        formData.append('confirm_password', confirmPassword.value);

        this.disabled = true;
        this.innerText = 'Сохранение...';

        try {
            const response = await fetch('php/password_change.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                successMsg.innerText = result.message;
                successMsg.style.display = 'block';
                currentPassword.value = '';
                newPassword.value = '';
                confirmPassword.value = '';
            } else {
                errorMsg.innerText = result.message;
                errorMsg.style.display = 'block';
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Ошибка соединения с сервером');
        } finally {
            this.disabled = false;
            this.innerText = 'Сменить пароль';
        }
    });
}

const deleteAccountBtn = document.getElementById('deleteAccountBtn');
const deleteOverlay = document.getElementById('deleteOverlay');
const deleteModal = document.getElementById('deleteModal');

if (deleteAccountBtn && deleteModal) {
    deleteAccountBtn.addEventListener('click', () => {
        deleteOverlay?.classList.add('active');
        deleteModal.classList.add('active');
        document.body.classList.add('modal-open');
    });

    const deleteCancel = deleteModal.querySelector('.btn_cancel');
    const deleteConfirm = deleteModal.querySelector('.btn_confirm');
    const deleteClose = deleteModal.querySelector('.modal_close');

    const closeDeleteModal = () => {
        deleteOverlay?.classList.remove('active');
        deleteModal.classList.remove('active');
        document.body.classList.remove('modal-open');
    };

    deleteCancel?.addEventListener('click', closeDeleteModal);
    deleteClose?.addEventListener('click', closeDeleteModal);
    deleteOverlay?.addEventListener('click', closeDeleteModal);

    deleteConfirm?.addEventListener('click', async () => {
        alert('Функция удаления аккаунта будет доступна позже');
        closeDeleteModal();
    });
}

const deleteRecipeOverlay = document.getElementById('deleteRecipeOverlay');
const deleteRecipeModal = document.getElementById('deleteRecipeModal');
const deleteRecipeTitle = document.getElementById('deleteRecipeTitle');
const confirmDeleteRecipe = document.getElementById('confirmDeleteRecipe');
let recipeToDelete = null;

document.querySelectorAll('.btn_delete_recipe').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        recipeToDelete = this.dataset.recipeId;
        const card = this.closest('.recipe_card');
        const title = card.querySelector('.recipe_card_title')?.innerText || '';

        if (deleteRecipeTitle) deleteRecipeTitle.innerText = title;
        deleteRecipeOverlay?.classList.add('active');
        deleteRecipeModal?.classList.add('active');
        document.body.classList.add('modal-open');
    });
});

const closeDeleteRecipeModal = () => {
    deleteRecipeOverlay?.classList.remove('active');
    deleteRecipeModal?.classList.remove('active');
    document.body.classList.remove('modal-open');
    recipeToDelete = null;
};

deleteRecipeModal?.querySelector('.btn_cancel')?.addEventListener('click', closeDeleteRecipeModal);
deleteRecipeModal?.querySelector('.modal_close')?.addEventListener('click', closeDeleteRecipeModal);
deleteRecipeOverlay?.addEventListener('click', closeDeleteRecipeModal);

if (confirmDeleteRecipe) {
    confirmDeleteRecipe.addEventListener('click', async function () {
        if (!recipeToDelete) return;

        const originalText = this.innerText;
        this.disabled = true;
        this.innerText = 'Удаление...';

        const formData = new FormData();
        formData.append('recipe_id', recipeToDelete);

        try {
            const response = await fetch('php/recipe_delete.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                alert(result.message || 'Ошибка при удалении');
                this.disabled = false;
                this.innerText = originalText;
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Ошибка соединения с сервером');
            this.disabled = false;
            this.innerText = originalText;
        }
    });
}

document.querySelectorAll('#subscriptionsGrid .btn_unsubscribe, #subscriptionsGrid .btn_follow_back').forEach(btn => {
    btn.addEventListener('click', async function () {
        const userId = this.dataset.userId;
        const action = this.classList.contains('btn_follow_back') ? 'follow' : 'unfollow';

        const originalText = this.innerText;
        this.disabled = true;
        this.innerText = action === 'unfollow' ? 'Отписка...' : 'Подписка...';

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('action', action);

        try {
            const response = await fetch('php/subscription_toggle.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                window.location.reload();

            } else {
                alert(result.message || 'Произошла ошибка');
                this.disabled = false;
                this.innerText = originalText;
            }
        } catch (error) {
            console.error('Ошибка сети:', error);
            alert('Ошибка соединения с сервером');
            this.disabled = false;
            this.innerText = originalText;
        }
    });
});

const profileSubscribeBtn = document.querySelector('.profile_info .btn_subscribe, .profile_info .btn_unsubscribe');

if (profileSubscribeBtn) {
    profileSubscribeBtn.addEventListener('click', async function () {
        const userId = this.dataset.userId;
        const isCurrentlyFollowing = this.classList.contains('btn_unsubscribe');
        const action = isCurrentlyFollowing ? 'unfollow' : 'follow';

        this.disabled = true;
        this.innerText = action === 'unfollow' ? 'Отписка...' : 'Подписка...';

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('action', action);

        try {
            const response = await fetch('php/subscription_toggle.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                window.location.reload();
            } else {
                alert(result.message || 'Произошла ошибка');
            }
        } catch (error) {
            console.error('Ошибка сети:', error);
            alert('Ошибка соединения с сервером');
        } finally {
            this.disabled = false;
        }
    });
}
function updateFollowersCount(count) {
    const statButtons = document.querySelectorAll('.stat_button');
    statButtons.forEach(btn => {
        const label = btn.querySelector('.stat_label');
        if (label && label.innerText.includes('подписчик')) {
            const countEl = btn.querySelector('.stat_count');
            if (countEl) countEl.innerText = count;
        }
    });
}

const avatarEditBtn = document.getElementById('avatarEditBtn');
const avatarInput = document.getElementById('avatarInput');
const avatarMessage = document.getElementById('avatarMessage');

if (avatarEditBtn && avatarInput) {
    avatarEditBtn.addEventListener('click', () => {
        avatarInput.click();
    });

    avatarInput.addEventListener('change', async function () {
        if (this.files.length === 0) return;

        const file = this.files[0];
        const formData = new FormData();
        formData.append('avatar', file);

        avatarEditBtn.disabled = true;
        avatarEditBtn.innerHTML = '<span style="font-size:12px;">Загрузка...</span>';
        if (avatarMessage) avatarMessage.style.display = 'none';

        try {
            const response = await fetch('php/avatar_upload.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                setTimeout(() => {
                    window.location.reload();
                }, 500);

            } else {
                if (avatarMessage) {
                    avatarMessage.textContent = result.message;
                    avatarMessage.style.color = 'red';
                    avatarMessage.style.display = 'block';
                }
            }
        } catch (error) {
            console.error('Ошибка:', error);
            if (avatarMessage) {
                avatarMessage.textContent = 'Ошибка соединения с сервером';
                avatarMessage.style.color = 'red';
                avatarMessage.style.display = 'block';
            }
        } finally {
            avatarEditBtn.disabled = false;
            avatarEditBtn.innerHTML = '<img src="asset/img/icon_edit_user.svg" alt="">';
            avatarInput.value = '';
        }
    });
}

document.addEventListener('click', async function (e) {
    const likeBtn = e.target.closest('.btn_heart');
    if (!likeBtn) return;

    e.preventDefault();
    e.stopPropagation();

    if (typeof IS_LOGGED_IN !== 'undefined' && !IS_LOGGED_IN) {
        if (typeof openModal === 'function' && typeof loginModal !== 'undefined') {
            openModal(loginModal);
        } else {
            alert('Войдите, чтобы добавлять рецепты в избранное');
        }
        return;
    }

    const recipeId = likeBtn.dataset.recipeId;
    const img = likeBtn.querySelector('img');
    const formData = new FormData();
    formData.append('recipe_id', recipeId);

    likeBtn.style.opacity = '0.5';
    likeBtn.style.pointerEvents = 'none';

    try {
        const response = await fetch('php/recipe_like.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            if (result.action === 'liked') {
                likeBtn.classList.add('active');
                img.src = 'asset/img/icon_heart-active.svg';
            } else {
                likeBtn.classList.remove('active');
                img.src = 'asset/img/icon_heart.svg';
            }

            const card = likeBtn.closest('.recipe_card');
            if (card) {
                const likesSpan = card.querySelector('.details_likes span');
                if (likesSpan) {
                    likesSpan.textContent = `Понравилось: ${result.likes_count}`;
                }
            }
            setTimeout(() => {
                window.location.reload();
            }, 100);
        } else {
            alert(result.message || 'Произошла ошибка');
        }
    } catch (error) {
        console.error('Ошибка сети:', error);
        alert('Ошибка соединения с сервером');
    } finally {
        likeBtn.style.opacity = '1';
        likeBtn.style.pointerEvents = 'auto';
    }
});

const likeBtn = document.getElementById('likeBtn');
const likesCount = document.getElementById('likesCount');

if (likeBtn && likesCount) {
    likeBtn.addEventListener('click', async function () {
        if (typeof IS_LOGGED_IN !== 'undefined' && !IS_LOGGED_IN) {
            if (typeof openModal === 'function' && typeof loginModal !== 'undefined') {
                openModal(loginModal);
            } else {
                alert('Войдите, чтобы добавлять рецепты в избранное');
            }
            return;
        }

        const recipeId = this.dataset.recipeId;
        const img = this.querySelector('img');
        const formData = new FormData();
        formData.append('recipe_id', recipeId);

        this.style.opacity = '0.5';
        this.style.pointerEvents = 'none';

        try {
            const response = await fetch('php/recipe_like.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                if (result.action === 'liked') {
                    this.classList.add('active');
                    img.src = 'asset/img/icon_heart-active.svg';
                } else {
                    this.classList.remove('active');
                    img.src = 'asset/img/icon_heart.svg';
                }

                likesCount.textContent = result.likes_count;
                setTimeout(() => {
                    window.location.reload();
                }, 100);
            } else {
                alert(result.message || 'Произошла ошибка');
            }
        } catch (error) {
            console.error('Ошибка сети:', error);
            alert('Ошибка соединения с сервером');
        } finally {
            this.style.opacity = '1';
            this.style.pointerEvents = 'auto';
        }
    });
}