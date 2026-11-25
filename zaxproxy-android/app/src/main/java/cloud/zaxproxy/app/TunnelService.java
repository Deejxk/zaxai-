package cloud.zaxproxy.app;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.content.pm.ServiceInfo;
import android.net.VpnService;
import android.os.Build;
import android.os.ParcelFileDescriptor;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.nio.charset.StandardCharsets;

/**
 * Sends the phone's traffic to the chosen proxy.
 *
 * Android hands this service a virtual network interface; the open-source engine
 * (hev-socks5-tunnel) reads the packets from it and forwards every connection to the
 * proxy over SOCKS5 with the customer's username and password. Website names are
 * resolved through the proxy too, so nothing is looked up outside it.
 *
 * The service lives in its own process (see the manifest) and ends that process when
 * it stops, which is how the engine is shut down cleanly.
 */
public class TunnelService extends VpnService {

    private static native boolean TProxyStartService(String configPath, int fd);
    private static native boolean TProxyStopService();
    private static native boolean TProxyIsRunning();
    private static native long[] TProxyGetStats();

    public static final String ACTION_CONNECT = "cloud.zaxproxy.app.CONNECT";
    public static final String ACTION_DISCONNECT = "cloud.zaxproxy.app.DISCONNECT";
    public static final String ACTION_STATE = "cloud.zaxproxy.app.STATE";
    public static final String STATE_FILE = "tunnel.state";

    private static final String CHANNEL = "connection";
    private static final int NOTIF_ID = 5;
    private static final int MTU = 8500;
    private static final String MAPPED_DNS = "198.18.0.2";

    static {
        System.loadLibrary("hev-socks5-tunnel");
    }

    private ParcelFileDescriptor tun;

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        if (intent == null || ACTION_DISCONNECT.equals(intent.getAction())) {
            stopTunnel();
            return START_NOT_STICKY;
        }
        if (tun != null) {
            stopEngineOnly(); // switching to another proxy
        }
        startTunnel(intent);
        return START_NOT_STICKY;
    }

    @Override
    public void onRevoke() {
        stopTunnel(); // the user turned the connection off from Android's own settings
        super.onRevoke();
    }

    @Override
    public void onDestroy() {
        writeState(this, "");
        super.onDestroy();
    }

    private void startTunnel(Intent in) {
        String host = in.getStringExtra("host");
        int port = in.getIntExtra("port", 0);
        String user = in.getStringExtra("user");
        String pass = in.getStringExtra("pass");
        String label = in.getStringExtra("label");
        long id = in.getLongExtra("id", 0);
        if (host == null || port <= 0) {
            stopTunnel();
            return;
        }

        Builder b = new Builder();
        b.setBlocking(false);
        b.setMtu(MTU);
        b.setSession("ZaxProxy Connect");
        b.addAddress("198.18.0.1", 32);
        b.addRoute("0.0.0.0", 0);
        b.addAddress("fc00::1", 128);
        b.addRoute("::", 0);
        b.addDnsServer(MAPPED_DNS);
        try {
            b.addDisallowedApplication(getPackageName()); // the app's own sign-in calls go out directly
        } catch (PackageManager.NameNotFoundException ignored) {
        }
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            b.setMetered(false);
        }
        tun = b.establish();
        if (tun == null) {
            stopTunnel();
            return;
        }

        File conf = new File(getCacheDir(), "tunnel.conf");
        String text = "misc:\n"
                + "  task-stack-size: 81920\n"
                + "tunnel:\n"
                + "  mtu: " + MTU + "\n"
                + "  icmp: 'reply'\n"
                + "socks5:\n"
                + "  port: " + port + "\n"
                + "  address: '" + q(host) + "'\n"
                + "  udp: 'tcp'\n";
        if (user != null && !user.isEmpty()) {
            text += "  username: '" + q(user) + "'\n"
                    + "  password: '" + q(pass == null ? "" : pass) + "'\n";
        }
        text += "mapdns:\n"
                + "  address: " + MAPPED_DNS + "\n"
                + "  port: 53\n"
                + "  network: 240.0.0.0\n"
                + "  netmask: 240.0.0.0\n"
                + "  cache-size: 10000\n";
        try (FileOutputStream out = new FileOutputStream(conf, false)) {
            out.write(text.getBytes(StandardCharsets.UTF_8));
        } catch (IOException e) {
            stopTunnel();
            return;
        }
        conf.setReadable(false, false);
        conf.setReadable(true, true);

        TProxyStartService(conf.getAbsolutePath(), tun.getFd());
        writeState(this, id + "|" + (label == null ? "" : label.replace("|", " ")) + "|" + System.currentTimeMillis());
        showNotification(label == null ? host : label);
        announce();
    }

    /** YAML single-quoted text: a quote is written twice. */
    private static String q(String s) {
        return s.replace("'", "''").replace("\n", "").replace("\r", "");
    }

    private void stopEngineOnly() {
        try {
            TProxyStopService();
        } catch (Throwable ignored) {
        }
        try {
            if (tun != null) tun.close();
        } catch (IOException ignored) {
        }
        tun = null;
    }

    private void stopTunnel() {
        stopEngineOnly();
        writeState(this, "");
        new File(getCacheDir(), "tunnel.conf").delete(); // do not leave the proxy password on disk
        announce();
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
            stopForeground(STOP_FOREGROUND_REMOVE);
        } else {
            stopForeground(true);
        }
        stopSelf();
        System.exit(0); // this process only: the engine is not designed to be restarted in place
    }

    private void announce() {
        sendBroadcast(new Intent(ACTION_STATE).setPackage(getPackageName()));
    }

    static void writeState(Context c, String value) {
        try (FileOutputStream out = new FileOutputStream(new File(c.getFilesDir(), STATE_FILE), false)) {
            out.write(value.getBytes(StandardCharsets.UTF_8));
        } catch (IOException ignored) {
        }
    }

    private void showNotification(String label) {
        NotificationManager nm = (NotificationManager) getSystemService(NOTIFICATION_SERVICE);
        Notification.Builder nb;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel ch = new NotificationChannel(CHANNEL, getString(R.string.notif_channel), NotificationManager.IMPORTANCE_LOW);
            ch.setShowBadge(false);
            nm.createNotificationChannel(ch);
            nb = new Notification.Builder(this, CHANNEL);
        } else {
            nb = new Notification.Builder(this);
        }
        int immutable = Build.VERSION.SDK_INT >= Build.VERSION_CODES.M ? PendingIntent.FLAG_IMMUTABLE : 0;
        PendingIntent open = PendingIntent.getActivity(this, 0, new Intent(this, MainActivity.class), immutable);
        PendingIntent stop = PendingIntent.getService(this, 1, new Intent(this, TunnelService.class).setAction(ACTION_DISCONNECT), immutable);
        nb.setSmallIcon(R.drawable.ic_notif)
                .setContentTitle(getString(R.string.notif_title))
                .setContentText(label)
                .setContentIntent(open)
                .setOngoing(true)
                .setColor(0xFF6D5EFC)
                .addAction(new Notification.Action.Builder(null, getString(R.string.disconnect), stop).build());
        Notification n = nb.build();
        if (Build.VERSION.SDK_INT >= 34) {
            startForeground(NOTIF_ID, n, ServiceInfo.FOREGROUND_SERVICE_TYPE_SYSTEM_EXEMPTED);
        } else {
            startForeground(NOTIF_ID, n);
        }
    }
}
