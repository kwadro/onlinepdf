import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal', 'content', 'select'];

    connect() {
        console.log('AddNew controller connected');
    }

    open(event) {
        event.preventDefault();
        console.log('locale open', this.contentTarget)
        // open modal window
        this.modalTarget.classList.remove('d-none');

        // if you need  AJAX, can load form
        const url = event.currentTarget.getAttribute('href');
        if (url) {
            fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(r => r.text())
                .then(html => {
                    this.contentTarget.innerHTML = html;
                });
        }
    }

    submitForm(event) {
        event.preventDefault();
        const url = event.currentTarget.getAttribute('data-href');
        const data = new FormData()
        this.contentTarget
            .querySelectorAll('input, select, textarea')
            .forEach(el => {
                if (!el.name) return
                const name = el.name.replace(/^.+\[(.+)]$/, '$1')
                data.append(name, el.value)
            })

        this.cleanErrors()

        fetch(url, {
            method: 'POST',
            body: data,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(json => {
                if (json.errors) {
                    console.log('json.errors', json.errors)
                    this.showErrors(json.errors)
                } else {
                    if (json.html) {
                        this.contentTarget.innerHTML = json.html
                    }
                    if (json.newLocale) {
                        this.addOptionToSelect(json.newLocale)
                    }
                    if (json.success) {
                        this.close(event)
                    }
                }

            })
            .catch(error => {
                console.error('Submit error:', error)
            })

    }

    addOptionToSelect(newLocale) {
        const option = document.createElement('option')
        option.value = newLocale.id
        option.text = newLocale.name
        option.selected = true
        this.selectTarget.appendChild(option)
    }

    showErrors(errors) {
        Object.entries(errors).forEach(([field, message]) => {
            const input = this.contentTarget.querySelector(
                `[name$="[${field}]"]`
            )
            console.log('message', message)
            console.log('field', field)
            console.log('input', input)
            if (!input) return

            input.classList.add('is-invalid')

            const error = document.createElement('div')
            error.classList.add('invalid-feedback')
            error.innerText = message

            input.closest('.mb-3')?.appendChild(error)
        })
    }

    cleanErrors() {
        this.contentTarget
            .querySelectorAll('.invalid-feedback')
            .forEach(el => {
                el.remove()
            })
        this.contentTarget
            .querySelectorAll('.is-invalid')
            .forEach(el => el.classList.remove('is-invalid'))
    }

    close(event) {
        event.preventDefault();
        this.modalTarget.classList.add('d-none');
    }
}

