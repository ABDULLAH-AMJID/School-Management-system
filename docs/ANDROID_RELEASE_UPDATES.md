# Android app updates through GitHub Releases

## Scope and current status

The pilot checks the latest **public, stable GitHub Release** of `ABDULLAH-AMJID/School-Management-system` at launch and via the Check for app updates button. GitHub must be reachable from the active network. No GitHub token is embedded in the app. The school portal still uses the private HTTPS LAN connection; the updater needs internet. Android updates are **not** triggered by pushing source code. Backend Laravel changes must separately be deployed to the school PC.

Release convention: the latest stable release tag must be `android-vN` where N is the app's integer `versionCode` (for example `android-v3`), and it must contain the asset `Edvora-Staff-release.apk`. The APK must have the same package ID and the same signing certificate as the already installed app, with versionCode exactly N and greater than the installed version. The app downloads over HTTPS only from GitHub and its approved asset hosts, rejects unexpected package, version, or signing certificate, and asks Android to install. Android also enforces signature verification and requires the user's confirmation. Installation cannot be silent. If another non-Android release is the GitHub latest release, no Android update is offered; publish a new stable Android release or improve the release-selection strategy before using this with mixed releases.

This updater and the pilot are not yet end-to-end tested on a real Android phone, Windows server or published release; test with dummy data before deployment. There is no release APK or release-signing key yet. Android permission for installing unknown apps may require the user to enable it in system settings, then check again. This sideload approach differs from Google Play In-App Updates and is not suitable for Play distribution without redesign.

## Signing and first installation

A school administrator should generate and securely back up a dedicated **release keystore** on a trusted build PC, outside the repository. Protect its passwords; loss means existing installs cannot receive normally signed updates. Never upload the keystore, passwords, or GitHub token to the repository or chat. The previous debug APK is signed by a debug key, not the school's release key. A properly release-signed APK cannot update that debug installation: users must first uninstall the debug build (which may clear app data) and install the school's initial release APK. All later versions must use the **same release signing key**. Install APKs only through a trusted channel and verify the signing certificate fingerprint out of band.

One-time keystore creation example (Java keytool, on the trusted Windows build PC):

```powershell
keytool -genkeypair -v -keystore C:\secure\edvora-release.jks -alias edvora -keyalg RSA -keysize 3072 -validity 10000
```

Record the displayed certificate SHA-256 fingerprint independently using `keytool -list -v -keystore C:\secure\edvora-release.jks -alias edvora`. Keep encrypted backups of the key and passwords with appropriate restricted access.

## Prepare and publish a new release

1. Increment `versionCode` in `android-staff/app/build.gradle` above the previous released code and update `versionName`; e.g. versionCode `3` for `android-v3`. Test the app and backend using a staging school environment.
2. On a trusted Windows build machine with JDK, Gradle, and Android SDK installed, set environment variables privately for the **current terminal only**, including `EDVORA_KEYSTORE` (absolute path), `EDVORA_KEY_ALIAS`, `EDVORA_STORE_PASSWORD`, and `EDVORA_KEY_PASSWORD`. Do not paste secrets into chat, scripts or GitHub. Avoid shell history and clear these variables after building.
3. In `android-staff`, run `gradle assembleRelease`. Check that the APK is signed with the expected certificate using the Android SDK's `apksigner verify --print-certs app/build/outputs/apk/release/app-release.apk`. Check package ID and versionCode using `apkanalyzer manifest application-id` and `apkanalyzer manifest version-code`. Rename the verified APK `Edvora-Staff-release.apk`.
4. On GitHub create a **published stable release** on the intended commit with tag `android-v3` (use the actual versionCode) and upload the APK as `Edvora-Staff-release.apk`. Do not attach the signing key or secrets. A draft, prerelease, source commit, or branch push is not a stable release.
5. Test the update end-to-end on an enrolled Android test device: available release, no update, offline, user cancel, unknown-app permission, valid upgrade, wrong-key APK, wrong package, invalid version, interrupted download, restart, and Wi-Fi connectivity. Never publish an untested build for the staff.

This is a staged pilot, not a complete enterprise update management system. Its updater uses the latest release only, has no Play Store staged rollout or enterprise device-management controls, and does not automatically install or reboot. Protect GitHub owner access with MFA and restrict who can publish releases.
