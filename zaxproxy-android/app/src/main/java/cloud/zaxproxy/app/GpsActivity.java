package cloud.zaxproxy.app;

import android.Manifest;
import android.annotation.SuppressLint;
import android.app.Activity;
import android.app.AppOpsManager;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.pm.PackageManager;
import android.net.ConnectivityManager;
import android.net.Network;
import android.net.NetworkCapabilities;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.os.Process;
import android.provider.Settings;
import android.webkit.JavascriptInterface;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.Locale;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

/**
 * The GPS map: a map of the world with the customer's proxies on it. Tap a place, search for a
 * state, city or country, or pick one of the proxies, then switch it on and the phone's location
 * becomes that place. The screen is a page bundled inside the app (assets/map.html); this class
 * gives that page the few things only Android can do.
 */
public class GpsActivity extends Activity {

    private static final String PAGE = "file:///android_asset/map.html";
    private static final String UA = "ZaxProxyConnect/" + BuildConfig.VERSION_NAME + " (Android; zaxproxy.cloud)";
    private static final int REQ_LOCATION = 20;

    private WebView web;
    private final Handler ui = new Handler(Looper.getMainLooper());
    private final ExecutorService io = Executors.newSingleThreadExecutor();
    private double pendingLat, pendingLng;
    private String pendingLabel;
    private boolean hasPending;
    private JSONObject want; // a place the main screen asked this screen to set (used once)

    private final BroadcastReceiver stateReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context c, Intent i) {
            pushState();
        }
    };

    /** True when the owner picked this app under Developer options > Select mock location app. */
    static boolean mockAllowed(Context c) {
        try {
            AppOpsManager ops = (AppOpsManager) c.getSystemService(Context.APP_OPS_SERVICE);
            int mode = Build.VERSION.SDK_INT >= 29
                    ? ops.unsafeCheckOpNoThrow(AppOpsManager.OPSTR_MOCK_LOCATION, Process.myUid(), c.getPackageName())
                    : ops.checkOpNoThrow(AppOpsManager.OPSTR_MOCK_LOCATION, Process.myUid(), c.getPackageName());
            return mode == AppOpsManager.MODE_ALLOWED;
        } catch (Exception e) {
            return false;
        }
    }

    static boolean locationGranted(Context c) {
        return c.checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED
                || c.checkSelfPermission(Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED;
    }

    /** Sets the phone's location to a place. Returns false when Android refused to start it. */
    static boolean begin(Context c, double lat, double lng, String label) {
        MockService.lastError = "";
        Intent i = new Intent(c, MockService.class).setAction(MockService.ACTION_START)
                .putExtra("lat", lat).putExtra("lng", lng).putExtra("label", label);
        try {
            if (Build.VERSION.SDK_INT >= 26) {
                c.startForegroundService(i);
            } else {
                c.startService(i);
            }
            return true;
        } catch (Exception e) {
            MockService.lastError = "Android did not let the app start. Open ZaxProxy Connect and try again.";
            return false;
        }
    }

    static void end(Context c) {
        try {
            c.startService(new Intent(c, MockService.class).setAction(MockService.ACTION_STOP));
        } catch (Exception ignored) {
        }
    }

    @SuppressLint({"SetJavaScriptEnabled", "AddJavascriptInterface"})
    @Override
    protected void onCreate(Bundle saved) {
        super.onCreate(saved);
        readWant(getIntent());
        web = new WebView(this);
        web.setBackgroundColor(0xFF0B0D14);
        setContentView(web);
        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setAllowFileAccess(true);
        s.setAllowContentAccess(false);
        s.setSupportZoom(false);
        s.setUserAgentString(s.getUserAgentString() + " " + UA);
        web.setOverScrollMode(WebView.OVER_SCROLL_NEVER);
        web.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView v, WebResourceRequest r) {
                // The app's own page never navigates away; any link opens in the phone's browser.
                Uri u = r.getUrl();
                if (PAGE.equals(u.toString())) return false;
                if ("https".equals(u.getScheme())) {
                    try {
                        startActivity(new Intent(Intent.ACTION_VIEW, u));
                    } catch (Exception ignored) {
                    }
                }
                return true;
            }
        });
        web.addJavascriptInterface(new Bridge(), "Zax");
        web.loadUrl(PAGE);
    }

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        readWant(intent);
        pushState();
    }

    private void readWant(Intent i) {
        want = null;
        if (i != null && i.hasExtra("lat")) {
            try {
                want = new JSONObject().put("lat", i.getDoubleExtra("lat", 0)).put("lng", i.getDoubleExtra("lng", 0))
                        .put("label", String.valueOf(i.getStringExtra("label"))).put("proxy", i.getLongExtra("proxy", 0));
            } catch (Exception ignored) {
            }
        }
    }

    @Override
    protected void onResume() {
        super.onResume();
        IntentFilter f = new IntentFilter(MockService.ACTION_STATE);
        if (Build.VERSION.SDK_INT >= 33) {
            registerReceiver(stateReceiver, f, Context.RECEIVER_NOT_EXPORTED);
        } else {
            registerReceiver(stateReceiver, f);
        }
        pushState();
    }

    @Override
    protected void onPause() {
        unregisterReceiver(stateReceiver);
        super.onPause();
    }

    @Override
    public void onBackPressed() {
        web.evaluateJavascript("window.zaxBack && window.zaxBack()", v -> {
            if (!"true".equals(v)) finish(); // the location stays on after leaving this screen
        });
    }

    private boolean developerOn() {
        return Settings.Global.getInt(getContentResolver(), Settings.Global.DEVELOPMENT_SETTINGS_ENABLED, 0) == 1;
    }

    /** The proxy the app is connected through right now (0 when not connected). */
    private long connectedProxy() {
        try (FileInputStream in = new FileInputStream(new File(getFilesDir(), TunnelService.STATE_FILE))) {
            byte[] buf = new byte[512];
            int n = in.read(buf);
            String s = n > 0 ? new String(buf, 0, n, StandardCharsets.UTF_8) : "";
            if (s.isEmpty()) return 0;
            ConnectivityManager cm = (ConnectivityManager) getSystemService(CONNECTIVITY_SERVICE);
            boolean vpn = false;
            for (Network net : cm.getAllNetworks()) {
                NetworkCapabilities caps = cm.getNetworkCapabilities(net);
                if (caps != null && caps.hasTransport(NetworkCapabilities.TRANSPORT_VPN)) vpn = true;
            }
            return vpn ? Long.parseLong(s.split("\\|", 2)[0]) : 0;
        } catch (Exception e) {
            return 0;
        }
    }

    private String stateJson() {
        JSONObject o = new JSONObject();
        try {
            o.put("on", MockService.running)
                    .put("lat", MockService.lat)
                    .put("lng", MockService.lng)
                    .put("label", MockService.label)
                    .put("since", MockService.since)
                    .put("error", MockService.lastError)
                    .put("mock", mockAllowed(this))
                    .put("developer", developerOn())
                    .put("connected", connectedProxy())
                    .put("version", BuildConfig.VERSION_NAME);
            if (want != null) {
                o.put("want", want);
                want = null;
            }
        } catch (Exception ignored) {
        }
        return o.toString();
    }

    private void pushState() {
        final String json = stateJson();
        ui.post(() -> web.evaluateJavascript("window.zaxState && window.zaxState(" + json + ")", null));
    }

    private void reply(String id, String json) {
        ui.post(() -> web.evaluateJavascript("window.zaxReply && window.zaxReply(" + JSONObject.quote(id) + "," + json + ")", null));
    }

    @Override
    public void onRequestPermissionsResult(int req, String[] perms, int[] results) {
        super.onRequestPermissionsResult(req, perms, results);
        if (req == REQ_LOCATION && hasPending) {
            hasPending = false;
            if (locationGranted(this)) {
                begin(this, pendingLat, pendingLng, pendingLabel);
                ui.postDelayed(this::pushState, 600);
            } else {
                MockService.lastError = "Allow location for ZaxProxy Connect, then tap the button again. Android needs it before an app may set the location.";
                pushState();
            }
        }
    }

    private static String get(String url) throws Exception {
        HttpURLConnection c = (HttpURLConnection) new URL(url).openConnection();
        c.setConnectTimeout(10000);
        c.setReadTimeout(12000);
        c.setRequestProperty("User-Agent", UA);
        c.setRequestProperty("Accept", "application/json");
        c.setRequestProperty("Accept-Language", "en");
        try (InputStream in = c.getInputStream()) {
            ByteArrayOutputStream out = new ByteArrayOutputStream();
            byte[] buf = new byte[8192];
            int n;
            while ((n = in.read(buf)) > 0 && out.size() < 600000) out.write(buf, 0, n);
            return new String(out.toByteArray(), StandardCharsets.UTF_8);
        } finally {
            c.disconnect();
        }
    }

    /** What the page may ask Android to do. Every method is safe to call with any input. */
    private final class Bridge {

        @JavascriptInterface
        public String state() {
            return stateJson();
        }

        /** The customer's active proxies as the main screen last loaded them. Never a password: only what the map shows. */
        @JavascriptInterface
        public String proxies() {
            JSONArray out = new JSONArray();
            try {
                JSONArray list = new JSONArray(getSharedPreferences("zax", MODE_PRIVATE).getString("proxies", "[]"));
                for (int i = 0; i < list.length(); i++) {
                    JSONObject p = list.optJSONObject(i);
                    if (p == null) continue;
                    JSONObject o = new JSONObject();
                    for (String k : new String[]{"id", "type", "country", "flag", "ip", "city", "region", "place", "left", "state"}) o.put(k, p.opt(k));
                    o.put("lat", p.isNull("lat") ? JSONObject.NULL : p.optDouble("lat"));
                    o.put("lng", p.isNull("lng") ? JSONObject.NULL : p.optDouble("lng"));
                    out.put(o);
                }
            } catch (Exception ignored) {
            }
            return out.toString();
        }

        @JavascriptInterface
        public void start(final double lat, final double lng, final String label) {
            if (Double.isNaN(lat) || Double.isNaN(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) return;
            final String name = label == null ? "" : (label.length() > 120 ? label.substring(0, 120) : label);
            ui.post(() -> {
                if (!mockAllowed(GpsActivity.this)) {
                    MockService.lastError = "mock";
                    pushState();
                    return;
                }
                if (!locationGranted(GpsActivity.this)) {
                    pendingLat = lat;
                    pendingLng = lng;
                    pendingLabel = name;
                    hasPending = true;
                    requestPermissions(new String[]{Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION}, REQ_LOCATION);
                    return;
                }
                begin(GpsActivity.this, lat, lng, name);
                ui.postDelayed(GpsActivity.this::pushState, 600);
            });
        }

        @JavascriptInterface
        public void stop() {
            ui.post(() -> {
                end(GpsActivity.this);
                ui.postDelayed(GpsActivity.this::pushState, 500);
            });
        }

        /** Back to the proxy screen. */
        @JavascriptInterface
        public void close() {
            ui.post(GpsActivity.this::finish);
        }

        /** Opens Developer options, or "About phone" when Developer options are still hidden. */
        @JavascriptInterface
        public void settings(final String which) {
            ui.post(() -> {
                String[] tries = "about".equals(which)
                        ? new String[]{Settings.ACTION_DEVICE_INFO_SETTINGS, Settings.ACTION_SETTINGS}
                        : new String[]{Settings.ACTION_APPLICATION_DEVELOPMENT_SETTINGS, Settings.ACTION_DEVICE_INFO_SETTINGS, Settings.ACTION_SETTINGS};
                for (String a : tries) {
                    try {
                        startActivity(new Intent(a));
                        return;
                    } catch (Exception ignored) {
                    }
                }
            });
        }

        /** Opens a page of the ZaxProxy website in the phone's browser. */
        @JavascriptInterface
        public void open(final String path) {
            if (path == null || !path.startsWith("/") || path.startsWith("//") || path.contains("\\")) return;
            ui.post(() -> {
                try {
                    startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(BuildConfig.SITE + path)));
                } catch (Exception ignored) {
                }
            });
        }

        /** Looks a place up by name. The answer arrives at window.zaxReply(id, [...]). */
        @JavascriptInterface
        public void search(final String id, final String query) {
            final String q = query == null ? "" : query.trim();
            if (q.isEmpty() || q.length() > 120) {
                reply(id, "[]");
                return;
            }
            io.execute(() -> {
                try {
                    String body = get("https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=8&accept-language=en&q=" + URLEncoder.encode(q, "UTF-8"));
                    reply(id, body.trim().startsWith("[") ? body : "[]");
                } catch (Exception e) {
                    reply(id, "null");
                }
            });
        }

        /** Names the place at a point on the map. The answer arrives at window.zaxReply(id, {...}). */
        @JavascriptInterface
        public void reverse(final String id, final double lat, final double lng) {
            if (Double.isNaN(lat) || Double.isNaN(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
                reply(id, "null");
                return;
            }
            io.execute(() -> {
                try {
                    String body = get(String.format(Locale.US, "https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=12&accept-language=en&lat=%.5f&lon=%.5f", lat, lng));
                    reply(id, body.trim().startsWith("{") ? body : "null");
                } catch (Exception e) {
                    reply(id, "null");
                }
            });
        }
    }
}
