import { Controller } from '@hotwired/stimulus';
import { requestLayoutRemeasure } from '../js/layout-chrome';

export default class extends Controller {
    static targets = ['imagePanel', 'textPanel', 'imageTab', 'textTab'];

    showImage(event) {
        event.preventDefault();
        this.activateTab('image');
    }

    showText(event) {
        event.preventDefault();
        this.activateTab('text');
    }

    activateTab(tab) {
        const isImage = tab === 'image';

        this.imagePanelTarget.classList.toggle('d-none', !isImage);
        this.textPanelTarget.classList.toggle('d-none', isImage);
        this.imageTabTarget.classList.toggle('is-active', isImage);
        this.textTabTarget.classList.toggle('is-active', !isImage);
        this.imageTabTarget.setAttribute('aria-selected', isImage ? 'true' : 'false');
        this.textTabTarget.setAttribute('aria-selected', !isImage ? 'true' : 'false');

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                requestLayoutRemeasure();
            });
        });
    }
}
