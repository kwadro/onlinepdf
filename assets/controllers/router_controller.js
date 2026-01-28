import { Controller } from '@hotwired/stimulus';

/**
 * Простий SPA Router контролер для Symfony
 */
export default class extends Controller {
    static targets = ['link', 'content'];

    connect() {
        console.log('Router controller connected');
    }

    /**
     * Клік по посиланню
     */
    navigate(event) {
        event.preventDefault();
        const url = event.currentTarget.getAttribute('href');
        console.log('url',url)
        if (!url) return;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(html => {
                // Підставляємо контент у наш container
                this.contentTarget.innerHTML = html;

                // Оновлюємо URL у браузері
                window.history.pushState({}, '', url);
            })
            .catch(error => console.error('Fetch error:', error));
    }
}
