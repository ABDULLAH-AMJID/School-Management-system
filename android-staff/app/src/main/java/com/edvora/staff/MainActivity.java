package com.edvora.staff;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.Context;
import android.graphics.Color;
import android.net.ConnectivityManager;
import android.net.Network;
import android.net.NetworkCapabilities;
import android.net.Uri;
import android.os.Bundle;
import android.view.Gravity;
import android.view.ViewGroup;
import android.webkit.CookieManager;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.TextView;

/** LAN-only browser for the existing, server-authorized staff portal. */
public final class MainActivity extends Activity {
    private WebView web;
    private String host;
    private int port;

    @Override public void onCreate(Bundle state) {
        super.onCreate(state);
        showSetup();
    }

    private boolean onWifi() {
        ConnectivityManager cm = (ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE);
        Network active = cm.getActiveNetwork();
        NetworkCapabilities cap = active == null ? null : cm.getNetworkCapabilities(active);
        return cap != null && cap.hasTransport(NetworkCapabilities.TRANSPORT_WIFI);
    }

    private static boolean privateIpv4(String ip) {
        if (ip == null || !ip.matches("[0-9]{1,3}(\\.[0-9]{1,3}){3}")) return false;
        String[] parts = ip.split("\\.");
        int[] octets = new int[4];
        for (int i = 0; i < 4; i++) {
            octets[i] = Integer.parseInt(parts[i]);
            if (octets[i] > 255 || !String.valueOf(octets[i]).equals(parts[i])) return false;
        }
        return octets[0] == 10 || (octets[0] == 172 && octets[1] >= 16 && octets[1] <= 31)
            || (octets[0] == 192 && octets[1] == 168);
    }

    private void showSetup() {
        ((ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE)).bindProcessToNetwork(null);
        LinearLayout root = new LinearLayout(this);
        root.setOrientation(LinearLayout.VERTICAL);
        root.setPadding(36, 60, 36, 36);
        root.setBackgroundColor(Color.rgb(248, 248, 255));
        TextView title = new TextView(this);
        title.setText("EDVORA  ·  STAFF"); title.setTextSize(24); title.setTextColor(Color.rgb(48, 40, 110));
        TextView info = new TextView(this);
        info.setText("Connect to school Wi-Fi, then enter the HTTPS address provided by your school administrator. The school CA certificate must be installed first.");
        info.setTextSize(16); info.setPadding(0, 24, 0, 24);
        EditText input = new EditText(this);
        input.setSingleLine(true); input.setHint("https://192.168.1.10:8443");
        input.setText(getPreferences(MODE_PRIVATE).getString("server", ""));
        Button connect = new Button(this); connect.setText("Connect securely");
        connect.setOnClickListener(v -> {
            Uri url = Uri.parse(input.getText().toString().trim());
            int selectedPort = url.getPort() == -1 ? 443 : url.getPort();
            if (!onWifi()) { message("Connect this phone to school Wi-Fi first."); return; }
            if (!"https".equals(url.getScheme()) || !privateIpv4(url.getHost()) || selectedPort < 1 || selectedPort > 65535
                    || url.getUserInfo() != null || url.getQuery() != null || url.getFragment() != null
                    || (url.getPath() != null && !url.getPath().isEmpty() && !"/".equals(url.getPath()))) {
                message("Enter only an HTTPS address with a private IPv4 address and optional port."); return;
            }
            ConnectivityManager cm = (ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE);
            Network wifi = cm.getActiveNetwork();
            if (wifi == null || !cm.bindProcessToNetwork(wifi)) { message("Unable to bind to Wi-Fi."); return; }
            host = url.getHost(); port = selectedPort;
            String base = "https://" + host + ":" + port + "/";
            getPreferences(MODE_PRIVATE).edit().putString("server", base).apply();
            openPortal(base);
        });
        root.addView(title); root.addView(info); root.addView(input); root.addView(connect);
        setContentView(root);
    }

    private boolean sameOrigin(Uri url) {
        int candidatePort = url.getPort() == -1 ? 443 : url.getPort();
        return "https".equals(url.getScheme()) && host.equals(url.getHost()) && port == candidatePort && url.getUserInfo() == null;
    }

    private void openPortal(String base) {
        LinearLayout root = new LinearLayout(this); root.setOrientation(LinearLayout.VERTICAL);
        Button settings = new Button(this); settings.setText("Change school server");
        settings.setOnClickListener(v -> { web.stopLoading(); web.destroy(); web = null; showSetup(); });
        root.addView(settings);
        web = new WebView(this);
        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(true); s.setDomStorageEnabled(true);
        s.setAllowFileAccess(false); s.setAllowContentAccess(false);
        s.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        CookieManager.getInstance().setAcceptThirdPartyCookies(web, false);
        web.setWebViewClient(new WebViewClient() {
            @Override public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                return !onWifi() || !sameOrigin(request.getUrl());
            }
        });
        root.addView(web, new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, 0, 1));
        setContentView(root); web.loadUrl(base);
    }
    private void message(String value) { new AlertDialog.Builder(this).setMessage(value).setPositiveButton("OK", null).show(); }
    @Override public void onBackPressed() { if (web != null && web.canGoBack()) web.goBack(); else if (web != null) { web.destroy(); web = null; showSetup(); } else super.onBackPressed(); }
    @Override protected void onDestroy() { if (web != null) web.destroy(); ((ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE)).bindProcessToNetwork(null); super.onDestroy(); }
}
