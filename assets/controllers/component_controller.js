import {Controller} from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    static values = {
        autosaveUrl: String
    };
    static targets = ['container'];

    connect() {
        if (this.hasContainerTarget) {
            this.index = this.containerTarget.querySelectorAll('.container-recipe-component-item').length;
            this.sortable = new Sortable(this.containerTarget, {
                animation: 150,
                onEnd: () => this.updatePositions()
            });
        } else {
            this.index = 0;
        }
    }
    setStatusSaveButton( saveButton, status){
        console.log('setStatusSaveButton saveButton',saveButton)
        console.log('setStatusSaveButton status',status)
        if(saveButton){
            if(status){
                saveButton.classList.remove('d-none');
            }else{
                saveButton.classList.add('d-none');
            }
        }
    }
    add(event) {
        event.preventDefault();
        if (!this.hasContainerTarget) {
            return;
        }
        const prototype = this.containerTarget.dataset.prototype;
        const newForm = prototype.replace(/__name__/g, this.index);

        const div = document.createElement('div');
        div.classList.add('container-recipe-component-item');
        div.innerHTML = newForm;
        div.querySelector('input[name*="[position]"]').value = this.index + 1;

        // remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-component';
        removeBtn.innerHTML = 'X';
        removeBtn.addEventListener('click', (event) => {
            event.preventDefault();
            const rightContentStart = document.getElementById('rightContent').offsetHeight;
            console.log('start 1 : ', rightContentStart)
            div.remove();
            this.updateUi(rightContentStart);
        });
        div.appendChild(removeBtn);
        this.containerTarget.appendChild(div);

        const groupItemElement = event.currentTarget.closest('.container-recipe-group-component-item');
        const saveButton = groupItemElement.querySelector('.group-save');
        this.setStatusSaveButton(saveButton,true)
        this.index++;
        document.body.style.height = (document.getElementById('rightContent').offsetHeight + 255) + 'px';
    }

    remove(event) {
        event.preventDefault();
        const rightContentStart = document.getElementById('rightContent').offsetHeight;
        console.log('rightContent start: ', rightContentStart)
        event.target.closest('.container-recipe-component-item').remove();
        this.updateUi(rightContentStart);
    }

    disconnect() {

    }

    init() {

    }

    updatePositions() {
        this.element.querySelectorAll('.recipe-component-item')
            .forEach((item, index) => {
                const input = item.querySelector('input[name*="[position]"]');
                if (input) input.value = index + 1;
            });
    }

    async autoSavePosition() {
        console.log('autoSave  position')
        if (!this.hasAutosaveUrlValue) return;
    }
    updateUi(rightContentStart) {
        const rightElement = document.getElementById('rightContent')
        if (rightElement) {
            const rightContentFinish =rightElement.offsetHeight;
            console.log('rightContent finish: ', rightContentFinish)
            const diff = rightContentStart - rightContentFinish;
            console.log('rightContent diff: ', diff)
            const bodyHeightStart = document.body.offsetHeight;
            console.log('body start height: ', document.body.offsetHeight)
            console.log('body height: ', document.body.style.height)
            const bodyHeightFinish = (bodyHeightStart - diff) + 'px'
            console.log('body heightFinish: ', bodyHeightFinish)
            // recalculate body height
            document.body.style.height = bodyHeightFinish
        }
    }
}
