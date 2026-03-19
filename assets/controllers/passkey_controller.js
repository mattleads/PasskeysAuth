import { Controller } from '@hotwired/stimulus';
import { performWebAuthn } from './webauthn_service.js';

export default class extends Controller {
    static values = {
        optionsUrl: String,
        resultUrl: String,
        isLogin: Boolean
    }

    async submit(event) {
        event.preventDefault();
        
        const usernameInput = this.element.querySelector('[name="username"]');
        const username = usernameInput?.value;

        // On registration, we usually need a username UNLESS the user is already logged in
        // The backend handles the logged-in case implicitly if username is null.
        // We only show the alert if it's registration AND no username AND no hidden indicator that we are logged in (optional).
        // For simplicity, we'll let the request proceed; the backend will fail gracefully if it can't find a user.
        
        try {
            await performWebAuthn(
                this.element, 
                this.optionsUrlValue, 
                this.resultUrlValue, 
                this.isLoginValue, 
                username
            );
        } catch (e) {
            console.error(e);
            alert(e.message);
        }
    }
}
