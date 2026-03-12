import { Controller } from '@hotwired/stimulus';
import { startRegistration, startAuthentication } from '@simplewebauthn/browser';
import { generateCsrfHeaders } from './csrf_protection_controller.js';

export default class extends Controller {
    static values = {
        optionsUrl: String,
        resultUrl: String,
        isLogin: Boolean
    }

    connect() {
        console.log('Passkey controller connected! 🔑');
    }

    async submit(event) {
        event.preventDefault();
        
        const username = this.element.querySelector('[name="username"]')?.value;

        if (!this.isLoginValue && !username) {
            alert('Please provide a username/email');
            return;
        }

        const csrfHeaders = generateCsrfHeaders(this.element);

        try {
            // 1. Fetch options
            const response = await fetch(this.optionsUrlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', ...csrfHeaders },
                body: username ? JSON.stringify({ username: username, displayName: username }) : '{}'
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.errorMessage || 'Failed to fetch WebAuthn options from server');
            }
            
            const options = await response.json();

            // 2. Trigger Apple's Passkey UI (Create or Get)
            let credential;
            if (this.isLoginValue) {
                credential = await startAuthentication({ optionsJSON: options });
            } else {
                credential = await startRegistration({ optionsJSON: options });
            }

            // 3. Send result back to verify
            const result = await fetch(this.resultUrlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', ...csrfHeaders },
                body: JSON.stringify(credential)
            });

            if (result.ok) {
                window.location.reload();
            } else {
                const errorText = await result.text();
                alert('Authentication failed: ' + errorText);
            }
        } catch (e) {
            console.error(e);
            alert('WebAuthn process failed: ' + e.message);
        }
    }
}
