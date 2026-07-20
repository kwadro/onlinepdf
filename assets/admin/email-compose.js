import tinymce from 'tinymce';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/code';

const COMPOSE_EDITOR_ID = 'email-compose-body';

function initComposeEditor() {
    const textarea = document.getElementById(COMPOSE_EDITOR_ID);
    if (!textarea) {
        return Promise.resolve(null);
    }

    const existingEditor = tinymce.get(COMPOSE_EDITOR_ID);
    if (existingEditor) {
        return Promise.resolve(existingEditor);
    }

    return tinymce.init({
        selector: `#${COMPOSE_EDITOR_ID}`,
        height: 220,
        menubar: false,
        statusbar: false,
        branding: false,
        license_key: 'gpl',
        base_url: '/build/tinymce',
        suffix: '.min',
        plugins: 'link lists code',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat',
        verify_html: false,
        valid_elements: '*[*]',
        extended_valid_elements: 'style,link[href|rel|type],meta[*],head[*],body[*],html[*],blockquote[*],div[*],p[*],span[*]',
        convert_urls: false,
        content_style: 'body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; margin: 12px 16px; color: #202124; min-height: 120px; } p { margin: 0 0 8px; }',
        setup(editor) {
            editor.on('init', () => {
                textarea.classList.add('tinymce-initialized');
            });
        },
    }).then((editors) => editors[0] ?? null);
}

function destroyComposeEditor() {
    const editor = tinymce.get(COMPOSE_EDITOR_ID);
    if (editor) {
        editor.remove();
    }

    const textarea = document.getElementById(COMPOSE_EDITOR_ID);
    textarea?.classList.remove('tinymce-initialized');
}

function setComposeContent(html) {
    const editor = tinymce.get(COMPOSE_EDITOR_ID);
    if (editor) {
        editor.setContent(html);
        return;
    }

    const textarea = document.getElementById(COMPOSE_EDITOR_ID);
    if (textarea) {
        textarea.value = html;
    }
}

function getComposeContent() {
    const editor = tinymce.get(COMPOSE_EDITOR_ID);
    return editor ? editor.getContent() : (document.getElementById(COMPOSE_EDITOR_ID)?.value ?? '');
}

export function initEmailCompose() {
    const configElement = document.getElementById('email-compose-config');
    const composeRoot = document.getElementById('email-compose');
    if (!configElement || !composeRoot) {
        return;
    }

    const config = JSON.parse(configElement.textContent);
    const form = composeRoot.querySelector('[data-compose-form]');
    const recipientText = composeRoot.querySelector('[data-compose-recipient-text]');
    const toInput = composeRoot.querySelector('[data-compose-to]');
    const subjectRow = composeRoot.querySelector('[data-compose-subject-row]');
    const subjectInput = composeRoot.querySelector('[data-compose-subject]');
    const sendButton = composeRoot.querySelector('[data-compose-send]');
    const statusElement = composeRoot.querySelector('[data-compose-status]');
    const quoteToggle = composeRoot.querySelector('[data-compose-quote-toggle]');
    const quoteElement = composeRoot.querySelector('[data-compose-quote]');
    const formatButton = composeRoot.querySelector('[data-compose-format]');
    const linkButton = composeRoot.querySelector('[data-compose-link]');
    const replyIcon = composeRoot.querySelector('[data-compose-mode-icon-reply]');
    const forwardIcon = composeRoot.querySelector('[data-compose-mode-icon-forward]');

    let currentMode = 'reply';
    let currentQuoteHtml = '';

    const setQuoteVisible = (visible) => {
        quoteElement.hidden = !visible;
        quoteToggle.hidden = false;
        quoteToggle.setAttribute('aria-expanded', visible ? 'true' : 'false');
        quoteToggle.classList.toggle('email-compose__quote-toggle--active', visible);
    };

    const resetQuote = (quoteHtml) => {
        currentQuoteHtml = quoteHtml;
        quoteElement.innerHTML = quoteHtml;
        setQuoteVisible(false);
    };

    const applyModeLayout = (mode) => {
        currentMode = mode;
        composeRoot.classList.toggle('email-compose--forward', mode === 'forward');
        composeRoot.classList.remove('email-compose--format-open');

        replyIcon.hidden = mode !== 'reply';
        forwardIcon.hidden = mode !== 'forward';

        if (mode === 'forward') {
            recipientText.hidden = true;
            toInput.hidden = false;
            toInput.value = '';
            subjectRow.hidden = false;
            subjectInput.value = config.forwardSubject;
        } else {
            recipientText.hidden = false;
            recipientText.textContent = config.replyToDisplay ?? config.replyTo ?? '';
            toInput.hidden = true;
            toInput.value = config.replyTo ?? '';
            subjectRow.hidden = true;
            subjectInput.value = config.replySubject;
        }
    };

    const openCompose = async (mode) => {
        if (mode === 'reply' && !config.replyTo) {
            return;
        }

        composeRoot.hidden = false;
        applyModeLayout(mode);
        resetQuote(mode === 'forward' ? config.forwardQuoteHtml : config.replyQuoteHtml);

        await initComposeEditor();
        setComposeContent('<p><br></p>');

        statusElement.textContent = '';
        statusElement.className = 'email-compose__status';

        composeRoot.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        const editor = tinymce.get(COMPOSE_EDITOR_ID);
        editor?.focus();
    };

    const closeCompose = () => {
        composeRoot.hidden = true;
        composeRoot.classList.remove('email-compose--format-open', 'email-compose--forward');
        destroyComposeEditor();
        form.reset();
        quoteElement.innerHTML = '';
        quoteToggle.hidden = true;
        statusElement.textContent = '';
        statusElement.className = 'email-compose__status';
    };

    document.querySelectorAll('[data-compose-open]').forEach((button) => {
        button.addEventListener('click', () => {
            openCompose(button.getAttribute('data-compose-open'));
        });
    });

    composeRoot.querySelector('[data-compose-discard]')?.addEventListener('click', closeCompose);

    quoteToggle.addEventListener('click', () => {
        setQuoteVisible(quoteElement.hidden);
    });

    formatButton.addEventListener('click', () => {
        composeRoot.classList.toggle('email-compose--format-open');
    });

    linkButton.addEventListener('click', () => {
        tinymce.get(COMPOSE_EDITOR_ID)?.execCommand('mceLink');
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const to = toInput.value.trim();
        const subject = subjectInput.value.trim();
        const body = `${getComposeContent()}${currentQuoteHtml}`;

        sendButton.disabled = true;
        statusElement.textContent = config.labels.sending;
        statusElement.className = 'email-compose__status email-compose__status--info';

        const payload = new FormData();
        payload.append('_token', config.csrfToken);
        payload.append('to', to);
        payload.append('subject', subject);
        payload.append('body', body);
        payload.append('type', currentMode);

        try {
            const response = await fetch(config.replyUrl, {
                method: 'POST',
                body: payload,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message ?? config.labels.sendError);
            }

            statusElement.textContent = result.message ?? config.labels.sendSuccess;
            statusElement.className = 'email-compose__status email-compose__status--success';

            window.setTimeout(closeCompose, 1200);
        } catch (error) {
            statusElement.textContent = error.message ?? config.labels.sendError;
            statusElement.className = 'email-compose__status email-compose__status--error';
        } finally {
            sendButton.disabled = false;
        }
    });
}
