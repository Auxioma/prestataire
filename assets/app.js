import './stimulus_bootstrap.js';
import * as bootstrap from 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import './styles/app.css';

window.bootstrap = bootstrap;

function initializeQuoteProposalDocumentMode(root = document) {
    const quoteProposalForms = root.querySelectorAll('[data-quote-proposal-form]');

    for (const form of quoteProposalForms) {
        if (form.dataset.quoteProposalFormInitialized === '1') {
            continue;
        }

        const externalPanel = form.querySelector('[data-quote-proposal-external-panel]');
        const nativeItemsSection = form.querySelector('[data-quote-proposal-native-items]');
        const modeInputs = form.querySelectorAll('input[name$="[documentMode]"]');

        if (!externalPanel || !nativeItemsSection || modeInputs.length === 0) {
            continue;
        }

        const refreshDocumentMode = () => {
            const checkedInput = form.querySelector('input[name$="[documentMode]"]:checked');
            const isExternalPdf = checkedInput?.value === 'external_pdf';

            externalPanel.hidden = !isExternalPdf;
            nativeItemsSection.hidden = isExternalPdf;
        };

        for (const input of modeInputs) {
            input.addEventListener('change', refreshDocumentMode);
        }

        form.dataset.quoteProposalFormInitialized = '1';
        refreshDocumentMode();
    }
}

document.addEventListener('DOMContentLoaded', () => initializeQuoteProposalDocumentMode());
document.addEventListener('turbo:load', () => initializeQuoteProposalDocumentMode());
