
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        console.log('navbar_controller.js start')
        this.element.addEventListener('shown.bs.collapse', () => {
            console.log('shown.bs.collapse')
            document.body.classList.add('body-lock');
        });

        this.element.addEventListener('hidden.bs.collapse', () => {
            console.log('hidden.bs.collapse')
            document.body.classList.remove('body-lock');
        });
    }
}
