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

        const isEmail = el.classList.contains('tinymce-email');
        const baseConfig = {
            target: el,
            height: isEmail ? 560 : 400,
            menubar: isEmail,
            license_key: 'gpl',
            base_url: '/build/tinymce',
            suffix: '.min',
            plugins: isEmail ? 'link lists code table fullscreen' : 'image link lists code table',
            toolbar: isEmail
                ? 'fullscreen | undo redo | code | bold italic underline | alignleft aligncenter alignright | bullist numlist | link'
                : 'undo redo | bold italic | bullist numlist | link image | code',
            automatic_uploads: !isEmail,
            images_upload_url: isEmail ? undefined : '/admin/tinymce/upload',
            images_reuse_filename: true,
            relative_urls: false,
            remove_script_host: false,
            setup: function () {
                el.classList.add('tinymce-initialized');
            },
        };

        if (isEmail) {
            Object.assign(baseConfig, {
                verify_html: false,
                valid_elements: '*[*]',
                extended_valid_elements: 'style,link[href|rel|type],meta[*],head[*],body[*],html[*]',
                convert_urls: false,
                content_style: 'body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; margin: 16px; }',
            });
        }

        tinymce.init(baseConfig);
    });
}

// easyadmin-image-preview.js
initExistingPreviews();
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
        if (e.target.closest('.ea-fileupload-delete-btn')) {
            const wrapper = e.target.closest('.form-group, .field-file');
            if (!wrapper) return;

            const input = wrapper.querySelector('input[type="file"]');
            if (input) {
                removePreview(input);
            }
        }
    });
});

function initExistingPreviews() {

    document.querySelectorAll('.field-file, .field-image').forEach(wrapper => {
        const fileLabel = wrapper.querySelector('.custom-file-label');
        if (!fileLabel) return;
        const fileName = fileLabel.innerHTML;
        const downloadPath = wrapper.querySelector('.download-path')
        const href = `/${downloadPath.innerHTML}${fileName}`;
        if (!href) return;
        createPreview(wrapper, href, 'link');
    });
}

function createPreview(input, file, type = 'file') {
    removePreview(input);
    const wrapper = input.closest('.form-group, .field-file');

    const deleteBtn = wrapper.querySelector('.ea-fileupload-delete-btn');
    deleteBtn?.classList.remove('d-none');
    deleteBtn?.classList.add('d-block');

    const img = document.createElement('img');
    img.className = 'ea-image-preview';
    img.style.maxWidth = '100px';
    img.style.marginTop = '10px';
    img.style.borderRadius = '6px';

    if (type === 'file') {
        img.src = URL.createObjectURL(file);
    } else {
        img.src = file;
    }

    wrapper?.appendChild(img);
}

function removePreview(input) {
    const wrapper = input.closest('.form-group, .field-file');
    if (!wrapper) return;

    const preview = wrapper.querySelector('.ea-image-preview');
    if (preview) {
        preview.remove();
    }
}


