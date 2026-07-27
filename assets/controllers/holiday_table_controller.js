import { Controller } from '@hotwired/stimulus';
import { requestLayoutRemeasure } from '../js/layout-chrome';
import { generateCsrfToken } from './csrf_protection_controller';

export default class extends Controller {
    static targets = [
        'form',
        'formPanel',
        'eventPicker',
        'eventSearch',
        'eventList',
        'formMode',
        'holidayTableId',
        'eventName',
        'eventDate',
        'results',
        'tbody',
        'meta',
        'error',
        'submit',
        'men',
        'women',
        'guestTotal',
        'recipesSelect',
        'selectedList',
        'recipesEmpty',
        'recipeSearch',
        'recipeDropdown',
        'addButton',
        'saveButton',
        'success',
    ];
    static values = {
        calculateUrl: String,
        saveUrl: String,
        loadEventUrl: String,
        requiresEventSelection: Boolean,
        savedEvents: Array,
        recipeOptions: Array,
        labelRemove: String,
        labelEmpty: String,
        labelNoResults: String,
        labelSaved: String,
        labelResultsMeta: String,
        labelEventsNoResults: String,
        labelLoadingEvent: String,
        labelFormModeEdit: { type: String, default: 'Edit event' },
        labelFormModeCreate: { type: String, default: 'New event' },
        labelEventsMeta: { type: String, default: '%date% · %guests% guests · %recipes% dishes' },
    };

    connect() {
        this.selectedIds = new Set();
        this.highlightedIndex = -1;
        this.savedEventsList = [...(this.savedEventsValue || [])];

        if (this.requiresEventSelectionValue) {
            this.renderEventList();
            this.showPickerPanel();
        } else {
            this.showFormPanel();
        }

        this.updateGuestTotal();
        this.renderSelected();
        this.renderDropdown();
    }

    filterEvents() {
        this.renderEventList();
    }

    renderEventList() {
        if (!this.hasEventListTarget) {
            return;
        }

        const query = this.hasEventSearchTarget
            ? this.eventSearchTarget.value.trim().toLowerCase()
            : '';

        const events = this.savedEventsList.filter((event) => {
            if (query === '') {
                return true;
            }

            const haystack = [
                event.name,
                event.date,
                String(event.guests),
            ].join(' ').toLowerCase();

            return haystack.includes(query);
        });

        this.eventListTarget.innerHTML = '';

        if (events.length === 0) {
            const emptyItem = document.createElement('li');
            emptyItem.className = 'holiday-table-event-list__empty';
            emptyItem.textContent = this.labelEventsNoResultsValue;
            this.eventListTarget.appendChild(emptyItem);
            return;
        }

        events.forEach((event) => {
            const item = document.createElement('li');
            item.className = 'holiday-table-event-list__item';
            item.dataset.eventId = String(event.id);
            item.setAttribute('role', 'option');
            item.dataset.action = 'click->holiday-table#selectEvent';

            const meta = (this.labelEventsMetaValue || '%date% · %guests% guests · %recipes% dishes')
                .replace('%date%', event.date || '—')
                .replace('%guests%', String(event.guests ?? 0))
                .replace('%recipes%', String(event.recipes_count ?? 0));

            item.innerHTML = `
                <span class="holiday-table-event-list__name">${this.escapeHtml(event.name || `#${event.id}`)}</span>
                <span class="holiday-table-event-list__meta">${this.escapeHtml(meta)}</span>
            `;
            this.eventListTarget.appendChild(item);
        });
    }

    selectEvent(event) {
        const eventId = parseInt(event.currentTarget.dataset.eventId, 10);
        if (!Number.isFinite(eventId)) {
            return;
        }

        this.loadEvent(eventId);
    }

    createNewEvent() {
        this.resetFormData();
        this.setFormMode(this.labelFormModeCreateValue);
        this.showFormPanel();
    }

    backToPicker() {
        this.hideAlerts();
        if (this.hasResultsTarget) {
            this.resultsTarget.classList.add('d-none');
        }
        this.showPickerPanel();
    }

    loadEvent(eventId) {
        if (!this.loadEventUrlValue) {
            return;
        }

        const url = this.loadEventUrlValue.replace(/\/0$/, `/${eventId}`);
        this.hideAlerts();
        this.setLoading(true);

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'include',
        })
            .then(async (response) => {
                const payload = await response.json();
                if (!response.ok) {
                    throw payload;
                }
                return payload;
            })
            .then((payload) => {
                this.populateForm(payload);
                this.setFormMode(this.labelFormModeEditValue, payload.event_name);
                this.showFormPanel();
            })
            .catch((error) => {
                const messages = error?.errors ?? [error?.error ?? this.labelLoadingEventValue];
                this.showError(Array.isArray(messages) ? messages.join(' ') : String(messages));
            })
            .finally(() => {
                this.setLoading(false);
            });
    }

    populateForm(data) {
        if (this.hasHolidayTableIdTarget) {
            this.holidayTableIdTarget.value = data.id ? String(data.id) : '';
        }
        if (this.hasEventNameTarget) {
            this.eventNameTarget.value = data.event_name || '';
        }
        if (this.hasEventDateTarget) {
            this.eventDateTarget.value = data.event_date || '';
        }
        if (this.hasMenTarget) {
            this.menTarget.value = String(data.men_count ?? 0);
        }
        if (this.hasWomenTarget) {
            this.womenTarget.value = String(data.women_count ?? 0);
        }

        this.selectedIds = new Set((data.recipes || []).map((id) => parseInt(id, 10)).filter(Number.isFinite));
        this.syncHiddenSelect();
        this.updateGuestTotal();
        this.renderSelected();
        this.renderDropdown();
    }

    resetFormData() {
        this.populateForm({
            id: '',
            event_name: '',
            event_date: '',
            men_count: 5,
            women_count: 5,
            recipes: [],
        });
    }

    setFormMode(modeLabel, eventName = '') {
        if (!this.hasFormModeTarget) {
            return;
        }

        this.formModeTarget.textContent = eventName
            ? `${modeLabel}: ${eventName}`
            : modeLabel;
    }

    showPickerPanel() {
        if (this.hasEventPickerTarget) {
            this.eventPickerTarget.classList.remove('d-none');
        }
        if (this.hasFormPanelTarget) {
            this.formPanelTarget.classList.add('d-none');
        }
        this.syncLayoutHeight();
    }

    showFormPanel() {
        if (this.hasEventPickerTarget) {
            this.eventPickerTarget.classList.add('d-none');
        }
        if (this.hasFormPanelTarget) {
            this.formPanelTarget.classList.remove('d-none');
        }
        this.syncLayoutHeight(true);
    }

    syncLayoutHeight(resetScroll = false) {
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                requestLayoutRemeasure();
                if (resetScroll) {
                    window.scrollTo({ top: 0, behavior: 'auto' });
                }
            });
        });
    }

    updateGuestTotal() {
        if (!this.hasGuestTotalTarget) {
            return;
        }

        const men = this.hasMenTarget ? parseInt(this.menTarget.value, 10) || 0 : 0;
        const women = this.hasWomenTarget ? parseInt(this.womenTarget.value, 10) || 0 : 0;
        this.guestTotalTarget.textContent = String(men + women);
    }

    filterRecipes() {
        this.highlightedIndex = 0;
        this.renderDropdown();
    }

    openDropdown() {
        this.renderDropdown();
    }

    addHighlightedRecipe() {
        const available = this.getAvailableRecipes();
        if (available.length === 0) {
            return;
        }

        const recipe = available[this.highlightedIndex] ?? available[0];
        this.addRecipeById(recipe.id);
    }

    addRecipe(event) {
        const recipeId = parseInt(event.currentTarget.dataset.recipeId, 10);
        if (!Number.isFinite(recipeId)) {
            return;
        }

        this.addRecipeById(recipeId);
    }

    removeRecipe(event) {
        const recipeId = parseInt(event.currentTarget.dataset.recipeId, 10);
        if (!Number.isFinite(recipeId)) {
            return;
        }

        this.selectedIds.delete(recipeId);
        this.syncHiddenSelect();
        this.renderSelected();
        this.renderDropdown();
    }

    addRecipeById(recipeId) {
        if (this.selectedIds.has(recipeId)) {
            return;
        }

        this.selectedIds.add(recipeId);
        this.syncHiddenSelect();
        this.renderSelected();
        this.renderDropdown();

        if (this.hasRecipeSearchTarget) {
            this.recipeSearchTarget.value = '';
            this.highlightedIndex = 0;
            this.renderDropdown();
            this.recipeSearchTarget.focus();
        }
    }

    getAvailableRecipes() {
        const query = this.hasRecipeSearchTarget
            ? this.recipeSearchTarget.value.trim().toLowerCase()
            : '';

        return (this.recipeOptionsValue || []).filter((recipe) => {
            if (this.selectedIds.has(recipe.id)) {
                return false;
            }

            if (query === '') {
                return true;
            }

            return recipe.name.toLowerCase().includes(query);
        });
    }

    renderSelected() {
        if (!this.hasSelectedListTarget) {
            return;
        }

        this.selectedListTarget.innerHTML = '';
        const selectedRecipes = (this.recipeOptionsValue || []).filter((recipe) =>
            this.selectedIds.has(recipe.id),
        );

        selectedRecipes.forEach((recipe) => {
            const item = document.createElement('li');
            item.className = 'holiday-table-recipes-selected-item';
            item.dataset.recipeId = String(recipe.id);
            item.innerHTML = `
                <span class="holiday-table-recipes-selected-item__name">${this.escapeHtml(recipe.name)}</span>
                <button
                    type="button"
                    class="holiday-table-recipes-selected-item__remove"
                    data-recipe-id="${recipe.id}"
                    data-action="holiday-table#removeRecipe"
                    aria-label="${this.escapeHtml(this.labelRemoveValue)}"
                >
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            `;
            this.selectedListTarget.appendChild(item);
        });

        if (this.hasRecipesEmptyTarget) {
            this.recipesEmptyTarget.classList.toggle('d-none', selectedRecipes.length > 0);
        }
    }

    renderDropdown() {
        if (!this.hasRecipeDropdownTarget) {
            return;
        }

        const available = this.getAvailableRecipes();
        this.recipeDropdownTarget.innerHTML = '';

        if (available.length === 0) {
            const emptyItem = document.createElement('li');
            emptyItem.className = 'holiday-table-recipes-dropdown__empty';
            emptyItem.textContent = this.labelNoResultsValue;
            this.recipeDropdownTarget.appendChild(emptyItem);
            this.highlightedIndex = -1;
            return;
        }

        if (this.highlightedIndex < 0 || this.highlightedIndex >= available.length) {
            this.highlightedIndex = 0;
        }

        available.forEach((recipe, index) => {
            const item = document.createElement('li');
            item.className = 'holiday-table-recipes-dropdown__item';
            item.dataset.recipeId = String(recipe.id);
            item.setAttribute('role', 'option');
            item.textContent = recipe.name;
            item.dataset.action = 'click->holiday-table#addRecipe';

            if (index === this.highlightedIndex) {
                item.classList.add('is-highlighted');
                item.setAttribute('aria-selected', 'true');
            }

            this.recipeDropdownTarget.appendChild(item);
        });
    }

    syncHiddenSelect() {
        if (!this.hasRecipesSelectTarget) {
            return;
        }

        Array.from(this.recipesSelectTarget.options).forEach((option) => {
            option.selected = this.selectedIds.has(parseInt(option.value, 10));
        });
    }

    buildFormData() {
        this.syncHiddenSelect();
        generateCsrfToken(this.formTarget);

        const formData = new FormData(this.formTarget);
        const fieldName = this.hasRecipesSelectTarget
            ? this.recipesSelectTarget.name
            : 'holiday_table_form[recipes][]';

        for (const key of new Set(Array.from(formData.keys()))) {
            if (key === fieldName || key.startsWith('holiday_table_form[recipes]')) {
                while (formData.has(key)) {
                    formData.delete(key);
                }
            }
        }

        this.selectedIds.forEach((id) => {
            formData.append(fieldName, String(id));
        });

        return formData;
    }

    parseResponsePayload(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return response.json();
        }

        return response.text().then((text) => {
            throw { error: text || 'Unexpected server response' };
        });
    }

    calculate(event) {
        event.preventDefault();
        this.submitForm(this.calculateUrlValue, () => {
            this.renderResults(this.lastPayload);
        });
    }

    save(event) {
        event.preventDefault();

        if (!this.saveUrlValue) {
            return;
        }

        this.submitForm(this.saveUrlValue, (payload) => {
            this.showSuccess(payload.message || this.labelSavedValue);
            if (payload.event) {
                this.upsertSavedEvent(payload.event);
                if (this.hasHolidayTableIdTarget && payload.id) {
                    this.holidayTableIdTarget.value = String(payload.id);
                }
            }
        });
    }

    upsertSavedEvent(eventData) {
        const summary = {
            id: eventData.id,
            name: eventData.event_name,
            date: eventData.event_date,
            guests: eventData.guest_count,
            recipes_count: (eventData.recipes || []).length,
        };

        const index = this.savedEventsList.findIndex((event) => event.id === summary.id);
        if (index >= 0) {
            this.savedEventsList[index] = summary;
        } else {
            this.savedEventsList.unshift(summary);
        }

        this.renderEventList();
    }

    submitForm(url, onSuccess) {
        if (!url) {
            this.showError('Request failed');
            return;
        }

        if (this.selectedIds.size === 0) {
            this.showError(this.labelEmptyValue);
            return;
        }

        const formData = this.buildFormData();

        this.hideAlerts();
        this.setLoading(true);

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        })
            .then(async (response) => {
                const payload = await this.parseResponsePayload(response);
                if (!response.ok) {
                    throw payload;
                }
                return payload;
            })
            .then((payload) => {
                this.lastPayload = payload;
                onSuccess(payload);
            })
            .catch((error) => {
                const messages = error?.errors ?? [error?.error ?? 'Request failed'];
                this.showError(Array.isArray(messages) ? messages.join(' ') : String(messages));
            })
            .finally(() => {
                this.setLoading(false);
            });
    }

    renderResults(payload) {
        this.tbodyTarget.innerHTML = '';

        if (!payload.items || payload.items.length === 0) {
            this.showError('No products found for selected recipes.');
            this.resultsTarget.classList.add('d-none');
            return;
        }

        payload.items.forEach((item) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${this.escapeHtml(item.ingredient)}</td>
                <td>${item.quantity}</td>
                <td>${this.escapeHtml(item.unit)}</td>
                <td>${this.escapeHtml((item.recipes || []).join(', '))}</td>
            `;
            this.tbodyTarget.appendChild(row);
        });

        const guestCount = payload.guest_count ?? '';
        const portions = payload.effective_portions ?? '';
        const recipesCount = (payload.recipes || []).length;
        this.metaTarget.textContent = (this.labelResultsMetaValue || 'Guests: %guests% · Portions: %portions% · Dishes: %recipes%')
            .replace('%guests%', guestCount)
            .replace('%portions%', portions)
            .replace('%recipes%', recipesCount);
        this.resultsTarget.classList.remove('d-none');
        this.syncLayoutHeight();
        this.resultsTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    showSuccess(message) {
        if (!this.hasSuccessTarget) {
            return;
        }

        this.successTarget.textContent = message;
        this.successTarget.classList.remove('d-none');
    }

    hideAlerts() {
        this.hideError();
        if (this.hasSuccessTarget) {
            this.successTarget.textContent = '';
            this.successTarget.classList.add('d-none');
        }
    }

    showError(message) {
        if (!this.hasErrorTarget) {
            return;
        }

        this.errorTarget.textContent = message;
        this.errorTarget.classList.remove('d-none');
    }

    hideError() {
        if (!this.hasErrorTarget) {
            return;
        }

        this.errorTarget.textContent = '';
        this.errorTarget.classList.add('d-none');
    }

    setLoading(isLoading) {
        if (this.hasSubmitTarget) {
            this.submitTarget.disabled = isLoading;
        }
        if (this.hasSaveButtonTarget) {
            this.saveButtonTarget.disabled = isLoading;
        }
    }

    escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
}
