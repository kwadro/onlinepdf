import { Controller } from "@hotwired/stimulus";

export default class extends Controller {

    connect() {
        console.log('image-cropper_controller.js connect')
    }
    loadImage() {
        console.log('image-cropper_controller.js loadImage')
    }

    disconnect() {
        console.log('image-cropper_controller.js disconnect')
    }
}
