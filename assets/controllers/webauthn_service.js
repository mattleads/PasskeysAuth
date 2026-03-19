import { startRegistration, startAuthentication, browserSupportsWebAuthnAutofill } from '@simplewebauthn/browser';
import { generateCsrfHeaders } from './csrf_protection_controller.js';

/**
 * Common WebAuthn logic to reduce duplication across Stimulus controllers.
 */
export async function performWebAuthn(formElement, optionsUrl, resultUrl, isLogin, username = null, useAutofill = false) {
    const csrfHeaders = generateCsrfHeaders(formElement);

    // 1. Fetch options from server
    const body = username ? JSON.stringify({ username: username, displayName: username }) : '{}';
    const response = await fetch(optionsUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...csrfHeaders },
        body: body
    });
    
    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.errorMessage || `Failed to fetch WebAuthn ${isLogin ? 'login' : 'registration'} options`);
    }
    
    const options = await response.json();

    // 2. Trigger native browser API (biometrics/security key)
    let credential;
    if (isLogin) {
        credential = await startAuthentication({ 
            optionsJSON: options,
            useBrowserAutofill: useAutofill 
        });
    } else {
        credential = await startRegistration({ 
            optionsJSON: options 
        });
    }

    // 3. Send result back to server for verification
    const result = await fetch(resultUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...csrfHeaders },
        body: JSON.stringify(credential)
    });

    if (result.ok) {
        window.location.reload();
    } else {
        const errorText = await result.text();
        throw new Error(errorText || 'Verification failed on server');
    }
}

/**
 * Checks if the browser supports WebAuthn autofill (Conditional Mediation)
 */
export async function supportsAutofill() {
    return await browserSupportsWebAuthnAutofill();
}
