import {Controller} from "@hotwired/stimulus";
import {EditorClass} from '../js/editor';

const  AVAILABLE_FIELDS= ['desc','title','serving','timecook','timeprepare'];
function loadTargets(){
    let values =['textarea'];

    AVAILABLE_FIELDS.forEach((field)=>{
        values.push(`count${field}`)
        values.push(`countSpan${field}`)
        values.push(`countWarning${field}`)
    })
    console.log('values loadTargets : ', values)
    return values;
}
function loadValues(){
    let values = {};
    AVAILABLE_FIELDS.forEach((field)=>{
        values[`maxLength${ucFirst(field)}`] = String
    })
    console.log('values Values : ', values)
    return values;
}
function loadCountWarning(){
    let values = {};
    AVAILABLE_FIELDS.forEach((field)=>{
        values[ucFirst(field)] = false;
    })
    console.log('values CountWarning : ', values)
    return values;
}
function ucFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

export default class extends Controller {
    static targets = loadTargets()
    static values = loadValues()
    showCountWarning= loadCountWarning();
    connect() {
        console.log('showCountWarning : ', this.showCountWarning)

        this.editor = new EditorClass();
        requestAnimationFrame(() => {
            this.connectAutoResize();
        });

    }
    onTextareaFocus(){
        console.log('onFocus')
        const currentElement = event.target;
        const fieldName = currentElement.dataset.fieldname;
        const countSpanElement = this[`countSpan${fieldName}Target`];
        const countWarningElement = this[`countWarning${fieldName}Target`];

        this.editor.showElement(countSpanElement)

        if (this.showCountWarning[fieldName]) {
            this.editor.showElement(countWarningElement)
            currentElement.classList.add('has-warning');
        }else{
            currentElement.classList.remove('has-warning');
        }
    }
    onTextareaBlur(event){
        console.log('onBlur')
        const currentElement = event.target;
        const fieldName = currentElement.dataset.fieldname;

        currentElement.classList.remove('is-focused');
        const countSpanElement = this[`countSpan${fieldName}Target`];
        const countWarningElement = this[`countWarning${fieldName}Target`];
        this.editor.hideElement(countSpanElement)
        this.editor.hideElement(countWarningElement)
    }

    connectAutoResize() {

            const styleProps = ['placeholder','width', 'padding', 'fontSize', 'fontFamily', 'lineHeight', 'border', 'boxSizing'];
            this.mirror = {}
            this.textareaTargets.forEach(el => {
                const fieldName = el.dataset.fieldname;
                this.mirror[fieldName] = document.createElement('textarea');
                const container  = document.getElementById(`container-recipe-${el.dataset.fieldname}`);
                if(container){
                    container.appendChild(this.mirror[fieldName]);
                    this.mirror[fieldName].style.position = 'absolute';
                    this.mirror[fieldName].style.visibility = 'hidden';
                    this.mirror[fieldName].style.top = '0';
                    this.mirror[fieldName].style.left = '0';
                    this.mirror[fieldName].style.height = '0';
                    this.mirror[fieldName].style.overflow = 'hidden';
                    this.mirror[fieldName].style.whiteSpace = 'pre-wrap';
                    this.mirror[fieldName].style.wordWrap = 'break-word';
                    this.mirror[fieldName].style.letterSpacing = '-0.025em';
                    styleProps.forEach(prop => {
                        this.mirror[fieldName].style[prop] = getComputedStyle(el)[prop];
                    });
                }

                el.addEventListener('input', this.onChange.bind(this));
                el.addEventListener('paste', this.onChange.bind(this));
                el.addEventListener('cut', this.onChange.bind(this));
                el.addEventListener('drop', this.onChange.bind(this));
                this.autoResizeElement(el);
            });
    }

    onChange(event) {
        const el = event.target;
        this.autoResizeElement(el);
        if (el.value === '') {
            console.log('Textarea порожня!');
        } else {
            console.log('Текст змінено:', el.value);
        }
    }
    autoResize(event) {
        const el = event ? event.target : null;
        if (el) this.autoResizeElement(el);
    }

    autoResizeElement(el) {
        this.fieldName = el.dataset.fieldname;
        console.log('fieldName : ', this.fieldName)

        this.mirror[this.fieldName].value = el.value  || el.placeholder|| ' ';
        console.log('value : ',this.mirror[this.fieldName].value)


        const lineHeight = parseInt(getComputedStyle(el).lineHeight);
        const paddingTop = parseInt(getComputedStyle(el).paddingTop);
        const paddingBottom = parseInt(getComputedStyle(el).paddingBottom);
        const minHeight = lineHeight + paddingTop + paddingBottom;
        const countSymbol = this.mirror[this.fieldName].value.trim().length||0;

        el.style.height = 'auto';
        let newHeight = Math.max(this.mirror[this.fieldName].scrollHeight, minHeight) ;
        if(countSymbol>100){
            // newHeight = newHeight * 2
        }
        console.log('newHeight auto : ',newHeight)

        const maxLength = this[`maxLength${ucFirst(this.fieldName)}Value`];
        const countWarningElement = this[`countWarning${this.fieldName}Target`];
        const countSpanElement = this[`countSpan${this.fieldName}Target`];
        const countElement = this[`count${this.fieldName}Target`];

        if(countWarningElement) {
            this.showCountWarning[this.fieldName] = countSymbol > maxLength;
            console.log('countSymbol : ', countSymbol)
            console.log('maxLengthValue : ', maxLength)
            console.log('maxLengthTitleValue : ', this.maxLengthTitleValue)
            console.log('maxLengthDescValue : ', this.maxLengthDescValue)
            console.log('showCountWarning : ', this.showCountWarning[this.fieldName])
            if (this.showCountWarning[this.fieldName]) {
               countWarningElement.innerText = 'Field is to large'
               this.editor.showElement(countWarningElement)
               el.classList.add('has-warning');
               countSpanElement.style.color = '#ff0000';
            } else {
               countWarningElement.innerText = '';
               this.editor.hideElement(countWarningElement)
               el.classList.remove('has-warning');
               countSpanElement.style.color = '#555555';
            }
        }
        el.style.height = newHeight + 'px';
        if(countElement) {
            countElement.style.marginTop = (newHeight + 3) + 'px';
        }
        if(countSpanElement){
            countSpanElement.innerText = countSymbol + '/'+ maxLength;
        }
    }


    disconnect() {
        const self = this

    }
}
