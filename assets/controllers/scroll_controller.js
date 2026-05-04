import {Controller} from "@hotwired/stimulus";
import {EditorClass} from '../js/editor';
export default class extends Controller {
    static targets = ['textareaTitle', 'countTitle', 'countSpanTitle', 'countWarningTitle']
    static values = {
        maxLengthTitle: Number
    }
    connect() {

        this.editor = new EditorClass();
        this.updateRightContent = this.updateRightContent.bind(this);
        requestAnimationFrame(() => {
            this.measure();
            this.addListeners();
        });

    }
    measure() {
        this.rightContent = document.getElementById('rightContent');

        if (!this.rightContent) return;

        this.heightContent = this.rightContent.offsetHeight;

        console.log('heightContent', this.heightContent);

        document.body.style.height = (this.heightContent + 220) + 'px';
    }

    updateRightContent() {
        const scrollY = window.scrollY;
        requestAnimationFrame(() => {
            this.rightContent.style.transform = `translateY(-${scrollY}px)`;
        });
    }

    addListeners() {
        const self = this
        window.addEventListener('scroll', this.updateRightContent);
        window.addEventListener('resize', this.updateRightContent);
    }

    disconnect() {
        const self = this
        window.removeEventListener('scroll', this.updateRightContent);
        window.removeEventListener('resize', this.updateRightContent);
    }
    addRecipe(e){

        window.location.href = event.currentTarget.getAttribute('data-add-url');
        this.updateRightContent()
    }
    addFavoriteItem(e){
        e.preventDefault()
        console.log('click add favorite')
        const buttonElement = e.currentTarget;
        let ajaxUrl = null;
        if (buttonElement.classList.contains('selected')) {
             ajaxUrl = buttonElement.dataset.remove
        }else{
            ajaxUrl = buttonElement.dataset.add;
        }
        console.log('ajaxUrl ',ajaxUrl)
        const formData = new FormData();
        formData.append('id', buttonElement.dataset.id);


        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        })
            .then(response => response.json())
            .then(json => {
                if (json.errors) {
                    console.log('errors', json.errors)
                } else {
                    if (json.success) {
                        buttonElement.classList.toggle('selected');
                    }else{
                        console.log('error message : ', json.message)
                    }
                }
            })
            .catch(error => {
                console.error('Submit error:', error)
            })
    }

}
