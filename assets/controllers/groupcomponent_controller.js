import {Controller} from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    static values = {
        autosaveUrl: String
    };
    static targets = ['container'];

    connect() {
        this.statusSave ={};
        if (this.hasContainerTarget) {
            const groupItemElements = this.containerTarget.querySelectorAll('.container-recipe-group-component-item');
            this.index = groupItemElements.length;
            this.sortable = new Sortable(this.containerTarget, {
                animation: 150,
                onEnd: () => this.updatePositions()
            });
        } else {
            this.index = 0;
        }

    }
    add(event) {
        const self = this;
        event.preventDefault();
        if (!this.hasContainerTarget) {
            return;
        }
        const prototype = this.containerTarget.dataset.prototype;
        const newForm = prototype.replace(/__name__/g, this.index);

        const componentFormElement = this.containerTarget.querySelector('.recipecomponents');
        let componentForm = '';
        let componentPrototype = null;
        if (componentFormElement) {
            componentPrototype = componentFormElement.dataset.prototype;
            componentForm = componentPrototype.replace(/__name__/g, this.index);
        }
        const div = document.createElement('div');
        div.classList.add('container-recipe-group-component-item');
        div.classList.add('collection');
        div.setAttribute(
            'data-controller',
            'component'
        );
        div.innerHTML = newForm;

        if (div.querySelector('input[name*="[position]"]')) {
            div.querySelector('input[name*="[position]"]').value = this.index + 1;
        }

        // save button
        const buttonSaveDiv = document.createElement('div');
        buttonSaveDiv.className = 'group-save d-none';

        const buttonSave = document.createElement('button');
        buttonSave.type = 'button';
        buttonSave.className = 'mb-1 fs-6 btn btn-success';
        buttonSave.id = 'recipe_save';
        buttonSave.setAttribute(
            'data-action',
            'click->scroll#saveScroll'
        );
        buttonSave.setAttribute(
            'name',
            'recipe[save]'
        );
        buttonSave.innerHTML = 'Зберегти';
        buttonSaveDiv.appendChild(buttonSave)

        // remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-step';
        removeBtn.innerHTML = 'X';
        removeBtn.addEventListener('click',
            () => {
                div.remove();
                this.updateUi();
                this.index--;
            });
        div.appendChild(removeBtn);

        const buttonAdd = document.createElement('button');
        buttonAdd.type = 'button';
        buttonAdd.id = 'add-component';
        buttonAdd.className = 'add-component';

        buttonAdd.setAttribute(
            'data-action',
            'click->component#add'
        );

        buttonAdd.setAttribute('title', 'Add Component');

        buttonAdd.textContent = '+';
        if (3===4 && componentPrototype) {
            const divComponent = document.createElement('div');
            divComponent.innerHTML = componentForm;
            divComponent.classList.add('recipecomponents');
            divComponent.setAttribute(
                'id',
                'recipe-components'
            );
            divComponent.setAttribute(
                'data-sotable-target',
                'container'
            );
            divComponent.setAttribute(
                'data-component-target',
                'container'
            );

            divComponent.setAttribute(
                'data-prototype',
                componentPrototype
            );
            const innerDiv = divComponent.firstElementChild;
            const divComponentContainer = document.createElement('div');
            divComponentContainer.classList.add('container-recipe-component-item');
            divComponentContainer.classList.add('collection');
            divComponentContainer.setAttribute(
                'data-controller',
                'component'
            );
            if (innerDiv) {
                divComponentContainer.innerHTML = innerDiv.outerHTML;
            }
            const buttonClose = document.createElement('button');
            buttonClose.type = 'button';
            buttonClose.className = 'remove-component';
            buttonClose.setAttribute('title', 'Remove Component');
            buttonClose.textContent = 'X';
            buttonClose.setAttribute(
                'data-action',
                'click->component#remove'
            );

            divComponentContainer.appendChild(buttonClose);
            divComponent.innerHTML = divComponentContainer.outerHTML;
            // div.appendChild(buttonSaveDiv);
            div.appendChild(divComponent);
            div.appendChild(buttonAdd);

        }
        this.containerTarget.appendChild(div);
        this.index++;
        document.body.style.height = (document.getElementById('rightContent').offsetHeight + 220) + 'px';
    }

    remove(event) {
        event.preventDefault();
        console.log('start: ', document.getElementById('rightContent').offsetHeight)
        //removed block
        event.target.closest('.container-recipe-group-component-item').remove();
        this.updateUi();
        this.index--;
    }

    disconnect() {

    }

    init() {

    }

    updateUi() {
        console.log('start: ', document.getElementById('rightContent').offsetHeight)
        //update positions after remove block
        this.updatePositions()
        //save data after remove block
        this.autoSubmit();
        console.log('finish: ', document.getElementById('rightContent').offsetHeight)
        console.log('body height: ', document.body.style.height)

        // recalculate body height
        document.body.style.height = (document.getElementById('rightContent').offsetHeight + 220) + 'px';
    }

    autoSubmit() {
        document.getElementById('recipe_save').click();
    }

    updatePositions() {
        this.element.querySelectorAll('.container-recipe-group-component-item')
            .forEach((item, index) => {
                const input = item.querySelector('input[name*="[position]"]');
                if (input) input.value = index + 1;
            });
    }
}
