import { Controller } from "@hotwired/stimulus";

export default class extends Controller {

    static targets = ["overlay", "content"];

    connect() {
        this.handleEscape = this.handleEscape.bind(this);
    }

    open(event) {
        const trigger = event.currentTarget;
        const rect = trigger.getBoundingClientRect();

        this.overlayTarget.classList.add("active");
        document.body.style.overflow = "hidden";

        // position under clicked button
        this.contentTarget.style.top =
            rect.bottom + window.scrollY + "px";

        this.contentTarget.style.left =
            rect.left + window.scrollX + "px";

        document.addEventListener("keydown", this.handleEscape);
    }

    close() {
        this.overlayTarget.classList.remove("active");
        document.body.style.overflow = "";
        document.removeEventListener("keydown", this.handleEscape);
    }

    closeOnOverlay(event) {
        if (event.target === this.overlayTarget) {
            this.close();
        }
    }

    handleEscape(event) {
        if (event.key === "Escape") {
            this.close();
        }
    }
    saveImage(event) {
        console.log('saveImage start')

        //clear popup section
        this.close()
    }
    saveName(event) {
        const self =this;
        const formData = new FormData();
        const formCode = 'user-name';
        console.log('save formCode : ', formCode)
        //load input data
        const inputs = this.contentTarget.querySelectorAll('input');
        console.log('save inputs : ', inputs)
        inputs.forEach(input => {
            if (input.name) {
                formData.append(input.name, input.value);
            }
        });
        formData.append('form_code', formCode)

        const baseSearchAjaxUrl = document.getElementById('button-open-name').dataset.ajaxurl;

        console.log('baseSearchAjaxUrl', baseSearchAjaxUrl)

        fetch(baseSearchAjaxUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(json => {
                if (json.errors) {
                    console.log('errors', json.errors)
                } else {
                    if (json.message) {
                        console.log('success  json', json.message)
                        self.changeFullName(json.message)
                        //clear popup section
                        self.close()
                    }
                }
            })
            .catch(error => {
                console.error('Submit error:', error)
            })
    }
    changeFullName(message){
        const inputFirstName = document.getElementById('first_name').value.trim();
        const inputLastName = document.getElementById('last_name').value.trim();
        const buttonOpenElement = document.getElementById('button-open-name');

        const fullNameElement = document.getElementById('user-full-name');
        const messageNameElement = document.getElementById('user-full-name-message');
        if(messageNameElement){
            messageNameElement.innerHTML = message;
            messageNameElement.classList.add('active');
        }

        const inputFullName = inputFirstName + ' ' + inputLastName;

        fullNameElement.innerHTML = '';
        fullNameElement.append(inputFullName);
        buttonOpenElement.innerHTML = this.getLabelByInputValue(inputFullName, 'name');

    }
    generateLabelByName(name) {
        return name
            .replace(/_/g, ' ')
            .replace(/\b\w/g, c => c.toUpperCase());
    }
    getLabelByInputValue(inputFullName, baseCode) {
        if (inputFullName.trim() !== '') {
            return 'Edit ' + baseCode;
        }
        return 'Add ' + baseCode;
    }
}
