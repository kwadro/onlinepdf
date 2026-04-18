export class EditorClass {

    constructor(version) {
        this.version = version;
        this.AVAILABLE_INPUT_PARAMS = ['value', 'name', 'id','src','innerText'];
    }
    showElement(element) {
        element.classList.remove('d-none')
        element.classList.add('d-block')
    }
    hideElement(element) {
        element.classList.add('d-none')
        element.classList.remove('d-block')
    }
    addFieldsToPopup(fieldParams, runParams) {
        const typeData = runParams.type;
        const entityData = runParams.entity;
        const editElements = [];
        fieldParams.forEach(fieldParam => {
            for (const fieldName in fieldParam) {
                const fieldData = fieldParam[fieldName];
                const baseContainerClass ='d-flex,flex-column,gap-2,align-items-center';
                const fullContainerClass = ('class' in fieldData) ? baseContainerClass+',' + fieldData.class : baseContainerClass;
                const classNames = 'form-control';
                if (fieldData.type === 'text') {
                    const elementParams =
                        {
                            'tag': 'div',
                            'class': fullContainerClass,
                            'children': [
                                {
                                    'tag': 'label',
                                    'class': 'form-label,small',
                                    'innerText': this.generateLabelByName(fieldName),
                                },
                                {
                                    'tag': 'input',
                                    'typeInput': 'text',
                                    'value': fieldData.value,
                                    'class': classNames,
                                    'id': fieldName,
                                    'name': fieldName,
                                    'attributes': [{'autocomplete': 'given-name'}]
                                }
                            ]
                        };
                    editElements.push(this.generateElement(elementParams))
                }
                if (fieldData.type === 'image') {
                    const elementParams =
                        {
                            'tag': 'div',
                            'class': fullContainerClass,
                            'children': [
                                {
                                    'tag': 'label',
                                    'class': 'form-label,small',
                                    'innerText': this.generateLabelByName(fieldName),
                                },
                                {
                                    'tag': 'input',
                                    'typeInput': 'file',
                                    'class': classNames,
                                    'id': fieldName,
                                    'name': fieldName,
                                    'attributes': [{'data-avatar-target': 'input'},{'data-action': 'change->avatar#change'},{'data-ajaxurl': fieldData.ajaxurl}]
                                },
                                {
                                    'tag': 'img',
                                    'class': 'profile-image',
                                    'src': fieldData.value,
                                    'attributes': [{'data-avatar-target': 'preview'}]
                                },
                                {
                                    'tag': 'div',
                                    'class': 'position-absolute,top-50,start-50,translate-middle,d-none',
                                    'src': fieldData.value,
                                    'attributes': [{'data-avatar-target': 'spinner'}],
                                    'children':[
                                        {
                                            'tag': 'div',
                                            'class': 'spinner-border,text-primary'
                                        }
                                    ]
                                },
                            ]
                        };
                    editElements.push(this.generateElement(elementParams))
                }
            }
        })
        let saveAction='';
        if (typeData==='Image'){
            saveAction+='click->avatar#save'+ entityData;
        }
         saveAction += ' click->popup#save' + typeData;


        const divParams = {
            'tag': 'div',
            'class': 'd-flex,flex-row,justify-content-start,align-items-center,mb-4',
            'children': [
                {
                    'tag': 'button',
                    'id': 'button-save-' + typeData.toLowerCase(),
                    'class': 'save-popup,save-btn,btn-success,small',
                    'attributes': [{'data-action':saveAction}],
                    'innerText': 'Save',
                },
                {
                    'tag': 'button',
                    'id': 'closePopup',
                    'class': 'close-btn',
                    'attributes': [{'data-action': 'click->popup#close'}],
                    'innerText': 'x',
                }
            ]
        }
        const divElement = this.generateElement(divParams)
        console.log('divElement', divElement)
        console.log('editElements', editElements)
        editElements.forEach((editElement) => {
            divElement.append(editElement)
        })
        const popupContentElement = document.getElementById('popup');
        popupContentElement.innerHTML = '';
        popupContentElement.appendChild(divElement);
    }

    generateElement(params) {

        const element = this.generateElementByTag(params);
        const childElements = [];
        if ('children' in params) {
            params.children.forEach((objChild, keyChild) => {
                childElements[keyChild] = this.generateElementByTag(objChild);
                if ('children' in objChild) {
                    childElements[keyChild].append(this.generateElementByTag(objChild['children']));
                }
            })
        }
        if (childElements.length) {
            childElements.forEach((child) => {
                element.append(child)
            })
        }
        return element
    }

    generateElementByTag(params) {
        const element = document.createElement(params.tag);
        if (params.tag === 'input') {
            element.type = ('typeInput' in params) ? params.typeInput : 'text';
        }
        this.AVAILABLE_INPUT_PARAMS.forEach((param) => {
            if (param in params) {
                element[param] = params[param];
            }
        })

        if ('attributes' in params) {
            const attributes = params['attributes'];
            attributes.forEach(obj => {
                for (const key in obj) {
                    element.setAttribute(key, obj[key]);
                }
            });
        }
        if ('class' in params) {
            const parts = params.class.split(',')
            element.classList.add(...parts);
        }

        return element
    }

    generateLabelByName(name) {
        return name
            .replace(/_/g, ' ')
            .replace(/\b\w/g, c => c.toUpperCase());
    }
}
