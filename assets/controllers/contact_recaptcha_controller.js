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
        this.boundTurboRender = this.onTurboRender.bind(this);
        this.boundBeforeCache = this.onBeforeCache.bind(this);
        this.boundExternalRefresh = () => this.scheduleRefresh();

        this.element.addEventListener('submit', this.boundSubmitHandler);
        this.element.addEventListener('contact-recaptcha:refresh', this.boundExternalRefresh);
        document.addEventListener('turbo:render', this.boundTurboRender);
        document.addEventListener('turbo:before-cache', this.boundBeforeCache);

        this.scheduleRefresh();
    }

    disconnect() {
        this.element.removeEventListener('submit', this.boundSubmitHandler);
        this.element.removeEventListener('contact-recaptcha:refresh', this.boundExternalRefresh);
        document.removeEventListener('turbo:render', this.boundTurboRender);
        document.removeEventListener('turbo:before-cache', this.boundBeforeCache);
    }

    onBeforeCache() {
        if (this.hasTokenTarget) {
            this.tokenTarget.value = '';
        }
    }

    onTurboRender(event) {
        const newBody = event.detail?.newBody;
        if (!newBody || !newBody.querySelector('[data-controller~="contact-recaptcha"]')) {
            return;
        }

        this.scheduleRefresh();
    }

    scheduleRefresh() {
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                if (!this.element.isConnected || !this.hasTokenTarget) {
                    return;
                }

                this.refreshToken().catch((error) => {
                    console.error('reCAPTCHA init failed:', error);
                });
            });
        });
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
            const resolveWhenReady = (attempt = 0) => {
                if (window.grecaptcha) {
                    resolve();
                    return;
                }

                if (attempt >= 50) {
                    reject(new Error('reCAPTCHA failed to load'));
                    return;
                }

                window.setTimeout(() => resolveWhenReady(attempt + 1), 100);
            };

            const existing = document.querySelector('script[src*="google.com/recaptcha/api.js"]');
            if (existing) {
                existing.addEventListener('load', () => resolveWhenReady(), { once: true });
                existing.addEventListener('error', reject, { once: true });
                resolveWhenReady();
                return;
            }

            const script = document.createElement('script');
            script.src = `https://www.google.com/recaptcha/api.js?render=${this.siteKeyValue}`;
            script.async = true;
            script.onload = () => resolveWhenReady();
            script.onerror = reject;
            document.head.appendChild(script);
        });

        return this.scriptPromise;
    }

    refreshToken(retry = 0) {
        if (!this.siteKeyValue) {
            return Promise.reject(new Error('Missing reCAPTCHA site key'));
        }

        if (!this.hasTokenTarget) {
            return Promise.reject(new Error('Missing reCAPTCHA token field'));
        }

        return this.loadScript().then(() => new Promise((resolve, reject) => {
            window.grecaptcha.ready(() => {
                window.grecaptcha
                    .execute(this.siteKeyValue, { action: this.actionValue })
                    .then((token) => {
                        if (!this.hasTokenTarget) {
                            reject(new Error('reCAPTCHA token field was removed'));
                            return;
                        }

                        this.tokenTarget.value = token;
                        resolve(token);
                    })
                    .catch((error) => {
                        if (retry < 3) {
                            window.setTimeout(() => {
                                this.refreshToken(retry + 1).then(resolve).catch(reject);
                            }, 250);
                            return;
                        }

                        reject(error);
                    });
            });
        }));
    }
}

const refreshContactRecaptchaForms = () => {
    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
            document.querySelectorAll('[data-controller~="contact-recaptcha"]').forEach((element) => {
                element.dispatchEvent(new CustomEvent('contact-recaptcha:refresh', { bubbles: false }));
            });
        });
    });
};

document.addEventListener('turbo:load', refreshContactRecaptchaForms);
