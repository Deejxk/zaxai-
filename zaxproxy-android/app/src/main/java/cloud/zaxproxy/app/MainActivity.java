package cloud.zaxproxy.app;

import android.Manifest;
import android.animation.ObjectAnimator;
import android.animation.PropertyValuesHolder;
import android.animation.ValueAnimator;
import android.app.Activity;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.net.ConnectivityManager;
import android.net.Network;
import android.net.NetworkCapabilities;
import android.net.Uri;
import android.net.VpnService;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.LayoutInflater;
import android.view.View;
import android.view.animation.AccelerateDecelerateInterpolator;
import android.view.inputmethod.EditorInfo;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.File;
import java.io.FileInputStream;
import java.nio.charset.StandardCharsets;
import java.util.Locale;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

/**
 * One screen: a large connect button, the location it will use, and the list of the
 * customer's proxies. Tapping a location selects it; if a connection is already running,
 * tapping another location switches to it.
 */
public class MainActivity extends Activity {

    private static final int REQ_VPN = 10;
    private static final int SEARCH_FROM = 6; // show the search box from this many proxies

    private final ExecutorService io = Executors.newSingleThreadExecutor();
    private final Handler ui = new Handler(Looper.getMainLooper());
    private SharedPreferences prefs;

    private View loginPanel, mainPanel, empty, power, halo, ring, disc, selected, none;
    private EditText inLogin, inPass, search;
    private TextView loginError, hello, statusTitle, statusSub, timer, message, selFlag, selTitle, selSub, selLabel, count;
    private Button btnLogin;
    private LinearLayout list;

    private JSONArray proxies = new JSONArray();
    private JSONArray expired = new JSONArray();
    private JSONArray manual = new JSONArray();   // typed in by hand; kept on this phone only
    private SharedPreferences manualPrefs;
    private static final int MAX_MANUAL = 30;
    private JSONObject pending;   // waiting for Android's permission dialog
    private long connectingId;    // the location being connected right now
    private ObjectAnimator pulse;
    private ObjectAnimator gpsPulse;
    private View pageHome, pageList, navHome, navList;
    private TextView gpsSub;
    private View gpsHalo, gpsIco;
    private android.widget.Switch gpsAuto;
    private String pulseMode = "";

    private final BroadcastReceiver stateReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context c, Intent i) {
            ui.postDelayed(MainActivity.this::render, 250);
        }
    };

    private final Runnable tick = new Runnable() {
        @Override
        public void run() {
            updateTimer();
            ui.postDelayed(this, 1000);
        }
    };

    @Override
    protected void onCreate(Bundle saved) {
        super.onCreate(saved);
        setContentView(R.layout.activity_main);
        prefs = getSharedPreferences("zax", MODE_PRIVATE);
        manualPrefs = getSharedPreferences("zax_manual", MODE_PRIVATE);

        loginPanel = findViewById(R.id.login_panel);
        mainPanel = findViewById(R.id.main_panel);
        empty = findViewById(R.id.empty);
        none = findViewById(R.id.none);
        power = findViewById(R.id.power);
        halo = findViewById(R.id.halo);
        ring = findViewById(R.id.ring);
        disc = findViewById(R.id.disc);
        selected = findViewById(R.id.selected);
        inLogin = findViewById(R.id.in_login);
        inPass = findViewById(R.id.in_pass);
        search = findViewById(R.id.search);
        loginError = findViewById(R.id.login_error);
        hello = findViewById(R.id.hello);
        statusTitle = findViewById(R.id.status_title);
        statusSub = findViewById(R.id.status_sub);
        timer = findViewById(R.id.timer);
        message = findViewById(R.id.message);
        selFlag = findViewById(R.id.sel_flag);
        selTitle = findViewById(R.id.sel_title);
        selSub = findViewById(R.id.sel_sub);
        selLabel = findViewById(R.id.sel_label);
        count = findViewById(R.id.count);
        btnLogin = findViewById(R.id.btn_login);
        list = findViewById(R.id.list);

        btnLogin.setOnClickListener(v -> signIn());
        eye(inPass);
        inPass.setOnEditorActionListener((v, action, e) -> {
            if (action == EditorInfo.IME_ACTION_DONE) {
                signIn();
                return true;
            }
            return false;
        });
        findViewById(R.id.link_register).setOnClickListener(v -> open("/panel/register/"));
        findViewById(R.id.link_forgot).setOnClickListener(v -> open("/panel/forgot/"));
        findViewById(R.id.btn_buy).setOnClickListener(v -> open("/panel/buy/"));
        findViewById(R.id.btn_add).setOnClickListener(v -> showAddManual());
        pageHome = findViewById(R.id.page_home);
        pageList = findViewById(R.id.page_list);
        navHome = findViewById(R.id.nav_home);
        navList = findViewById(R.id.nav_list);
        navHome.setOnClickListener(v -> showPage(false));
        navList.setOnClickListener(v -> showPage(true));
        findViewById(R.id.nav_gps).setOnClickListener(v -> startActivity(new Intent(this, GpsActivity.class)));
        selected.setOnClickListener(v -> showPage(true)); // tap the chosen location to change it
        // On shorter phones the one-line explanation goes, so the home page always fits without scrolling.
        if (!getResources().getBoolean(R.bool.roomy)) findViewById(R.id.gps_auto_sub).setVisibility(View.GONE);
        showPage(false);
        gpsSub = findViewById(R.id.gps_sub);
        gpsHalo = findViewById(R.id.gps_halo);
        gpsIco = findViewById(R.id.gps_ico);
        gpsAuto = findViewById(R.id.gps_auto);
        findViewById(R.id.gps_card).setOnClickListener(v -> startActivity(new Intent(this, GpsActivity.class)));
        findViewById(R.id.gps_open).setOnClickListener(v -> startActivity(new Intent(this, GpsActivity.class)));
        gpsAuto.setChecked(manualPrefs.getBoolean("gps_auto", false));
        gpsAuto.setOnCheckedChangeListener((b, on) -> {
            manualPrefs.edit().putBoolean("gps_auto", on).apply();
            String[] st = state();
            if (on && st != null) followGps(byId(activeId(st))); // already connected: match now
        });
        findViewById(R.id.btn_refresh).setOnClickListener(v -> {
            v.animate().rotationBy(360).setDuration(600).start();
            refresh(true);
        });
        findViewById(R.id.btn_logout).setOnClickListener(v -> signOut());
        power.setOnClickListener(v -> togglePower());
        search.addTextChangedListener(new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int a, int b, int c) {
            }

            @Override
            public void onTextChanged(CharSequence s, int a, int b, int c) {
                renderList(state());
            }

            @Override
            public void afterTextChanged(Editable s) {
            }
        });

        try {
            proxies = new JSONArray(prefs.getString("proxies", "[]"));
            expired = new JSONArray(prefs.getString("expired", "[]"));
            manual = new JSONArray(manualPrefs.getString("list", "[]"));
        } catch (Exception ignored) {
        }
        if (Build.VERSION.SDK_INT >= 33 && checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(new String[]{Manifest.permission.POST_NOTIFICATIONS}, 11);
        }
    }

    @Override
    protected void onResume() {
        super.onResume();
        IntentFilter f = new IntentFilter(TunnelService.ACTION_STATE);
        f.addAction(MockService.ACTION_STATE);
        if (Build.VERSION.SDK_INT >= 33) {
            registerReceiver(stateReceiver, f, Context.RECEIVER_NOT_EXPORTED);
        } else {
            registerReceiver(stateReceiver, f);
        }
        render();
        ui.post(tick);
        if (token() != null) {
            refresh(false);
        }
    }

    @Override
    protected void onPause() {
        unregisterReceiver(stateReceiver);
        ui.removeCallbacks(tick);
        super.onPause();
    }

    private String token() {
        return prefs.getString("token", null);
    }

    private void open(String path) {
        try {
            startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(BuildConfig.SITE + path)));
        } catch (Exception ignored) {
        }
    }

    /* ---------- Sign in / out ---------- */

    private void signIn() {
        final String login = inLogin.getText().toString().trim();
        final String pass = inPass.getText().toString();
        if (login.isEmpty() || pass.isEmpty()) {
            showLoginError(getString(R.string.email) + " + " + getString(R.string.password));
            return;
        }
        btnLogin.setEnabled(false);
        btnLogin.setText(R.string.signing_in);
        loginError.setVisibility(View.GONE);
        io.execute(() -> {
            try {
                final Api.Result r = Api.login(login, pass, Build.MANUFACTURER + " " + Build.MODEL);
                ui.post(() -> {
                    btnLogin.setEnabled(true);
                    btnLogin.setText(R.string.sign_in);
                    if (r.ok()) {
                        inPass.setText("");
                        inPass.setTransformationMethod(android.text.method.PasswordTransformationMethod.getInstance());
                        inPass.setCompoundDrawablesWithIntrinsicBounds(0, 0, R.drawable.ic_eye, 0);
                        prefs.edit().putString("token", r.json.optString("token")).apply();
                        store(r.json);
                        render();
                    } else {
                        showLoginError(r.error(getString(R.string.offline)));
                    }
                });
            } catch (Exception e) {
                ui.post(() -> {
                    btnLogin.setEnabled(true);
                    btnLogin.setText(R.string.sign_in);
                    showLoginError(getString(R.string.offline));
                });
            }
        });
    }

    private void showLoginError(String text) {
        loginError.setText(text);
        loginError.setVisibility(View.VISIBLE);
    }

    private void signOut() {
        final String t = token();
        disconnect();
        if (MockService.running) GpsActivity.end(this);
        prefs.edit().clear().apply();
        proxies = new JSONArray();
        expired = new JSONArray();
        showPage(false);
        search.setText("");
        render();
        if (t != null) {
            io.execute(() -> {
                try {
                    Api.logout(t);
                } catch (Exception ignored) {
                }
            });
        }
    }

    /* ---------- Data ---------- */

    private void store(JSONObject json) {
        JSONArray arr = json.optJSONArray("proxies");
        proxies = arr == null ? new JSONArray() : arr;
        JSONArray old = json.optJSONArray("expired");
        expired = old == null ? new JSONArray() : old;
        JSONObject user = json.optJSONObject("user");
        // The proxy in use has run out or was removed: stop using it.
        String[] st = state();
        if (st != null && activeId(st) > 0 && byId(activeId(st)) == null) {
            startService(new Intent(this, TunnelService.class).setAction(TunnelService.ACTION_DISCONNECT));
            showMessage(getString(R.string.expired_now));
        }
        prefs.edit()
                .putString("proxies", proxies.toString())
                .putString("expired", expired.toString())
                .putString("name", user == null ? "" : user.optString("name"))
                .apply();
    }

    private void refresh(boolean showErrors) {
        final String t = token();
        if (t == null) return;
        io.execute(() -> {
            try {
                final Api.Result r = Api.proxies(t);
                ui.post(() -> {
                    if (r.ok()) {
                        store(r.json);
                        showMessage(null);
                        render();
                    } else if (r.status == 401) {
                        signOut();
                    } else if (showErrors) {
                        showMessage(r.error(getString(R.string.offline)));
                    }
                });
            } catch (Exception e) {
                if (showErrors) ui.post(() -> showMessage(getString(R.string.offline)));
            }
        });
    }

    private void showMessage(String text) {
        message.setVisibility(text == null ? View.GONE : View.VISIBLE);
        if (text != null) message.setText(text);
    }

    private JSONObject byId(long id) {
        for (int i = 0; i < proxies.length(); i++) {
            JSONObject p = proxies.optJSONObject(i);
            if (p != null && p.optLong("id") == id) return p;
        }
        for (int i = 0; i < manual.length(); i++) {
            JSONObject p = manual.optJSONObject(i);
            if (p != null && p.optLong("id") == id) return p;
        }
        return null;
    }

    /** The location the big button uses: the one last tapped, else the first in the list. */
    private JSONObject chosen() {
        JSONObject p = byId(prefs.getLong("selected", 0));
        return p != null ? p : proxies.length() > 0 ? proxies.optJSONObject(0) : manual.optJSONObject(0);
    }

    /* ---------- Connection ---------- */

    /** [id, label, started-at] while connected, otherwise null. */
    private String[] state() {
        try (FileInputStream in = new FileInputStream(new File(getFilesDir(), TunnelService.STATE_FILE))) {
            byte[] buf = new byte[512];
            int n = in.read(buf);
            String s = n > 0 ? new String(buf, 0, n, StandardCharsets.UTF_8) : "";
            if (s.isEmpty() || !vpnActive()) return null;
            String[] parts = s.split("\\|", 3);
            return new String[]{parts[0], parts.length > 1 ? parts[1] : "", parts.length > 2 ? parts[2] : "0"};
        } catch (Exception e) {
            return null;
        }
    }

    private long activeId(String[] st) {
        try {
            return st == null ? 0 : Long.parseLong(st[0]);
        } catch (NumberFormatException e) {
            return 0;
        }
    }

    private boolean vpnActive() {
        ConnectivityManager cm = (ConnectivityManager) getSystemService(CONNECTIVITY_SERVICE);
        if (cm == null) return false;
        for (Network n : cm.getAllNetworks()) {
            NetworkCapabilities caps = cm.getNetworkCapabilities(n);
            if (caps != null && caps.hasTransport(NetworkCapabilities.TRANSPORT_VPN)) return true;
        }
        return false;
    }

    private void togglePower() {
        if (connectingId != 0) return;
        if (state() != null) {
            disconnect();
            return;
        }
        JSONObject p = chosen();
        if (p == null) {
            Toast.makeText(this, R.string.choose_first, Toast.LENGTH_SHORT).show();
            return;
        }
        connect(p);
    }

    /** A tap on a location: remember it, and move the live connection to it if there is one. */
    private void pick(JSONObject p) {
        if (connectingId != 0) return;
        prefs.edit().putLong("selected", p.optLong("id")).apply();
        String[] st = state();
        if (st != null && activeId(st) != p.optLong("id")) {
            Toast.makeText(this, getString(R.string.switching, p.optString("country")), Toast.LENGTH_SHORT).show();
            connect(p);
        } else {
            render();
        }
        showPage(false);
    }

    private void connect(final JSONObject p) {
        Intent ask = VpnService.prepare(this);
        if (ask != null) {
            pending = p;
            startActivityForResult(ask, REQ_VPN); // Android's own "Connection request" dialog
            return;
        }
        prefs.edit().putLong("selected", p.optLong("id")).apply();
        connectingId = p.optLong("id");
        showMessage(null);
        long wait = 0;
        if (state() != null) {
            // Switching: end the running connection first, then start the new one.
            startService(new Intent(this, TunnelService.class).setAction(TunnelService.ACTION_DISCONNECT));
            wait = 1100;
        }
        render();
        ui.postDelayed(() -> {
            Intent i = new Intent(this, TunnelService.class).setAction(TunnelService.ACTION_CONNECT)
                    .putExtra("id", p.optLong("id"))
                    .putExtra("host", p.optString("ip"))
                    .putExtra("port", p.optInt("port"))
                    .putExtra("user", p.optString("username"))
                    .putExtra("pass", p.optString("password"))
                    .putExtra("label", p.optString("flag") + " " + (p.optString("place").isEmpty() ? p.optString("country") : p.optString("place") + ", " + p.optString("country")) + " · " + p.optString("ip"));
            try {
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                    startForegroundService(i);
                } else {
                    startService(i);
                }
            } catch (Exception e) {
                connectingId = 0;
                showMessage(getString(R.string.vpn_denied));
                render();
                return;
            }
            if (manualPrefs.getBoolean("gps_auto", false)) followGps(p);
            ui.postDelayed(() -> {
                connectingId = 0;
                render();
            }, 1800);
        }, wait);
    }

    private void disconnect() {
        if (state() == null) return;
        startService(new Intent(this, TunnelService.class).setAction(TunnelService.ACTION_DISCONNECT));
        // The GPS followed the proxy, so it goes back to the real location with it.
        if (manualPrefs.getBoolean("gps_auto", false) && MockService.running) GpsActivity.end(this);
        ui.postDelayed(this::render, 700);
    }

    @Override
    protected void onActivityResult(int req, int result, Intent data) {
        super.onActivityResult(req, result, data);
        if (req == REQ_VPN) {
            JSONObject p = pending;
            pending = null;
            if (result == RESULT_OK && p != null) {
                connect(p);
            } else {
                showMessage(getString(R.string.vpn_denied));
            }
        }
    }

    /* ---------- Screen ---------- */

    private void render() {
        boolean in = token() != null;
        loginPanel.setVisibility(in ? View.GONE : View.VISIBLE);
        mainPanel.setVisibility(in ? View.VISIBLE : View.GONE);
        if (!in) {
            stopPulse();
            return;
        }
        String[] st = state();
        long active = activeId(st);
        String name = prefs.getString("name", "");
        hello.setText(name.isEmpty() ? getString(R.string.tagline) : getString(R.string.hello, name));

        String mode = st != null ? "on" : (connectingId != 0 ? "busy" : "idle");
        disc.setBackgroundResource("on".equals(mode) ? R.drawable.power_on : "busy".equals(mode) ? R.drawable.power_busy : R.drawable.power_idle);
        ring.setBackgroundResource("on".equals(mode) ? R.drawable.ring_on : "busy".equals(mode) ? R.drawable.ring_busy : R.drawable.ring_idle);
        halo.setBackgroundResource("on".equals(mode) ? R.drawable.halo_on : "busy".equals(mode) ? R.drawable.halo_busy : R.drawable.halo_idle);
        startPulse(mode);
        power.setContentDescription(getString(st != null ? R.string.disconnect : R.string.connect));

        JSONObject shown = st != null ? byId(active) : (connectingId != 0 ? byId(connectingId) : chosen());
        if (st != null) {
            statusTitle.setText(R.string.protected_title);
            statusTitle.setTextColor(getColor(R.color.ok));
            statusSub.setText(R.string.tap_disconnect);
        } else if (connectingId != 0) {
            statusTitle.setText(R.string.connecting);
            statusTitle.setTextColor(getColor(R.color.warn));
            statusSub.setText(shown == null ? "" : shown.optString("country"));
        } else {
            statusTitle.setText(R.string.not_connected);
            statusTitle.setTextColor(getColor(R.color.text));
            statusSub.setText(proxies.length() + manual.length() == 0 ? R.string.empty_sub_manual : R.string.tap_connect);
        }
        timer.setVisibility(st != null ? View.VISIBLE : View.GONE);
        updateTimer();

        selected.setVisibility(shown == null ? View.GONE : View.VISIBLE);
        if (shown != null) {
            selLabel.setText(st != null ? R.string.your_ip : R.string.selected_location);
            selFlag.setText(shown.optString("flag"));
            String place = shown.optString("place");
            selTitle.setText(place.isEmpty() ? shown.optString("country") : place);
            selSub.setText((place.isEmpty() ? shown.optString("type") : shown.optString("country")) + " · " + shown.optString("ip") + (shown.optLong("id") < 0 ? ":" + shown.optInt("port") : ""));
        }

        renderGps();
        count.setText(String.valueOf(proxies.length() + manual.length()));
        search.setVisibility(proxies.length() >= SEARCH_FROM ? View.VISIBLE : View.GONE);
        empty.setVisibility(proxies.length() + manual.length() == 0 ? View.VISIBLE : View.GONE);
        renderList(st);
    }

    private void renderList(String[] st) {
        long active = activeId(st);
        JSONObject sel = chosen();
        long selId = sel == null ? 0 : sel.optLong("id");
        String q = search.getVisibility() == View.VISIBLE ? search.getText().toString().trim().toLowerCase(Locale.ROOT) : "";
        list.removeAllViews();
        LayoutInflater inf = LayoutInflater.from(this);
        int shownRows = 0;
        for (int i = 0; i < proxies.length(); i++) {
            final JSONObject p = proxies.optJSONObject(i);
            if (p == null) continue;
            if (!q.isEmpty() && !(p.optString("country") + " " + p.optString("place") + " " + p.optString("type") + " " + p.optString("ip")).toLowerCase(Locale.ROOT).contains(q)) {
                continue;
            }
            shownRows++;
            long id = p.optLong("id");
            boolean on = st != null && id == active;
            boolean picked = !on && id == selId && st == null;
            boolean busy = connectingId == id;
            View row = inf.inflate(R.layout.row_proxy, list, false);
            row.setBackgroundResource(on ? R.drawable.bg_row_on : (picked || busy) ? R.drawable.bg_row_sel : R.drawable.bg_row);
            ((TextView) row.findViewById(R.id.flag)).setText(p.optString("flag"));
            ((TextView) row.findViewById(R.id.title)).setText(p.optString("country"));
            String where = p.optString("place");
            ((TextView) row.findViewById(R.id.sub)).setText(where.isEmpty() ? p.optString("ip") : where + " · " + p.optString("ip"));
            ((TextView) row.findViewById(R.id.type)).setText(p.optString("type"));
            TextView left = row.findViewById(R.id.left);
            boolean expiring = "expiring".equals(p.optString("state"));
            left.setText(p.optString("left"));
            left.setTextColor(getColor(expiring ? R.color.warn : R.color.ok));
            left.setBackgroundResource(expiring ? R.drawable.bg_chip_warn : R.drawable.bg_chip_ok);
            ImageView mark = row.findViewById(R.id.mark);
            TextView state = row.findViewById(R.id.state);
            if (on) {
                mark.setBackgroundResource(R.drawable.dot_on);
                mark.setImageResource(R.drawable.ic_check);
                state.setText(R.string.state_connected);
                state.setTextColor(getColor(R.color.ok));
            } else if (busy) {
                mark.setBackgroundResource(R.drawable.dot_sel);
                state.setText(R.string.connecting);
                state.setTextColor(getColor(R.color.warn));
            } else if (picked) {
                mark.setBackgroundResource(R.drawable.dot_sel);
                mark.setImageResource(R.drawable.ic_check);
                state.setText(R.string.state_selected);
                state.setTextColor(0xFFB3AAFF);
            } else {
                state.setText("");
            }
            row.setOnClickListener(v -> pick(p));
            list.addView(row);
        }
        // Proxies typed in by hand.
        int manualRows = 0;
        for (int i = 0; i < manual.length(); i++) {
            final JSONObject p = manual.optJSONObject(i);
            if (p == null) continue;
            String addr = p.optString("ip") + ":" + p.optInt("port");
            if (!q.isEmpty() && !(p.optString("country") + " " + addr + " manual").toLowerCase(Locale.ROOT).contains(q)) continue;
            if (manualRows++ == 0) list.addView(sectionHead(getString(R.string.manual_head, manual.length())));
            shownRows++;
            long id = p.optLong("id");
            boolean on = st != null && id == active;
            boolean picked = !on && id == selId && st == null;
            boolean busy = connectingId == id;
            View row = inf.inflate(R.layout.row_proxy, list, false);
            row.setBackgroundResource(on ? R.drawable.bg_row_on : (picked || busy) ? R.drawable.bg_row_sel : R.drawable.bg_row);
            ((TextView) row.findViewById(R.id.flag)).setText("✏️");
            ((TextView) row.findViewById(R.id.title)).setText(p.optString("country"));
            ((TextView) row.findViewById(R.id.sub)).setText(addr);
            ((TextView) row.findViewById(R.id.type)).setText(R.string.manual_type);
            TextView remove = row.findViewById(R.id.left);
            remove.setText("✕ " + getString(R.string.manual_remove));
            remove.setTextColor(getColor(R.color.muted));
            remove.setBackgroundResource(R.drawable.bg_chip);
            remove.setOnClickListener(v -> askRemove(p));
            ImageView mark = row.findViewById(R.id.mark);
            TextView state = row.findViewById(R.id.state);
            if (on) {
                mark.setBackgroundResource(R.drawable.dot_on);
                mark.setImageResource(R.drawable.ic_check);
                state.setText(R.string.state_connected);
                state.setTextColor(getColor(R.color.ok));
            } else if (busy) {
                mark.setBackgroundResource(R.drawable.dot_sel);
                state.setText(R.string.connecting);
                state.setTextColor(getColor(R.color.warn));
            } else if (picked) {
                mark.setBackgroundResource(R.drawable.dot_sel);
                mark.setImageResource(R.drawable.ic_check);
                state.setText(R.string.state_selected);
                state.setTextColor(0xFFB3AAFF);
            } else {
                state.setText("");
            }
            row.setOnClickListener(v -> pick(p));
            list.addView(row);
        }
        none.setVisibility(shownRows == 0 && proxies.length() + manual.length() > 0 ? View.VISIBLE : View.GONE);

        // Expired proxies: shown for information only. They cannot be selected or connected.
        if (expired.length() > 0 && q.isEmpty()) {
            list.addView(sectionHead(getString(R.string.expired_head, expired.length())));
            for (int i = 0; i < expired.length(); i++) {
                JSONObject p = expired.optJSONObject(i);
                if (p == null) continue;
                View row = inf.inflate(R.layout.row_proxy, list, false);
                row.setAlpha(0.6f);
                ((TextView) row.findViewById(R.id.flag)).setText(p.optString("flag"));
                ((TextView) row.findViewById(R.id.title)).setText(p.optString("country"));
                String where = p.optString("place");
                ((TextView) row.findViewById(R.id.sub)).setText(where.isEmpty() ? p.optString("ip") : where + " · " + p.optString("ip"));
                ((TextView) row.findViewById(R.id.type)).setText(p.optString("type"));
                TextView left = row.findViewById(R.id.left);
                left.setText(getString(R.string.expired_on, p.optString("ended")));
                left.setTextColor(getColor(R.color.bad));
                left.setBackgroundResource(R.drawable.bg_chip_bad);
                row.findViewById(R.id.mark).setVisibility(View.INVISIBLE);
                TextView state = row.findViewById(R.id.state);
                state.setText(R.string.state_expired);
                state.setTextColor(getColor(R.color.bad));
                row.setOnClickListener(v -> {
                    Toast.makeText(this, R.string.expired_hint, Toast.LENGTH_LONG).show();
                    open("/panel/proxies/");
                });
                list.addView(row);
            }
        }
    }

    /** Puts an eye at the end of a password box: tap it to see what was typed, tap again to hide it. */
    @android.annotation.SuppressLint("ClickableViewAccessibility")
    private void eye(final EditText box) {
        box.setCompoundDrawablesWithIntrinsicBounds(0, 0, R.drawable.ic_eye, 0);
        box.setOnTouchListener((v, e) -> {
            float zone = 52 * getResources().getDisplayMetrics().density; // the eye and the space around it
            if (e.getX() < box.getWidth() - zone) return false;
            if (e.getAction() == android.view.MotionEvent.ACTION_UP) {
                boolean hidden = box.getTransformationMethod() instanceof android.text.method.PasswordTransformationMethod;
                int at = box.getSelectionEnd();
                box.setTransformationMethod(hidden ? android.text.method.HideReturnsTransformationMethod.getInstance() : android.text.method.PasswordTransformationMethod.getInstance());
                box.setCompoundDrawablesWithIntrinsicBounds(0, 0, hidden ? R.drawable.ic_eye_off : R.drawable.ic_eye, 0);
                box.setSelection(Math.max(0, Math.min(at, box.getText().length())));
            }
            return true;
        });
    }

    /* ---------- Pages ---------- */

    /** Home is a fixed screen; Locations is the list. */
    private void showPage(boolean list) {
        pageHome.setVisibility(list ? View.GONE : View.VISIBLE);
        pageList.setVisibility(list ? View.VISIBLE : View.GONE);
        tint(navHome, !list);
        tint(navList, list);
        tint(findViewById(R.id.nav_gps), false);
    }

    private void tint(View item, boolean active) {
        android.view.ViewGroup g = (android.view.ViewGroup) item;
        int c = active ? 0xFFB3AAFF : 0xFF7F89A3;
        ((ImageView) g.getChildAt(0)).setColorFilter(c);
        ((TextView) g.getChildAt(1)).setTextColor(c);
    }

    @Override
    public void onBackPressed() {
        if (mainPanel.getVisibility() == View.VISIBLE && pageList.getVisibility() == View.VISIBLE) {
            showPage(false);
            return;
        }
        super.onBackPressed();
    }

    /* ---------- GPS location ---------- */

    private void renderGps() {
        boolean on = MockService.running;
        String where = MockService.label == null || MockService.label.isEmpty() ? String.format(Locale.US, "%.4f, %.4f", MockService.lat, MockService.lng) : MockService.label;
        gpsSub.setText(on ? getString(R.string.gps_on, where) : getString(R.string.gps_off));
        gpsSub.setTextColor(getColor(on ? R.color.ok : R.color.text));
        gpsIco.setBackgroundResource(on ? R.drawable.bg_gps_ico_on : R.drawable.bg_gps_ico);
        gpsHalo.setBackgroundResource(on ? R.drawable.bg_gps_ico_on : R.drawable.bg_gps_ico);
        if (gpsPulse == null) {
            gpsPulse = ObjectAnimator.ofPropertyValuesHolder(gpsHalo,
                    PropertyValuesHolder.ofFloat(View.SCALE_X, 0.85f, 1.25f),
                    PropertyValuesHolder.ofFloat(View.SCALE_Y, 0.85f, 1.25f),
                    PropertyValuesHolder.ofFloat(View.ALPHA, 0.4f, 0f));
            gpsPulse.setDuration(1600);
            gpsPulse.setRepeatCount(ValueAnimator.INFINITE);
            gpsPulse.setInterpolator(new AccelerateDecelerateInterpolator());
            gpsPulse.start();
        }
    }

    /** Moves the phone's GPS to where a proxy is. Opens the map when the phone still needs setting up. */
    private void followGps(JSONObject p) {
        if (p == null) return;
        if (p.isNull("lat") || p.isNull("lng") || !p.has("lat")) {
            Toast.makeText(this, R.string.gps_no_position, Toast.LENGTH_LONG).show();
            return;
        }
        double lat = p.optDouble("lat"), lng = p.optDouble("lng");
        String place = p.optString("place");
        String label = place.isEmpty() ? p.optString("country") : place + ", " + p.optString("country");
        if (GpsActivity.mockAllowed(this) && GpsActivity.locationGranted(this)) {
            GpsActivity.begin(this, lat, lng, label);
            ui.postDelayed(this::render, 900);
        } else {
            startActivity(new Intent(this, GpsActivity.class).putExtra("lat", lat).putExtra("lng", lng).putExtra("label", label).putExtra("proxy", p.optLong("id")));
        }
    }

    private TextView sectionHead(String text) {
        TextView head = new TextView(this);
        head.setText(text);
        head.setTextColor(getColor(R.color.muted));
        head.setTextSize(13.5f);
        head.setTypeface(head.getTypeface(), android.graphics.Typeface.BOLD);
        int pad = (int) (getResources().getDisplayMetrics().density * 4);
        head.setPadding(pad, pad * 4, pad, pad * 2);
        return head;
    }

    /* ---------- Proxies typed in by hand ---------- */

    private void saveManual() {
        manualPrefs.edit().putString("list", manual.toString()).apply();
    }

    private void askRemove(final JSONObject p) {
        new android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
                .setTitle(p.optString("country"))
                .setMessage(R.string.manual_remove_ask)
                .setNegativeButton(R.string.cancel, null)
                .setPositiveButton(R.string.manual_remove, (d, w) -> {
                    long id = p.optLong("id");
                    if (activeId(state()) == id) disconnect();
                    JSONArray kept = new JSONArray();
                    for (int i = 0; i < manual.length(); i++) {
                        JSONObject x = manual.optJSONObject(i);
                        if (x != null && x.optLong("id") != id) kept.put(x);
                    }
                    manual = kept;
                    saveManual();
                    render();
                })
                .show();
    }

    /** "ip:port:user:pass" or "user:pass@ip:port" typed into the first box fills every box. */
    private static void spread(EditText host, EditText port, EditText user, EditText pass) {
        String v = host.getText().toString().trim().replaceFirst("^[A-Za-z0-9]+://", "");
        String cred = "";
        int at = v.lastIndexOf('@');
        if (at > 0) {
            cred = v.substring(0, at);
            v = v.substring(at + 1);
        }
        String[] parts = v.split(":", 4);
        if (parts.length < 2 || !parts[1].matches("\\d{1,5}")) return;
        host.setText(parts[0]);
        port.setText(parts[1]);
        if (!cred.isEmpty()) {
            int c = cred.indexOf(':');
            user.setText(c < 0 ? cred : cred.substring(0, c));
            pass.setText(c < 0 ? "" : cred.substring(c + 1));
        } else if (parts.length == 4) {
            user.setText(parts[2]);
            pass.setText(parts[3]);
        }
    }

    private void showAddManual() {
        final android.app.Dialog d = new android.app.Dialog(this);
        d.requestWindowFeature(android.view.Window.FEATURE_NO_TITLE);
        d.setContentView(R.layout.dialog_manual);
        if (d.getWindow() != null) {
            d.getWindow().setBackgroundDrawable(new android.graphics.drawable.ColorDrawable(0));
            d.getWindow().setLayout((int) (getResources().getDisplayMetrics().widthPixels * 0.94f), android.view.ViewGroup.LayoutParams.WRAP_CONTENT);
            d.getWindow().setSoftInputMode(android.view.WindowManager.LayoutParams.SOFT_INPUT_ADJUST_RESIZE);
        }
        final EditText host = d.findViewById(R.id.m_host), port = d.findViewById(R.id.m_port), user = d.findViewById(R.id.m_user), pass = d.findViewById(R.id.m_pass), name = d.findViewById(R.id.m_name);
        final TextView error = d.findViewById(R.id.m_error);
        eye(pass);
        final Button save = d.findViewById(R.id.m_save);
        host.setOnFocusChangeListener((v, has) -> {
            if (!has) spread(host, port, user, pass);
        });
        d.findViewById(R.id.m_cancel).setOnClickListener(v -> d.dismiss());
        save.setOnClickListener(v -> {
            spread(host, port, user, pass);
            final String h = host.getText().toString().trim(), u = user.getText().toString().trim(), pw = pass.getText().toString();
            int pt;
            try {
                pt = Integer.parseInt(port.getText().toString().trim());
            } catch (NumberFormatException e) {
                pt = 0;
            }
            int problem = 0;
            if (h.isEmpty() || h.length() > 253 || h.matches(".*[\\s/@].*")) problem = R.string.manual_bad_host;
            else if (pt < 1 || pt > 65535) problem = R.string.manual_bad_port;
            else if (u.isEmpty() != pw.isEmpty() || u.length() > 255 || pw.length() > 255) problem = R.string.manual_bad_auth;
            else if (manual.length() >= MAX_MANUAL) problem = R.string.manual_full;
            if (problem != 0) {
                error.setText(problem);
                error.setVisibility(View.VISIBLE);
                return;
            }
            final int portNo = pt;
            error.setVisibility(View.GONE);
            save.setEnabled(false);
            save.setText(R.string.manual_checking);
            io.execute(() -> {
                final int result = Api.checkSocks5(h, portNo, u, pw);
                ui.post(() -> {
                    if (!d.isShowing()) return;
                    save.setEnabled(true);
                    save.setText(R.string.manual_save);
                    if (result != 0) {
                        error.setText(result == 2 ? R.string.manual_rejected : result == 3 ? R.string.manual_not_socks : R.string.manual_unreachable);
                        error.setVisibility(View.VISIBLE);
                        return;
                    }
                    long id = -1;
                    for (int i = 0; i < manual.length(); i++) {
                        JSONObject x = manual.optJSONObject(i);
                        if (x != null && x.optLong("id") <= id) id = x.optLong("id") - 1;
                    }
                    String label = name.getText().toString().trim();
                    JSONObject p = new JSONObject();
                    try {
                        p.put("id", id).put("type", getString(R.string.manual_type)).put("country", label.isEmpty() ? getString(R.string.manual_default) : label)
                                .put("flag", "✏️").put("ip", h).put("place", "").put("port", portNo).put("username", u).put("password", pw);
                    } catch (Exception ignored) {
                    }
                    manual.put(p);
                    saveManual();
                    d.dismiss();
                    connect(p);
                });
            });
        });
        d.show();
    }

    private void updateTimer() {
        if (timer.getVisibility() != View.VISIBLE) return;
        String[] st = state();
        if (st == null) {
            render();
            return;
        }
        long start;
        try {
            start = Long.parseLong(st[2]);
        } catch (NumberFormatException e) {
            start = 0;
        }
        long s = start > 0 ? Math.max(0, (System.currentTimeMillis() - start) / 1000) : 0;
        timer.setText(String.format(Locale.US, "%02d:%02d:%02d", s / 3600, (s % 3600) / 60, s % 60));
    }

    /** The soft ring around the button breathes: slowly when connected, quickly while connecting. */
    private void startPulse(String mode) {
        if (mode.equals(pulseMode) && pulse != null) return;
        stopPulse();
        pulseMode = mode;
        float to = "idle".equals(mode) ? 1.04f : 1.12f;
        pulse = ObjectAnimator.ofPropertyValuesHolder(halo,
                PropertyValuesHolder.ofFloat(View.SCALE_X, 0.9f, to),
                PropertyValuesHolder.ofFloat(View.SCALE_Y, 0.9f, to),
                PropertyValuesHolder.ofFloat(View.ALPHA, "idle".equals(mode) ? 0.16f : 0.26f, 0.05f));
        pulse.setDuration("busy".equals(mode) ? 700 : "on".equals(mode) ? 1800 : 2600);
        pulse.setRepeatCount(ValueAnimator.INFINITE);
        pulse.setRepeatMode(ValueAnimator.REVERSE);
        pulse.setInterpolator(new AccelerateDecelerateInterpolator());
        pulse.start();
    }

    private void stopPulse() {
        if (pulse != null) {
            pulse.cancel();
            pulse = null;
        }
        pulseMode = "";
    }
}
