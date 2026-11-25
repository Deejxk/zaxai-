package cloud.zaxproxy.app;

import org.json.JSONObject;

import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;

/** Talks to the ZaxProxy website. Always HTTPS; the account password is sent once, at sign-in only. */
final class Api {

    static final class Result {
        final int status;
        final JSONObject json;

        Result(int status, JSONObject json) {
            this.status = status;
            this.json = json;
        }

        boolean ok() {
            return json != null && json.optBoolean("ok", false);
        }

        String error(String fallback) {
            String e = json == null ? "" : json.optString("error", "");
            return e.isEmpty() ? fallback : e;
        }
    }

    private Api() {
    }

    static Result login(String login, String password, String device) throws Exception {
        JSONObject body = new JSONObject();
        body.put("login", login);
        body.put("password", password);
        body.put("device", device);
        return call("POST", "/zp-app/login/", null, body);
    }

    static Result proxies(String token) throws Exception {
        return call("GET", "/zp-app/proxies/", token, null);
    }

    static Result logout(String token) throws Exception {
        return call("POST", "/zp-app/logout/", token, new JSONObject());
    }

    private static Result call(String method, String path, String token, JSONObject body) throws Exception {
        HttpURLConnection c = (HttpURLConnection) new URL(BuildConfig.SITE + path).openConnection();
        try {
            c.setRequestMethod(method);
            c.setConnectTimeout(15000);
            c.setReadTimeout(25000);
            c.setInstanceFollowRedirects(false);
            c.setRequestProperty("Accept", "application/json");
            c.setRequestProperty("User-Agent", "ZaxProxyConnect-Android/" + BuildConfig.VERSION_NAME);
            if (token != null) {
                c.setRequestProperty("X-ZP-Token", token);
            }
            if (body != null) {
                byte[] bytes = body.toString().getBytes(StandardCharsets.UTF_8);
                c.setDoOutput(true);
                c.setRequestProperty("Content-Type", "application/json; charset=utf-8");
                try (OutputStream out = c.getOutputStream()) {
                    out.write(bytes);
                }
            }
            int status = c.getResponseCode();
            InputStream in = status >= 400 ? c.getErrorStream() : c.getInputStream();
            String text = in == null ? "" : read(in);
            JSONObject json = null;
            try {
                json = new JSONObject(text);
            } catch (Exception ignored) {
            }
            return new Result(status, json);
        } finally {
            c.disconnect();
        }
    }

    private static String read(InputStream in) throws Exception {
        ByteArrayOutputStream out = new ByteArrayOutputStream();
        byte[] buf = new byte[8192];
        int n;
        while ((n = in.read(buf)) > 0) {
            out.write(buf, 0, n);
            if (out.size() > 4 * 1024 * 1024) break;
        }
        in.close();
        return out.toString("UTF-8");
    }

    /**
     * Asks a proxy the first two questions of SOCKS5 (hello, then username and password) so a
     * typing mistake is caught before it is saved. 0 = fine, 1 = no answer, 2 = wrong username or
     * password, 3 = something answered that is not a SOCKS5 proxy.
     */
    static int checkSocks5(String host, int port, String user, String pass) {
        try (java.net.Socket s = new java.net.Socket()) {
            s.connect(new java.net.InetSocketAddress(host, port), 9000);
            s.setSoTimeout(9000);
            java.io.OutputStream out = s.getOutputStream();
            java.io.InputStream in = s.getInputStream();
            boolean auth = !user.isEmpty();
            out.write(auth ? new byte[]{5, 2, 0, 2} : new byte[]{5, 1, 0});
            out.flush();
            int ver = in.read(), method = in.read();
            if (ver != 5) return ver < 0 ? 1 : 3;
            if (method == 0) return 0;
            if (method != 2 || !auth) return 2;
            byte[] u = user.getBytes(java.nio.charset.StandardCharsets.UTF_8), p = pass.getBytes(java.nio.charset.StandardCharsets.UTF_8);
            java.io.ByteArrayOutputStream b = new java.io.ByteArrayOutputStream();
            b.write(1);
            b.write(u.length);
            b.write(u, 0, u.length);
            b.write(p.length);
            b.write(p, 0, p.length);
            out.write(b.toByteArray());
            out.flush();
            in.read();
            int status = in.read();
            return status == 0 ? 0 : status < 0 ? 1 : 2;
        } catch (Exception e) {
            return 1;
        }
    }
}
