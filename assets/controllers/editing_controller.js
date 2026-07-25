import {Controller} from '@hotwired/stimulus';
import {EditorClass} from '../js/editor';

export default class extends Controller {
    static targets = ['container', 'fullName', 'resourceImage']

    connect() {
        console.log('connect editing_controller.js')
    }

    saveName(event) {
        const formData = new FormData();
        //load input data
        const inputs = this.fullNameTarget.querySelectorAll('input');
        inputs.forEach(input => {
            if (input.name) {
                formData.append(input.name, input.value);
            }
        });
        formData.append('form_code', 'user-name')

        const baseSearchAjaxUrl = event.target.dataset.ajaxurl;
        fetch(baseSearchAjaxUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(json => {
                if (json.errors) {
                    console.log('errors', json.errors)
                } else {
                    if (json.message) {
                        console.log('success  json', json.message)
                    }
                }
            })
            .catch(error => {
                console.error('Submit error:', error)
            })
    }

    openName(event) {
        event.preventDefault();

        let userFullName = this.fullNameTarget.textContent.trim();
        if (userFullName === '—' || userFullName === '–' || userFullName === '-') {
            userFullName = '';
        }

        const parts = userFullName.split(/\s+/).filter(Boolean);
        const firstName = parts[0] || '';
        const lastName = parts.slice(1).join(' ');

        const instance = new EditorClass();
        instance.addFieldsToPopup(
            [
                {'first_name': {'value': firstName, 'type': 'text', 'class': 'me-1'}},
                {'last_name': {'value': lastName, 'type': 'text'}}
            ], {'type': 'Name'}
        )
    }

    openImage(event) {
        event.preventDefault();
        let imageValue = this.resourceImageTarget.src ;
        const entity = event.currentTarget.dataset.entity;
        const instance = new EditorClass();
        instance.addFieldsToPopup(
            [
                {
                    'image_file': {
                        'ajaxurl': event.target.dataset.ajaxurl,
                        'value': imageValue,
                        'type': 'image',
                        'class': 'me-1,w-100,user-select-none'
                    }
                },
            ], {'type': 'Image','entity': entity}
        );
    }
}
