# Symfony 7.4 Passkey & Hybrid Authentication Demo

This is a complete, working demonstration of implementing robust, passwordless Passkey (FIDO2/WebAuthn) and traditional password authentication in a modern **Symfony 7.4** application. It uses the industry-standard `web-auth/webauthn-symfony-bundle` alongside a custom **Hybrid Authenticator** and modern frontend tooling (Stimulus + AssetMapper) to provide a seamless, progressive user experience.

## ✨ Features

*   **Hybrid Authentication:** A single, intelligent login form. Enter an email, and the app automatically decides whether to trigger a Passkey prompt or reveal a password field.
*   **WebAuthn Autofill (Conditional Mediation):** Modern "Passkey Autofill" support. When a user focuses the email input, the browser automatically suggests their saved Passkeys for instant login.
*   **Progressive Passkey Adoption:** Users can register with a traditional password and later "upgrade" their account by adding one or more Passkeys from their dashboard.
*   **Modern Symfony Stack:** Built on Symfony 7.4 LTS with Doctrine ORM.
*   **Custom Authenticator:** Implements a clean `HybridAuthenticator` using Symfony's Passport system to handle legacy password logins alongside WebAuthn.
*   **Zero-Node Frontend:** Uses Symfony AssetMapper and Stimulus (`@hotwired/stimulus`) to handle the WebAuthn API natively on the client side without a `node_modules` folder.
*   **SOLID/DRY Architecture:** Business logic is decoupled into specialized controllers and services, with a shared `webauthn_service.js` for consistent frontend behavior.
*   **Biometric Support:** Works out-of-the-box with Apple Touch ID / Face ID, Windows Hello, Android Biometrics, and hardware security keys (like YubiKey).

## 🚀 Requirements

*   PHP 8.2 or higher
*   Composer
*   A local web server (or the Symfony CLI server)
*   *Note: Passkeys/WebAuthn require a secure context (HTTPS) to run in production. Browsers make an exception for `localhost` and `127.0.0.1` during development.*

## 📦 Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/mattleads/PasskeysAuth.git
   cd PasskeysAuth
   ```
   
2. **Install dependencies:**
   ```bash
   composer install
   ```
   
3. **Configure Environment Variables:**
   The WebAuthn bundle relies on specific `.env` variables to properly validate requests against your domain. For local development, they are defaulted to `localhost`:
   ```env
   RELYING_PARTY_ID=localhost
   RELYING_PARTY_NAME="My Application"
   WEBAUTHN_ALLOWED_ORIGINS=localhost
   ```

4. **Database Setup:**
   The project is pre-configured to use SQLite for easy setup.
   ```bash
   # Create the SQLite database and run migrations
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Start the local server:**
   ```bash
   # Using PHP built-in server
   php -S localhost:8000 -t public
   ```

6. **Access the application:**
   Open your browser and navigate to `http://localhost:8000`.

## 💻 Usage Examples

### 1. Hybrid Login & Autofill
1. **Scenario A (Autofill):** Click into the email input. If you have a registered Passkey, your browser will offer it as an autofill suggestion. Selecting it logs you in instantly.
2. **Scenario B (Manual):** Enter your email and click **Continue**.
   - If you have a Passkey, your browser will prompt for biometric authentication.
   - If you don't have a Passkey, a password field will appear.
3. **Fallback:** You can always click "Login with Password instead" to use your legacy credentials if biometrics are unavailable.

### 2. Traditional Password Registration
1. Click the link **"Or Register with a Traditional Password"** below the login form.
2. Enter your email and choose a password.
3. After registering, you will be redirected to the login page.

### 3. Adding a Passkey to a Password Account
1. Log in to your account using your email and password.
2. On the **Dashboard**, locate the **"Passkey Security"** section.
3. Click **"Add a Passkey to your Account"**.
4. Follow the browser prompt to register your biometric (fingerprint/face).
5. Next time you log in, the browser will offer the Passkey via both autofill and the manual flow.

## 🏗️ Architecture Overview

*   **`App\Security\HybridAuthenticator`:** A custom authenticator that intercepts password-based login attempts, validating them via the Symfony Passport system.
*   **`App\Controller\AuthController`:** Contains the `/api/auth/flow` endpoint, which performs real-time detection of a user's preferred authentication method.
*   **`App\Controller\PasskeySettingsController`:** A specialized controller for authenticated users to manage their Passkeys, bypassing standard registration limits for existing accounts.
*   **`assets/controllers/webauthn_service.js`:** A shared JavaScript module that encapsulates the WebAuthn challenge/response cycle (fetch options -> browser API -> verify result).
*   **`App\Entity\User`:** Supports both `userHandle` (for WebAuthn) and a hashed `password`.
*   **`App\Repository\UserRepository` & `PublicKeyCredentialSourceRepository`:** Centralizes persistence logic and ensures correct Base64URL encoding for binary credential data.

## 🛡️ Security

The project implements full CSRF protection on all authentication-related AJAX requests via `csrf_protection_controller.js`, ensuring that even passwordless flows are protected against cross-site attacks.
