import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.input = document.getElementById('search-input');
        if (!this.input) {
            return;
        }

        this.onEnter = this.onEnter.bind(this);
        this.input.addEventListener('keydown', this.onEnter);
    }

    disconnect() {
        if (this.input && this.onEnter) {
            this.input.removeEventListener('keydown', this.onEnter);
        }
    }

    onEnter(event) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        this.open(event);
    }

    open(event) {
        event?.preventDefault?.();

        const input = document.getElementById('search-input');
        if (!input) {
            return;
        }

        const query = input.value.trim();
        if (query === '' || !input.dataset.baseurl) {
            return;
        }

        const redirectUrl = input.dataset.baseurl.replace('/keyword', `/${encodeURIComponent(query)}`);
        window.location.href = redirectUrl;
    }
}
