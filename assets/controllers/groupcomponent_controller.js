import {Controller} from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    static values = {
        autosaveUrl: String
    };
    static targets = ['container'];

    connect() {
        this.index = this.containerTarget.querySelectorAll('.container-recipe-group-component-item').length;

        this.sortable = new Sortable(this.containerTarget, {
            animation: 150,
            onEnd: () => this.updatePositions()
        });
    }

    add(event) {
        event.preventDefault();
        const prototype = this.containerTarget.dataset.prototype;
        const newForm = prototype.replace(/__name__/g, this.index);

        const div = document.createElement('div');
        div.classList.add('container-recipe-group-component-item');
        div.innerHTML = newForm;

        if(div.querySelector('input[name*="[position]"]')){
            div.querySelector('input[name*="[position]"]').value = this.index + 1;
        }


        // remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-step';
        removeBtn.innerHTML = 'X';
        removeBtn.addEventListener('click',
            ()=>{
                console.log('start 1 : ',document.getElementById('rightContent').offsetHeight)
                div.remove();
                console.log('finish 1 : ',document.getElementById('rightContent').offsetHeight)
                document.body.style.height = (document.getElementById('rightContent').offsetHeight + 220) + 'px';
        });
        div.appendChild(removeBtn);
        this.containerTarget.appendChild(div);
        this.index++;
        document.body.style.height = (document.getElementById('rightContent').offsetHeight + 220) + 'px';
    }

    remove(event) {
        event.preventDefault();
        console.log('start: ',document.getElementById('rightContent').offsetHeight)
        event.target.closest('.container-recipe-group-component-item').remove();
        console.log('finish: ',document.getElementById('rightContent').offsetHeight)
        console.log('body height: ',document.body.style.height)
        this.autoSubmit();
        document.body.style.height = (document.getElementById('rightContent').offsetHeight + 220) + 'px';
    }

    disconnect() {

    }

    init() {

    }

    updatePositions() {
        this.element.querySelectorAll('.recipe-group-component-item')
            .forEach((item, index) => {
                const input = item.querySelector('input[name*="[position]"]');
                if (input) input.value = index + 1;
            });
        this.autoSavePosition().then(r => {
            console.log('response : ', r)
        });
        this.autoSubmit();
    }

    async autoSavePosition() {
        console.log('autoSave  position')
        if (!this.hasAutosaveUrlValue) return;
    }

    autoSubmit() {
        const form = document.querySelector('form');
        console.log('form ', form)
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
            .then(response => response.text())
            .then(() => {
                console.log('Saved!');
            })
            .catch(() => {
                console.error('Save error');
            });
    }
}
