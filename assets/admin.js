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
