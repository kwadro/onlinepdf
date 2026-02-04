import tinymce from 'tinymce';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';

import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/code';

document.addEventListener('DOMContentLoaded', function () {
    initTinyMce();
});

document.addEventListener('ea.form.rendered', function (event) {
    initTinyMce(event.target);
});

function initTinyMce(context = document) {
    context.querySelectorAll('textarea.tinymce').forEach(function (el) {
        if (el.classList.contains('tinymce-initialized')) {
            return;
        }

        tinymce.init({
            target: el,
            height: 400,
            menubar: false,
            license_key: 'gpl',
            base_url: '/build/tinymce',
            suffix: '.min',
            plugins: 'image link lists code table',
            toolbar: 'undo redo | bold italic | bullist numlist | link image | code',
            automatic_uploads: true,
            images_upload_url: '/admin/tinymce/upload',
            images_reuse_filename: true,
            relative_urls: false,
            remove_script_host: false,

            setup: function () {
                el.classList.add('tinymce-initialized');
            }
        });
    });
}
// assets/admin/easyadmin-image-preview.js

document.addEventListener('DOMContentLoaded', () => {

    document.addEventListener('change', function (e) {
        const input = e.target;

        if (!input.matches('input[type="file"]')) {
            return;
        }

        if (!input.files || !input.files[0]) {
            removePreview(input);
            return;
        }

        const file = input.files[0];

        if (!file.type.startsWith('image/')) {
            removePreview(input);
            return;
        }

        createPreview(input, file);
    });

    document.addEventListener('click', function (e) {
        // EasyAdmin delete file button
        if (e.target.closest('[data-action="delete"]')) {
            const wrapper = e.target.closest('.form-group, .field-file');
            if (!wrapper) return;

            const input = wrapper.querySelector('input[type="file"]');
            if (input) {
                removePreview(input);
            }
        }
    });
});

function createPreview(input, file) {
    removePreview(input);

    const img = document.createElement('img');
    img.className = 'ea-image-preview';
    img.style.maxWidth = '200px';
    img.style.marginTop = '10px';
    img.style.borderRadius = '6px';

    img.src = URL.createObjectURL(file);

    input.closest('.form-group, .field-file')?.appendChild(img);
}

function removePreview(input) {
    const wrapper = input.closest('.form-group, .field-file');
    if (!wrapper) return;

    const preview = wrapper.querySelector('.ea-image-preview');
    if (preview) {
        preview.remove();
    }
}


