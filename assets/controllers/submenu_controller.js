import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        console.log('submenu_controller.js start')
        const rightBlocks = document.querySelectorAll('.mega-right-block');
        const leftItems = this.element.querySelectorAll('.mega-left-item')
        let lastTap = null;
        if (leftItems) {
            leftItems.forEach(item => {
                item.addEventListener('mouseenter', function () {
                    if (window.innerWidth >= 768) {
                        activateItem(item);
                    }
                });
                item.addEventListener('click', function (e) {
                    if (window.innerWidth < 768) {
                        const now = Date.now();
                        const DOUBLE_TAP_DELAY = 400;
                        if (lastTap && lastTap.item === item && now - lastTap.time < DOUBLE_TAP_DELAY) {
                            const link = item.querySelector('a');
                            if (link && link.href) {
                                window.location.href = link.href;
                            }
                            lastTap = null;
                        }else{
                            e.preventDefault(); // блокуємо перехід по href
                            activateItem(item);
                            lastTap = { item, time: now };
                        }
                    }
                });
            });
        }
        function activateItem(item) {
            leftItems.forEach(i => i.classList.remove('active'));
            rightBlocks.forEach(b => b.classList.add('d-none'));

            item.classList.add('active');
            const target = document.getElementById(item.dataset.target);
            if (target) target.classList.remove('d-none');
        }
    }
}
