# Symfony 7.4 Passkey Authentication Demo

This is a complete, working demonstration of implementing robust, passwordless Passkey (FIDO2/WebAuthn) authentication in a modern **Symfony 7.4** application. It uses the industry-standard `web-auth/webauthn-symfony-bundle` along with modern frontend tooling (Stimulus + AssetMapper) to provide a seamless user experience.

## ✨ Features

*   **100% Passwordless:** No passwords, hashes, or reset emails required. Entities and Security config are completely stripped of password logic.
*   **Modern Symfony Stack:** Built on Symfony 7.4 LTS with Doctrine ORM.
*   **Zero-Node Frontend:** Uses Symfony AssetMapper and Stimulus (`@hotwired/stimulus`) to handle the WebAuthn API natively on the client side.
*   **Standardized Security:** Integrates natively into the Symfony Security component (Firewalls, Authenticators) for both registration and login flows.
*   **CSRF Protection:** Secure Stimulus controllers integrate Symfony's CSRF protection into AJAX `fetch` requests.
*   **Biometric Support:** Works out-of-the-box with Apple Touch ID / Face ID, Windows Hello, Android Biometrics, and hardware security keys (like YubiKey), with full `TrustPath` data integrity.

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
   
2. Install dependencies:
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
   # Create the SQLite database and schema
   php bin/console doctrine:database:create
   php bin/console doctrine:schema:create
   ```

5. **Start the local server:**
   You can use Symfony CLI or the built-in PHP server.
   ```bash
   # Using PHP built-in server
   php -S localhost:8000 -t public
   
   # Or using Symfony CLI
   symfony server:start
   ```

6. **Access the application:**
   Open your browser and navigate to `http://localhost:8000`.

## 💻 Usage Guide

### 1. Registration
1. On the home page, enter an email address under the **Register** section.
2. Click "Register with Passkey".
3. Your browser or operating system will prompt you to create a Passkey (e.g., scan your fingerprint or use Face ID).
4. Upon success, the server securely stores the public key, and you are logged in and redirected to the dashboard.

### 2. Login
1. Once registered, log out (or open an incognito window).
2. On the home page, click "Log in with Passkey" under the **Login** section.
3. Your browser will prompt you to authenticate using the Passkey you created.
4. Upon success, you are securely logged into the dashboard.

## 🏗️ Architecture Overview

If you want to understand how this works or adapt it for your own application, here are the key components:

*   **`config/packages/webauthn.yaml`:** The core configuration file for the WebAuthn bundle. It defines the Relying Party (your app) using environment variables.
*   **`config/packages/security.yaml`:** Configures the `webauthn` firewall to handle both the registration and authentication result directly via the Symfony Security component, securely persisting the user and logging them in.
*   **Entities:**
    *   `App\Entity\User`: Represents the human user. Unlike traditional apps, it contains a `userHandle` (a unique UUID required by WebAuthn) and **no password**.
    *   `App\Entity\PublicKeyCredentialSource`: Stores the metadata, trust path, and public key of the device the user registered with.
*   **Repositories:**
    *   `UserRepository`: Bridges Doctrine and the WebAuthn bundle by implementing `PublicKeyCredentialUserEntityRepositoryInterface`, `CanRegisterUserEntity`, and `CanGenerateUserEntity`.
    *   `PublicKeyCredentialSourceRepository`: Bridges the credential storage by implementing `PublicKeyCredentialSourceRepositoryInterface` and `CanSaveCredentialSource`.
*   **Frontend (`assets/controllers/passkey_controller.js`):** A Stimulus controller that bridges the HTML forms to the `@simplewebauthn/browser` package. It handles the two-step handshake: asking the server for "options" (a challenge), triggering the browser's native biometric prompt, and sending the "result" back to the server for verification, all while passing secure CSRF headers.


