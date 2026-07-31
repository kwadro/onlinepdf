import { Controller } from '@hotwired/stimulus';
import { requestLayoutRemeasure } from '../js/layout-chrome';

export default class extends Controller {
    static targets = [
        'imagePanel',
        'textPanel',
        'imageTab',
        'textTab',
        'templateInput',
        'designOption',
    ];

    static values = {
        autosaveUrl: String,
        locale: String,
        site: String,
    };

    connect() {
        this.syncDesignSelection();
    }

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

    selectDesign(event) {
        event.preventDefault();

        const template = event.currentTarget.dataset.template;
        if (!this.hasTemplateInputTarget || !template) {
            return;
        }

        if (this.templateInputTarget.value === template) {
            return;
        }

        this.templateInputTarget.value = template;
        this.syncDesignSelection();
        this.saveTemplate(template);
    }

    syncDesignSelection() {
        if (!this.hasTemplateInputTarget || !this.hasDesignOptionTarget) {
            return;
        }

        const activeTemplate = this.templateInputTarget.value || '1';
        this.designOptionTargets.forEach((option) => {
            option.classList.toggle('is-active', option.dataset.template === activeTemplate);
        });
    }

    saveTemplate(template) {
        if (!this.hasAutosaveUrlValue) {
            return;
        }

        const recipeIdElement = document.getElementById('facebook_setting_recipe_id')
            || document.getElementById('recipe_id');

        if (!recipeIdElement) {
            return;
        }

        fetch(this.autosaveUrlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                value: template,
                field: 'facebook-template',
                id: recipeIdElement.value,
                locale_code: this.localeValue,
                site_id: this.siteValue,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    this.refreshPreviewImage();
                }
            })
            .catch((error) => {
                console.warn('Template save failed', error);
            })
            .finally(() => {
                requestLayoutRemeasure();
            });
    }

    refreshPreviewImage() {
        const imageElement = document.getElementById('recipe-facebook-image');
        if (!imageElement) {
            return;
        }

        imageElement.src = `${imageElement.src.split('?')[0]}?t=${Date.now()}`;
    }
}
