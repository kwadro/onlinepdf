import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    closeCategory(event) {
        const container = event.target.closest('.category-badge');
        if(container){
            const categoryId = 'category-' + container.dataset.categoryid;
            this.closeElementById(container,categoryId);
        }
    }

    closeAuthor(event) {
        const container = event.target.closest('.category-badge');
        if(container){
            const authorId = 'author-' + container.dataset.authorid;
            this.closeElementById(container,authorId);
        }
    }

    closeElementById(container, id){
        const selectElement  = document.getElementById(`${id}`);
        console.log('closeItem selectElement',selectElement)
        if(selectElement){
            selectElement.checked = false;
            const event = new Event('change', { bubbles: true });
            selectElement.dispatchEvent(event);
            container.remove()
        }
    }

    connect() {
        console.log('connect filter_controller.js')
    }
}
