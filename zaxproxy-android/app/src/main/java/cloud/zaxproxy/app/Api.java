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
            c.setRequestProperty("User-Agent", "ZaxProxy-Android/" + BuildConfig.VERSION_NAME);
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
}
