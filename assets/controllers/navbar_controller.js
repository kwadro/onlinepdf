
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('shown.bs.collapse', () => {
            document.body.classList.add('body-lock');
        });

        this.element.addEventListener('hidden.bs.collapse', () => {
            document.body.classList.remove('body-lock');
        });
    }
}
