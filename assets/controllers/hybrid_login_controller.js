import { Controller } from '@hotwired/stimulus';
import { performWebAuthn, supportsAutofill } from './webauthn_service.js';

export default class extends Controller {
    static values = {
        optionsUrl: String,
        resultUrl: String,
        flowUrl: String
    }

    static targets = ["email", "passwordContainer", "password", "continueButton", "loginButton", "passwordFallback"]

    async connect() {
        if (await supportsAutofill()) {
            try {
                // Trigger autofill flow. Pass null for username as it's a discoverable credentials request.
                // We set useAutofill = true.
                await performWebAuthn(this.element, this.optionsUrlValue, this.resultUrlValue, true, null, true);
            } catch (e) {
                // Silence common abort errors that happen when user ignores the autofill prompt
                if (e.name !== 'NotAllowedError' && e.name !== 'AbortError' && e.name !== 'CanceledError') {
                    console.warn('WebAuthn Autofill issue:', e);
                }
            }
        }
    }

    async submit(event) {
        event.preventDefault();
        
        const email = this.emailTarget.value;
        if (!email) {
            alert('Please provide an email');
            return;
        }

        // If password container is visible, submit the form normally for legacy login
        if (!this.passwordContainerTarget.classList.contains('d-none')) {
            this.element.submit();
            return;
        }

        try {
            // Determine auth flow (Passkey vs Password)
            const response = await fetch(`${this.flowUrlValue}?email=${encodeURIComponent(email)}`);
            const data = await response.json();

            if (data.flow === 'passkey') {
                this.passwordFallbackTarget.classList.remove('d-none');
                try {
                    await performWebAuthn(this.element, this.optionsUrlValue, this.resultUrlValue, true, email);
                } catch (e) {
                    console.warn('WebAuthn failed or canceled:', e);
                    // If it was just a cancellation, don't alert, just let user use password fallback
                    if (e.name !== 'NotAllowedError' && e.name !== 'AbortError') {
                        alert('Passkey login failed: ' + e.message);
                    }
                    this.showPasswordInput();
                }
            } else {
                this.showPasswordInput();
            }
        } catch (e) {
            console.error(e);
            alert('Failed to determine auth flow: ' + e.message);
        }
    }

    showPasswordInput(event) {
        if (event) event.preventDefault();
        this.passwordContainerTarget.classList.remove('d-none');
        this.passwordTarget.setAttribute('required', 'required');
        this.continueButtonTarget.classList.add('d-none');
        this.loginButtonTarget.classList.remove('d-none');
        this.passwordFallbackTarget.classList.add('d-none');
        this.passwordTarget.focus();
    }
}
