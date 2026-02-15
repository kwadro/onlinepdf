import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        console.log('sidebar_controller.js start')
    }
    open(){
        console.log('sidebar_controller.js open')
    }
}
