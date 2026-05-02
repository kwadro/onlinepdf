import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        trim: { type: Boolean, default: true },
        debounce: { type: Number, default: 200 },
        autosaveUrl: String
    };
    debounceValue= 500;
    connect() {
        this.textarea = this.element;

        this.createUI();
        this.init();
        this.bindEvents();
    }

    disconnect() {
        this.textarea.removeEventListener('input', this.inputHandler);
        this.textarea.removeEventListener('blur', this.blurHandler);
    }

    init() {
        this.animateResize();
        this.updateUI();
    }

    bindEvents() {
        this.inputHandler = this.debounce(() => {
            this.animateResize();
            this.updateUI();
            this.autoSave().then(r => {console.log('autoSave r : ',r)});
        });

        this.blurHandler = () => {
            if (this.trimValue) {
                this.textarea.value = this.textarea.value.trim();
                this.updateUI();
            }
        };

        this.textarea.addEventListener('input', this.inputHandler);
        this.textarea.addEventListener('blur', this.blurHandler);
    }

    createUI() {
        if (this.textarea.nextElementSibling?.classList.contains('mt-2')) {
            return;
        }
        this.container = document.createElement('div');
        this.container.className = 'mt-2';

        // Progress bar
        this.progressWrapper = document.createElement('div');
        this.progressWrapper.className = 'progress';
        this.progressWrapper.style.height = '4px';

        this.progressBar = document.createElement('div');
        this.progressBar.className = 'progress-bar';
        this.progressBar.role = 'progressbar';

        this.progressWrapper.appendChild(this.progressBar);

        // Counter
        this.counter = document.createElement('small');
        this.counter.className = 'text-muted d-block mt-1';

        this.container.appendChild(this.progressWrapper);
        this.container.appendChild(this.counter);

        this.textarea.insertAdjacentElement('afterend', this.container);
    }

    animateResize() {
        this.textarea.style.transition = 'height 0.15s ease';
        this.textarea.style.height = 'auto';
        this.textarea.style.height = `${this.textarea.scrollHeight}px`;
    }

    updateUI() {
        const max = parseInt(this.textarea.getAttribute('maxlength'));
        const length = this.textarea.value.length;

        this.counter.textContent = max
            ? `${length} / ${max}`
            : `${length}`;

        if (!max) return;

        const percent = Math.min((length / max) * 100, 100);

        this.progressBar.style.width = percent + '%';

        if (percent > 100) {
            this.progressBar.classList.add('bg-danger');
            this.textarea.classList.add('is-invalid');
        } else if (percent > 80) {
            this.progressBar.classList.add('bg-warning');
            this.progressBar.classList.remove('bg-danger');
            this.textarea.classList.remove('is-invalid');
        } else {
            this.progressBar.classList.remove('bg-danger', 'bg-warning');
            this.textarea.classList.remove('is-invalid');
        }
    }

    async autoSave() {
        if (!this.hasAutosaveUrlValue) return;

        try {
            const recipeId = document.getElementById('recipe_id').value;
            const positionId = this.element.closest('.collection')?.querySelector('input[name*="[position]"]')?.value;
            console.log('area autosave ',recipeId)
            console.log('area positionId ',positionId)
            await fetch(this.autosaveUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    value: this.textarea.value,
                    field: this.textarea.getAttribute('field'),
                    id: recipeId,
                    position_id: positionId,
                    locale_code: this.textarea.getAttribute('locale')
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
