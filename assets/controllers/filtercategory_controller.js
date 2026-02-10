import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const self = this;
        const filterAjaxUrl = this.element.dataset.ajaxurl

        this.element.addEventListener('change', (e) => {
            if (e.target.dataset.level === '1') {
                const formCheckElement = e.target.closest('.form-check');
                const isChecked = e.target.checked;
                if (formCheckElement) {
                    const childElement = formCheckElement.querySelectorAll(formCheckElement.dataset.childid)[0];
                    if (childElement) {
                        childElement
                            .querySelectorAll('input')
                            .forEach(el => {
                                if (el.name !== 'category[]') return
                                if (el.type === 'checkbox') {
                                    el.checked = isChecked;
                                }
                            })
                    }
                    console.log('change element checked : ', e.target.checked)
                }
            }

            const data = new FormData()
            const filterCatIds = []
            const filterAuthorIds = []
            this.element
                .querySelectorAll('input')
                .forEach(el => {
                    if (el.type === 'hidden') {
                        data.append(el.name, el.value)
                    }
                    if (el.checked && el.name==='category[]') {
                        filterCatIds.push(el.id.replace('category-',''));
                    }
                    if (el.checked && el.name==='author[]') {
                        filterAuthorIds.push(el.id.replace('author-',''));
                    }
                })

            const filterCatIdsStr = filterCatIds.join(', ');
            data.append('category_ids', filterCatIdsStr)

            const filterAuthorIdsStr = filterAuthorIds.join(', ');
            data.append('author_ids', filterAuthorIdsStr)

            fetch(filterAjaxUrl, {
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
                        const gridElement = document.querySelector('.product-grid');
                        gridElement.innerHTML = json.gridHtml;
                        const selectedFilterElement = document.querySelector('.selected-filter');
                        selectedFilterElement.innerHTML = json.filterHtml;
                    }
                })
                .catch(error => {
                    console.error('Submit error:', error)
                })
        });
    }
}
