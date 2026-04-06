import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    debounce(fn, delay = 300) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(this, args), delay);
        };
    }
    connect() {

    }
    connectOld() {
        const input = document.getElementById('search-input');
        const searchFormToken = document.getElementById('search_form').value;
        const gridElement = document.querySelector('.product-grid');
        const baseSearchAjaxUrl = this.element.dataset.ajaxurl;

        console.log('search_controller.js baseSearchAjaxUrl ',baseSearchAjaxUrl)

        const search = this.debounce(function () {
            const query = this.value.trim();
            console.log('query',query)

            if (query.length < 4) {
                gridElement.innerHTML = '........';
                return;
            }
            const data = new FormData();
            data.append('q', query);
            data.append('_token', searchFormToken);

            fetch(baseSearchAjaxUrl, {
                method: 'POST',
                body: data,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(json => {
                    if (json.errors) {
                        console.log('errors', json.errors)
                    } else {
                        if(gridElement){
                            gridElement.innerHTML = json.gridHtml;
                        }
                        const selectedFilterElement = document.querySelector('.selected-filter');
                        if(selectedFilterElement){
                            selectedFilterElement.innerHTML = json.filterHtml;
                        }
                    }
                })
                .catch(error => {
                    console.error('Submit error:', error)
                })
        }, 300);
        input.addEventListener('input', search)
    }
    open(){
        const input = document.getElementById('search-input');
        const redirectUrl = input.dataset.baseurl.replace('/keyword', `/${input.value}`);
        console.log('redirect url ', redirectUrl)
        window.location.href = redirectUrl;
    }
}
