import { Controller } from '@hotwired/stimulus';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
export default class extends Controller {

    static targets = ['input', 'preview', 'spinner'];
    connect() {
        this.cropper = null;
    }
    change() {
        const file = this.inputTarget.files[0];
        if (!file) return;

        // validate type
        if (!file.type.startsWith('image/')) {
            alert('Only image files allowed');
            this.inputTarget.value = '';
            return;
        }

        // validate size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('Max file size is 2MB');
            this.inputTarget.value = '';
            return;
        }
        // save old image for rollback
        this.oldSrc = this.previewTarget.src;

        // preview
        const reader = new FileReader();
        reader.onload = (e) => {
            this.previewTarget.src = e.target.result;
            if (this.cropper) {
                this.cropper.destroy();
            }
            this.cropper = new Cropper(this.previewTarget, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 1,
                responsive: true,
            });
        };
        reader.readAsDataURL(file);

        // auto upload
        //this.upload(file);
    }
    saveRecipe() {
        console.log('saveRecipe  start',this.cropper)
        if (!this.cropper) return;

        const canvas = this.cropper.getCroppedCanvas({
            width: 300,
            height: 300,
        });

        canvas.toBlob((blob) => {
            const recipeId = document.getElementById('recipe_id').value;
            console.log('saveRecipe recipeId',recipeId)
            console.log('saveRecipe blob',blob)
            try{
                const formData = new FormData();
                formData.append('recipe_file', blob,'recipe.png');
                formData.append('form_code', 'recipe-image');
                formData.append('id', recipeId);
                console.log('saveRecipe formData : ',formData)
                const baseAjaxUrl = this.inputTarget.dataset.ajaxurl;

                console.log('saveRecipe baseAjaxUrl',baseAjaxUrl)


                fetch(baseAjaxUrl, {
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
                        console.log('Upload success', data.imageUrl);
                        document.getElementById('recipe-image').src = '/uploads/recipes/' + data.imageUrl;
                    })
            }catch (e){
                console.log('error',e)
            }
        });
    }
    saveAvatar() {
        console.log('save avatar  start',this.cropper)
        if (!this.cropper) return;

        const canvas = this.cropper.getCroppedCanvas({
            width: 300,
            height: 300,
        });

        canvas.toBlob((blob) => {
            const formData = new FormData();
            formData.append('image_file', blob,'avatar.png');
            formData.append('form_code', 'avatar-image');
            const baseSearchAjaxUrl = this.inputTarget.dataset.ajaxurl;
            console.log('baseSearchAjaxUrl',baseSearchAjaxUrl)
            console.log('formData',formData)
            fetch(baseSearchAjaxUrl, {
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
                    console.log('Upload success', data.avatarUrl);
                    document.getElementById('user-image').src = '/uploads/avatars/' + data.avatarUrl;
                    document.getElementById('user-header-image').src = '/uploads/avatars/' + data.avatarUrl;
                })
        });
    }
    upload(file) {
        const formData = new FormData();
        formData.append('avatar_file', file);
        formData.append('form_code', 'user-image');
        console.log('upload formData : ',formData)
        // show spinner
        this.spinnerTarget.classList.remove('d-none');
        this.inputTarget.disabled = true;
        const baseSearchAjaxUrl = this.inputTarget.dataset.ajaxurl;
        console.log('baseSearchAjaxUrl',baseSearchAjaxUrl)
        fetch(baseSearchAjaxUrl, {
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
                console.log('Upload success', data);
                this.previewTarget.src = '/uploads/avatars/' + data.avatarUrl;

            })
            .catch(error => {
                alert('Upload error');
                this.previewTarget.src = this.oldSrc; // rollback
            })
            .finally(() => {
                this.spinnerTarget.classList.add('d-none');
                this.inputTarget.disabled = false;
            });
    }
}
