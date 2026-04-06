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

        document.body.style.height = (this.heightContent + 180) + 'px';
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
}
