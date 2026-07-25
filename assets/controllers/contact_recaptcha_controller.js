import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['token'];

    static values = {
        siteKey: String,
        action: { type: String, default: 'contact' },
    };

    connect() {
        this.submitting = false;
        this.scriptPromise = null;
        this.boundSubmitHandler = this.handleSubmit.bind(this);
        this.element.addEventListener('submit', this.boundSubmitHandler);
        this.refreshToken().catch((error) => {
            console.error('reCAPTCHA init failed:', error);
        });
    }

    disconnect() {
        this.element.removeEventListener('submit', this.boundSubmitHandler);
    }

    handleSubmit(event) {
        if (this.submitting) {
            return;
        }

        event.preventDefault();
        this.submitting = true;

        this.refreshToken()
            .then(() => {
                this.element.removeEventListener('submit', this.boundSubmitHandler);
                this.element.submit();
            })
            .catch((error) => {
                console.error('reCAPTCHA submit failed:', error);
                this.submitting = false;
            });
    }

    loadScript() {
        if (window.grecaptcha) {
            return Promise.resolve();
        }

        if (this.scriptPromise) {
            return this.scriptPromise;
        }

        this.scriptPromise = new Promise((resolve, reject) => {
            const existing = document.querySelector('script[src*="google.com/recaptcha/api.js"]');
            if (existing) {
                existing.addEventListener('load', () => resolve(), { once: true });
                existing.addEventListener('error', reject, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = `https://www.google.com/recaptcha/api.js?render=${this.siteKeyValue}`;
            script.async = true;
            script.onload = () => resolve();
            script.onerror = reject;
            document.head.appendChild(script);
        });

        return this.scriptPromise;
    }

    refreshToken() {
        if (!this.siteKeyValue) {
            return Promise.reject(new Error('Missing reCAPTCHA site key'));
        }

        return this.loadScript().then(() => new Promise((resolve, reject) => {
            window.grecaptcha.ready(() => {
                window.grecaptcha
                    .execute(this.siteKeyValue, { action: this.actionValue })
                    .then((token) => {
                        this.tokenTarget.value = token;
                        resolve(token);
                    })
                    .catch(reject);
            });
        }));
    }
}
