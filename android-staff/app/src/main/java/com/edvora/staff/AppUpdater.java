package com.edvora.staff;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.Intent;
import android.content.pm.PackageInfo;
import android.content.pm.PackageManager;
import android.content.pm.Signature;
import android.net.Uri;
import android.os.Build;
import android.provider.Settings;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.Arrays;

/** Stable releases only. Android still checks package signatures and requires user consent. */
final class AppUpdater {
    private static final String API = "https://api.github.com/repos/ABDULLAH-AMJID/School-Management-system/releases/latest";
    private static final String APK = "Edvora-Staff-release.apk";
    private static final long MAX_APK = 100L * 1024 * 1024;
    private final Activity activity;
    AppUpdater(Activity activity) { this.activity = activity; }
    private void ui(Runnable r) { activity.runOnUiThread(() -> { if (!activity.isFinishing() && !activity.isDestroyed()) r.run(); }); }
    private void show(String s) { new AlertDialog.Builder(activity).setMessage(s).setPositiveButton("OK", null).show(); }

    void check(boolean interactive) {
        if (interactive) android.widget.Toast.makeText(activity, "Checking GitHub Releases…", android.widget.Toast.LENGTH_SHORT).show();
        new Thread(() -> {
            try {
                HttpURLConnection c = open(API);
                try {
                    if (c.getResponseCode() == 404) { if (interactive) ui(() -> show("No public stable Android release is available yet.")); return; }
                    if (c.getResponseCode() != 200) throw new Exception("GitHub returned HTTP " + c.getResponseCode());
                    ByteArrayOutputStream out = new ByteArrayOutputStream();
                    try (InputStream in = c.getInputStream()) { byte[] b = new byte[4096]; int n; while ((n = in.read(b)) != -1) { if (out.size() + n > 1024 * 1024) throw new Exception("Release metadata too large"); out.write(b, 0, n); } }
                    JSONObject release = new JSONObject(out.toString("UTF-8"));
                    String tag = release.getString("tag_name");
                    if (!tag.matches("android-v[1-9][0-9]{0,8}")) { if (interactive) ui(() -> show("Latest release is not an Android release. Ask the school administrator.")); return; }
                    int version = Integer.parseInt(tag.substring("android-v".length()));
                    int current = activity.getPackageManager().getPackageInfo(activity.getPackageName(), 0).versionCode;
                    if (version <= current) { if (interactive) ui(() -> show("Your app is up to date.")); return; }
                    JSONArray assets = release.getJSONArray("assets"); String url = null;
                    for (int i = 0; i < assets.length(); i++) {
                        JSONObject a = assets.getJSONObject(i);
                        if (APK.equals(a.getString("name"))) { url = a.getString("browser_download_url"); break; }
                    }
                    if (url == null || !url.startsWith("https://github.com/ABDULLAH-AMJID/School-Management-system/releases/download/")) throw new Exception("Release APK missing or URL invalid");
                    final String download = url;
                    ui(() -> new AlertDialog.Builder(activity).setTitle("App update available")
                        .setMessage("Android release " + tag + " is available. Download and install it? Android will ask for your confirmation.")
                        .setNegativeButton("Later", null).setPositiveButton("Download", (d, w) -> download(download, version)).show());
                } finally { c.disconnect(); }
            } catch (Exception e) { if (interactive) ui(() -> show("Could not check for updates. Check internet and try again.")); }
        }, "update-check").start();
    }

    private static HttpURLConnection open(String address) throws Exception {
        URL url = new URL(address);
        if (!"https".equals(url.getProtocol())) throw new Exception("HTTPS required");
        HttpURLConnection c = (HttpURLConnection) url.openConnection();
        c.setConnectTimeout(15000); c.setReadTimeout(30000); c.setRequestProperty("User-Agent", "Edvora-Staff-Android");
        c.setRequestProperty("Accept", "application/vnd.github+json"); c.setInstanceFollowRedirects(false); return c;
    }

    private void download(String url, int expectedVersion) {
        android.widget.Toast.makeText(activity, "Downloading update; keep the app open.", android.widget.Toast.LENGTH_LONG).show();
        new Thread(() -> {
            File temp = new File(activity.getCacheDir(), "update.tmp");
            File target = new File(activity.getCacheDir(), "update.apk");
            try {
                String next = url; HttpURLConnection c = null;
                for (int i = 0; i < 5; i++) {
                    URL parsed = new URL(next); String h = parsed.getHost();
                    if (!"https".equals(parsed.getProtocol()) || !(h.equals("github.com") || h.equals("release-assets.githubusercontent.com") || h.equals("objects.githubusercontent.com"))) throw new Exception("Unexpected download host");
                    c = open(next); int status = c.getResponseCode();
                    if (status >= 300 && status < 400) { String location = c.getHeaderField("Location"); if (location == null) throw new Exception("Missing redirect"); next = new URL(parsed, location).toString(); c.disconnect(); c = null; continue; }
                    if (status != 200) throw new Exception("Download failed"); break;
                }
                if (c == null) throw new Exception("Too many redirects");
                try (InputStream in = c.getInputStream(); FileOutputStream out = new FileOutputStream(temp)) {
                    byte[] b = new byte[32768]; int n; long total = 0;
                    while ((n = in.read(b)) != -1) { total += n; if (total > MAX_APK) throw new Exception("APK too large"); out.write(b, 0, n); }
                } finally { c.disconnect(); }
                verify(temp, expectedVersion);
                if (target.exists() && !target.delete()) throw new Exception("Cannot replace update");
                if (!temp.renameTo(target)) throw new Exception("Cannot save update");
                ui(this::offerInstall);
            } catch (Exception e) { temp.delete(); ui(() -> show("Update download or signature verification failed. No update was installed.")); }
        }, "update-download").start();
    }

    @SuppressWarnings("deprecation") private void verify(File file, int expectedVersion) throws Exception {
        PackageManager pm = activity.getPackageManager();
        PackageInfo installed = pm.getPackageInfo(activity.getPackageName(), PackageManager.GET_SIGNATURES);
        PackageInfo incoming = pm.getPackageArchiveInfo(file.getAbsolutePath(), PackageManager.GET_SIGNATURES);
        if (incoming == null || !activity.getPackageName().equals(incoming.packageName) || incoming.versionCode != expectedVersion || incoming.versionCode <= installed.versionCode
                || installed.signatures == null || installed.signatures.length != 1 || incoming.signatures == null || incoming.signatures.length != 1
                || !Arrays.equals(installed.signatures[0].toByteArray(), incoming.signatures[0].toByteArray())) throw new Exception("Invalid package, version, or signing certificate");
    }

    private void offerInstall() {
        if (Build.VERSION.SDK_INT >= 26 && !activity.getPackageManager().canRequestPackageInstalls()) {
            new AlertDialog.Builder(activity).setTitle("Allow updates")
                .setMessage("Android needs permission for Edvora Staff to install updates. Enable it in settings, then check for updates again.")
                .setNegativeButton("Cancel", null).setPositiveButton("Settings", (d, w) -> activity.startActivity(new Intent(Settings.ACTION_MANAGE_UNKNOWN_APP_SOURCES, Uri.parse("package:" + activity.getPackageName())))).show();
            return;
        }
        Uri content = Uri.parse("content://" + activity.getPackageName() + ".updates/update.apk");
        Intent install = new Intent(Intent.ACTION_VIEW).setDataAndType(content, "application/vnd.android.package-archive").addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION);
        try { activity.startActivity(install); } catch (Exception e) { show("Android package installer is unavailable."); }
    }
}
