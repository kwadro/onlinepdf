import { Controller } from '@hotwired/stimulus';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

export default class extends Controller {

    static targets = ['input', 'preview', 'spinner'];

    connect() {
        this.cropper = null;
    }

    disconnect() {
        this.destroyCropper();
    }

    destroyCropper() {
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
    }

    closePopup() {
        const overlay = document.querySelector('[data-popup-target="overlay"]');
        if (overlay) {
            overlay.classList.remove('active');
        }
        document.body.style.overflow = '';
    }

    updateAvatarImages(avatarUrl) {
        const avatarSrc = '/uploads/avatars/' + avatarUrl;

        ['user-image', 'user-header-image'].forEach((id) => {
            const element = document.getElementById(id);
            if (element) {
                element.src = avatarSrc;
            }
        });

        const sidebarAvatar = document.querySelector('.site-sidebar__avatar');
        if (sidebarAvatar) {
            sidebarAvatar.src = avatarSrc;
            return;
        }

        const sidebarFallback = document.querySelector('.site-sidebar__avatar-fallback');
        if (!sidebarFallback) {
            return;
        }

        const profileName = sidebarFallback
            .closest('.site-sidebar__profile')
            ?.querySelector('.site-sidebar__profile-name')
            ?.textContent
            ?.trim() || '';

        const sidebarImage = document.createElement('img');
        sidebarImage.className = 'site-sidebar__avatar';
        sidebarImage.src = avatarSrc;
        sidebarImage.alt = profileName;
        sidebarImage.width = 52;
        sidebarImage.height = 52;
        sidebarFallback.replaceWith(sidebarImage);
    }

    change() {
        if (!this.hasInputTarget || !this.hasPreviewTarget) {
            return;
        }

        const file = this.inputTarget.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('Only image files allowed');
            this.inputTarget.value = '';
            return;
        }

        if (file.size > 3 * 1024 * 1024) {
            alert('Max file size is 3MB');
            this.inputTarget.value = '';
            return;
        }

        this.oldSrc = this.previewTarget.src;

        const reader = new FileReader();
        reader.onload = (e) => {
            this.previewTarget.src = e.target.result;
            this.destroyCropper();
            this.cropper = new Cropper(this.previewTarget, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 1,
                responsive: true,
            });
        };
        reader.readAsDataURL(file);
    }

    saveRecipe() {
        if (!this.cropper || !this.hasInputTarget) {
            return;
        }

        const ajaxUrl = this.inputTarget.dataset.ajaxurl;
        const canvas = this.cropper.getCroppedCanvas({
            width: 300,
            height: 300,
        });

        canvas.toBlob((blob) => {
            const recipeId = document.getElementById('recipe_id')?.value;
            if (!recipeId || !ajaxUrl) {
                return;
            }

            const formData = new FormData();
            formData.append('recipe_file', blob, 'recipe.png');
            formData.append('form_code', 'recipe-image');
            formData.append('id', recipeId);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Upload failed');
                    return response.json();
                })
                .then(data => {
                    const recipeImage = document.getElementById('recipe-image');
                    if (recipeImage && data.imageUrl) {
                        recipeImage.src = '/uploads/recipes/' + data.imageUrl;
                    }
                    this.destroyCropper();
                    this.closePopup();
                })
                .catch(error => {
                    console.error('Upload error:', error);
                });
        });
    }

    saveAvatar() {
        if (!this.cropper || !this.hasInputTarget) {
            return;
        }

        const ajaxUrl = this.inputTarget.dataset.ajaxurl;
        const canvas = this.cropper.getCroppedCanvas({
            width: 300,
            height: 300,
        });

        canvas.toBlob((blob) => {
            if (!ajaxUrl) {
                return;
            }

            const formData = new FormData();
            formData.append('image_file', blob, 'avatar.png');
            formData.append('form_code', 'avatar-image');

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Upload failed');
                    return response.json();
                })
                .then(data => {
                    if (data.avatarUrl) {
                        this.updateAvatarImages(data.avatarUrl);
                    }
                    this.destroyCropper();
                    this.closePopup();
                })
                .catch(error => {
                    console.error('Upload error:', error);
                });
        });
    }

    upload(file) {
        if (!this.hasInputTarget || !this.hasPreviewTarget || !this.hasSpinnerTarget) {
            return;
        }

        const formData = new FormData();
        formData.append('avatar_file', file);
        formData.append('form_code', 'user-image');

        this.spinnerTarget.classList.remove('d-none');
        this.inputTarget.disabled = true;
        const ajaxUrl = this.inputTarget.dataset.ajaxurl;

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) throw new Error('Upload failed');
                return response.json();
            })
            .then(data => {
                this.updateAvatarImages(data.avatarUrl);
            })
            .catch(() => {
                alert('Upload error');
                this.previewTarget.src = this.oldSrc;
            })
            .finally(() => {
                this.spinnerTarget.classList.add('d-none');
                this.inputTarget.disabled = false;
            });
    }
}
