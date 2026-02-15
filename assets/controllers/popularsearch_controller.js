import {Controller} from '@hotwired/stimulus';

export default class extends Controller {

    connect() {
        console.log('popular search_controller.js start')
    }
    open(event){
        const el = event.currentTarget

        window.location.href = el.dataset.popularsearchQueryUrlValue;
    }
}
