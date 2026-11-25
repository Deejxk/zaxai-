package cloud.zaxproxy.app;

import android.Manifest;
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
import android.view.LayoutInflater;
import android.view.View;
import android.view.inputmethod.EditorInfo;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.TextView;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.File;
import java.io.FileInputStream;
import java.nio.charset.StandardCharsets;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class MainActivity extends Activity {

    private static final int REQ_VPN = 10;

    private final ExecutorService io = Executors.newSingleThreadExecutor();
    private final Handler ui = new Handler(Looper.getMainLooper());
    private SharedPreferences prefs;

    private View loginPanel, mainPanel, hero, empty;
    private EditText inLogin, inPass;
    private TextView loginError, hello, statusTitle, statusSub, message;
    private Button btnLogin, btnHero;
    private LinearLayout list;

    private JSONArray proxies = new JSONArray();
    private JSONObject pending;   // waiting for Android's permission dialog
    private long connectingId;    // row showing "Connecting…"

    private final BroadcastReceiver stateReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context c, Intent i) {
            ui.postDelayed(MainActivity.this::render, 250);
        }
    };

    @Override
    protected void onCreate(Bundle saved) {
        super.onCreate(saved);
        setContentView(R.layout.activity_main);
        prefs = getSharedPreferences("zax", MODE_PRIVATE);

        loginPanel = findViewById(R.id.login_panel);
        mainPanel = findViewById(R.id.main_panel);
        hero = findViewById(R.id.hero);
        empty = findViewById(R.id.empty);
        inLogin = findViewById(R.id.in_login);
        inPass = findViewById(R.id.in_pass);
        loginError = findViewById(R.id.login_error);
        hello = findViewById(R.id.hello);
        statusTitle = findViewById(R.id.status_title);
        statusSub = findViewById(R.id.status_sub);
        message = findViewById(R.id.message);
        btnLogin = findViewById(R.id.btn_login);
        btnHero = findViewById(R.id.btn_hero);
        list = findViewById(R.id.list);

        btnLogin.setOnClickListener(v -> signIn());
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
        findViewById(R.id.btn_refresh).setOnClickListener(v -> refresh(true));
        findViewById(R.id.btn_logout).setOnClickListener(v -> signOut());
        btnHero.setOnClickListener(v -> disconnect());

        try {
            proxies = new JSONArray(prefs.getString("proxies", "[]"));
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
        if (Build.VERSION.SDK_INT >= 33) {
            registerReceiver(stateReceiver, f, Context.RECEIVER_NOT_EXPORTED);
        } else {
            registerReceiver(stateReceiver, f);
        }
        render();
        if (token() != null) {
            refresh(false);
        }
    }

    @Override
    protected void onPause() {
        unregisterReceiver(stateReceiver);
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
        prefs.edit().clear().apply();
        proxies = new JSONArray();
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
        JSONObject user = json.optJSONObject("user");
        prefs.edit()
                .putString("proxies", proxies.toString())
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

    /* ---------- Connection ---------- */

    /** "id|label" while connected, otherwise empty. */
    private String[] state() {
        try (FileInputStream in = new FileInputStream(new File(getFilesDir(), TunnelService.STATE_FILE))) {
            byte[] buf = new byte[512];
            int n = in.read(buf);
            String s = n > 0 ? new String(buf, 0, n, StandardCharsets.UTF_8) : "";
            if (s.isEmpty() || !vpnActive()) return null;
            String[] parts = s.split("\\|", 2);
            return new String[]{parts[0], parts.length > 1 ? parts[1] : ""};
        } catch (Exception e) {
            return null;
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

    private void connect(JSONObject p) {
        Intent ask = VpnService.prepare(this);
        if (ask != null) {
            pending = p;
            startActivityForResult(ask, REQ_VPN); // Android's own "Connection request" dialog
            return;
        }
        connectingId = p.optLong("id");
        Intent i = new Intent(this, TunnelService.class).setAction(TunnelService.ACTION_CONNECT)
                .putExtra("id", p.optLong("id"))
                .putExtra("host", p.optString("ip"))
                .putExtra("port", p.optInt("port"))
                .putExtra("user", p.optString("username"))
                .putExtra("pass", p.optString("password"))
                .putExtra("label", p.optString("flag") + " " + p.optString("country") + " · " + p.optString("ip"));
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            startForegroundService(i);
        } else {
            startService(i);
        }
        render();
        ui.postDelayed(() -> {
            connectingId = 0;
            render();
        }, 2500);
    }

    private void disconnect() {
        if (state() == null) return;
        startService(new Intent(this, TunnelService.class).setAction(TunnelService.ACTION_DISCONNECT));
        ui.postDelayed(this::render, 600);
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
        if (!in) return;

        String[] st = state();
        long activeId = 0;
        try {
            activeId = st == null ? 0 : Long.parseLong(st[0]);
        } catch (NumberFormatException ignored) {
        }
        String name = prefs.getString("name", "");
        hello.setText(name.isEmpty() ? getString(R.string.app_name) : getString(R.string.hello, name));
        hero.setBackgroundResource(st != null ? R.drawable.bg_hero_on : R.drawable.bg_hero);
        btnHero.setVisibility(st != null ? View.VISIBLE : View.GONE);
        if (st != null) {
            statusTitle.setText(R.string.connected);
            statusSub.setText(st[1]);
        } else if (connectingId != 0) {
            statusTitle.setText(R.string.connecting);
            statusSub.setText("");
        } else {
            statusTitle.setText(R.string.not_connected);
            statusSub.setText(R.string.not_connected_sub);
        }

        list.removeAllViews();
        empty.setVisibility(proxies.length() == 0 ? View.VISIBLE : View.GONE);
        LayoutInflater inf = LayoutInflater.from(this);
        for (int i = 0; i < proxies.length(); i++) {
            final JSONObject p = proxies.optJSONObject(i);
            if (p == null) continue;
            View row = inf.inflate(R.layout.row_proxy, list, false);
            boolean on = st != null && p.optLong("id") == activeId;
            row.findViewById(R.id.row).setBackgroundResource(on ? R.drawable.bg_card_on : R.drawable.bg_card);
            ((TextView) row.findViewById(R.id.flag)).setText(p.optString("flag"));
            ((TextView) row.findViewById(R.id.title)).setText(p.optString("country") + " · " + p.optString("type"));
            ((TextView) row.findViewById(R.id.sub)).setText(p.optString("ip") + ":" + p.optInt("port"));
            TextView left = row.findViewById(R.id.left);
            left.setText(p.optString("left"));
            left.setTextColor(getColor("expiring".equals(p.optString("state")) ? R.color.warn : R.color.ok));
            Button action = row.findViewById(R.id.action);
            if (on) {
                action.setText(R.string.disconnect);
                action.setBackgroundResource(R.drawable.bg_btn_stop);
                action.setOnClickListener(v -> disconnect());
            } else if (connectingId == p.optLong("id")) {
                action.setText(R.string.connecting);
                action.setEnabled(false);
            } else {
                action.setText(R.string.connect);
                action.setOnClickListener(v -> connect(p));
            }
            list.addView(row);
        }
    }
}
