import {Controller} from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        trim: {type: Boolean, default: true},
        debounce: {type: Number, default: 200},
        autosaveUrl: String
    };
    debounceValue = 500;

    connect() {
        this.text = this.element;
        this.bindEvents();
    }

    disconnect() {
        this.text.removeEventListener('input', this.inputHandler);
        this.text.removeEventListener('blur', this.blurHandler);
    }

    bindEvents() {
        this.inputHandler = this.debounce(() => {
            this.autoSave().then(r => {
                console.log('autoSave r : ', r)
            });
        });

        this.blurHandler = () => {
            if (this.trimValue) {
                this.text.value = this.text.value.trim();
            }
        };
        this.text.addEventListener('input', this.inputHandler);
        this.text.addEventListener('blur', this.blurHandler);
    }

    async autoSave() {
        if (!this.hasAutosaveUrlValue) return;
        try {
            const recipeId = document.getElementById('recipe_id').value;
            const positionId = this.element.closest('.collection')?.querySelector('input[name*="[position]"]')?.value;
            console.log('autosave ',recipeId)
            console.log('positionId ',positionId)
            await fetch(this.autosaveUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    value: this.text.value,
                    field: this.text.getAttribute('field'),
                    recipe_id: recipeId,
                    position_id: positionId,
                    locale_code: this.text.getAttribute('locale')
                })
            });
        } catch (e) {
            console.warn('Autosave failed', e);
        }
    }

    debounce(callback) {
        let timeout;
        return () => {
            clearTimeout(timeout);
            timeout = setTimeout(callback, this.debounceValue);
        };
    }
}
