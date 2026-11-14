# Android staff app — LAN pilot (not production-ready)

The Android project wraps the existing responsive Laravel portal. Authorization stays on the server. It requires an HTTPS URL with an RFC1918 IPv4 address and binds its network connections to the current Wi-Fi network. It cannot identify whether that Wi-Fi belongs to the school; firewall and router isolation are still necessary. The app does not bypass certificate validation. It trusts the normal Android system CAs **and user-installed CAs**; only install a verified school CA on managed devices.

## Important status

- Existing email/password login and remember-me are used. WhatsApp OTP enrollment and password setup are **not implemented**.
- The first APK is a debug/pilot build, not an authenticated release. It needs real Android and Windows integration tests before any staff/student data is used. Debug signing keys are not suitable for distribution.
- Downloads, uploads, printing, camera and external links are not yet implemented or verified in the wrapper. Some ERP features may therefore not work.
- PC must remain on; Wi-Fi must allow clients to reach the server (no client isolation). No public port forwarding or tunnel is necessary.

## HTTPS setup on Windows (pilot)

1. Reserve a static IPv4 address on the router, e.g. `192.168.1.10`. Firewall-allow TCP 8443 **only** from the school LAN; never expose it publicly.
2. Install Caddy from the official site https://caddyserver.com/docs/install and independently verify the download. Copy `scripts/windows/Caddyfile.lan.example` outside the repo and replace `192.168.1.10` with the actual fixed address. Caddy's `tls internal` creates a local CA and an IP-address certificate. Secure the Caddy data directory and its **private CA key**; never distribute or commit it. See https://caddyserver.com/docs/automatic-https#local-https .
3. Stop the old ERP server first. The included `scripts/windows/start-school-erp-https.ps1 -CaddyConfig C:\path\to\Caddyfile` launches Laravel on `127.0.0.1:8000`, Caddy, queue, scheduler and connector after the `.env` settings in step 4 are in place. The existing `start-school-erp.ps1` exposes HTTP on the LAN and **must not be used as-is** for this pilot. Startup launch alone does not prove service health; verify each process.
4. Configure Laravel for the reverse proxy: `APP_URL=https://192.168.1.10:8443`, `SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false`, production secrets, and set `TRUST_LOCAL_HTTPS_PROXY=true` to trust only the loopback proxy for forwarded headers. Do not trust arbitrary internet or LAN proxies. After changing `.env`, clear configuration cache with `php artisan config:clear`, then test generated HTTPS links and secure sessions. Only enable this setting when direct access to the backend is limited to loopback. Do not put credentials in the repo.
5. Export only Caddy's **public root certificate**, typically `root.crt` in Caddy's data directory under `pki/authorities/local/`. Verify its SHA-256 fingerprint on the PC and on the receiving device out-of-band. Install this public root as a user CA on each managed Android device following its OS instructions. Never share `root.key`. Android might require a device lock. Installing a user CA affects other apps that choose to trust user CAs and carries risk; use managed devices and remove the CA when access is revoked.
6. Connect phone to school Wi-Fi, open the app and enter `https://192.168.1.10:8443`. The IP must exactly match the Caddy certificate. If validation fails, do not bypass it; check the CA, IP, time, and network. Test login, logout, permissions and session expiry with dummy data.

Changing the PC's LAN IP requires updating Caddy, `APP_URL`, Android settings, firewall, and re-testing. Existing LAN clients using plain HTTP must migrate before the old listener is closed.

## Build

Install JDK 17+, Android SDK platform 35 and build tools 35.0.0, plus Gradle 8.9. From `android-staff`, set `ANDROID_HOME` to the SDK path and run `gradle assembleDebug`. The output is `app/build/outputs/apk/debug/app-debug.apk`. For production, configure a private release signing key, run release builds and undertake Android/Windows security and end-to-end testing.
