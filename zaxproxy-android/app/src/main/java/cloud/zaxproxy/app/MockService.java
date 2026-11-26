package cloud.zaxproxy.app;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.ServiceInfo;
import android.location.Criteria;
import android.location.Location;
import android.location.LocationManager;
import android.os.Build;
import android.os.Handler;
import android.os.HandlerThread;
import android.os.IBinder;
import android.os.SystemClock;

/**
 * Keeps the phone's location set to the chosen place.
 *
 * Android lets one app, chosen by the owner under Developer options ("Select mock location app"),
 * supply the location other apps receive. While this service runs it supplies the chosen place
 * once a second to the GPS, network and fused location sources. Stopping it hands the location
 * back to the real GPS.
 */
public class MockService extends Service {

    static final String ACTION_START = "cloud.zaxproxy.app.GPS_START";
    static final String ACTION_STOP = "cloud.zaxproxy.app.GPS_STOP";
    static final String ACTION_STATE = "cloud.zaxproxy.app.GPS_STATE";
    private static final String CHANNEL = "gps";
    private static final int NOTIF_ID = 8;

    /** Read by the screen (same process). */
    static volatile boolean running;
    static volatile double lat, lng;
    static volatile String label = "";
    static volatile long since;
    static volatile String lastError = "";

    private HandlerThread thread;
    private Handler handler;
    private LocationManager lm;
    private final java.util.List<String> providers = new java.util.ArrayList<>();

    private final Runnable push = new Runnable() {
        @Override
        public void run() {
            if (!running) return;
            for (String p : providers) {
                try {
                    lm.setTestProviderLocation(p, fix(p));
                } catch (Exception ignored) {
                    // one source refusing must not stop the others
                }
            }
            handler.postDelayed(this, 1000);
        }
    };

    private Location fix(String provider) {
        Location l = new Location(provider);
        l.setLatitude(lat);
        l.setLongitude(lng);
        l.setAltitude(12);
        l.setAccuracy(4f);
        l.setSpeed(0f);
        l.setBearing(0f);
        l.setTime(System.currentTimeMillis());
        l.setElapsedRealtimeNanos(SystemClock.elapsedRealtimeNanos());
        if (Build.VERSION.SDK_INT >= 26) {
            l.setVerticalAccuracyMeters(3f);
            l.setSpeedAccuracyMetersPerSecond(0.1f);
            l.setBearingAccuracyDegrees(1f);
        }
        return l;
    }

    @Override
    public void onCreate() {
        super.onCreate();
        lm = (LocationManager) getSystemService(Context.LOCATION_SERVICE);
        thread = new HandlerThread("zax-gps");
        thread.start();
        handler = new Handler(thread.getLooper());
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        String action = intent == null ? ACTION_START : intent.getAction();
        if (ACTION_STOP.equals(action)) {
            stopMock();
            stopSelf();
            return START_NOT_STICKY;
        }
        SharedPreferences prefs = getSharedPreferences("gps", MODE_PRIVATE);
        if (intent != null && intent.hasExtra("lat")) {
            lat = intent.getDoubleExtra("lat", 0);
            lng = intent.getDoubleExtra("lng", 0);
            label = String.valueOf(intent.getStringExtra("label"));
            prefs.edit().putString("lat", String.valueOf(lat)).putString("lng", String.valueOf(lng)).putString("label", label).apply();
        } else {
            // Restarted by Android after being cleared from memory: carry on with the same place.
            try {
                lat = Double.parseDouble(prefs.getString("lat", "0"));
                lng = Double.parseDouble(prefs.getString("lng", "0"));
            } catch (NumberFormatException e) {
                stopSelf();
                return START_NOT_STICKY;
            }
            label = prefs.getString("label", "");
        }

        try {
            if (Build.VERSION.SDK_INT >= 29) {
                startForeground(NOTIF_ID, notification(), ServiceInfo.FOREGROUND_SERVICE_TYPE_LOCATION);
            } else {
                startForeground(NOTIF_ID, notification());
            }
        } catch (Exception e) {
            fail("Android did not let the app keep running. Allow location for ZaxProxy Connect and try again.");
            return START_NOT_STICKY;
        }

        boolean was = running;
        if (!was) {
            providers.clear();
            addProvider(LocationManager.GPS_PROVIDER);
            addProvider(LocationManager.NETWORK_PROVIDER);
            if (Build.VERSION.SDK_INT >= 31) addProvider(LocationManager.FUSED_PROVIDER);
            if (providers.isEmpty()) {
                fail("mock"); // not chosen as the mock location app
                return START_NOT_STICKY;
            }
            since = System.currentTimeMillis();
        }
        lastError = "";
        running = true;
        prefs.edit().putBoolean("on", true).apply();
        handler.removeCallbacks(push);
        handler.post(push);
        ((NotificationManager) getSystemService(NOTIFICATION_SERVICE)).notify(NOTIF_ID, notification());
        announce();
        return START_STICKY;
    }

    private void addProvider(String name) {
        try {
            try {
                lm.removeTestProvider(name); // left over from an earlier run
            } catch (Exception ignored) {
            }
            lm.addTestProvider(name, false, false, false, false, true, true, true, Criteria.POWER_LOW, Criteria.ACCURACY_FINE);
            lm.setTestProviderEnabled(name, true);
            providers.add(name);
        } catch (Exception ignored) {
            // SecurityException: this app is not the chosen mock location app. Others: source missing on this phone.
        }
    }

    private void stopMock() {
        running = false;
        if (handler != null) handler.removeCallbacks(push);
        for (String p : providers) {
            try {
                lm.setTestProviderEnabled(p, false);
            } catch (Exception ignored) {
            }
            try {
                lm.removeTestProvider(p);
            } catch (Exception ignored) {
            }
        }
        providers.clear();
        getSharedPreferences("gps", MODE_PRIVATE).edit().putBoolean("on", false).apply();
        if (Build.VERSION.SDK_INT >= 24) {
            stopForeground(STOP_FOREGROUND_REMOVE);
        } else {
            stopForeground(true);
        }
        announce();
    }

    private void fail(String why) {
        lastError = why;
        stopMock();
        stopSelf();
    }

    private void announce() {
        sendBroadcast(new Intent(ACTION_STATE).setPackage(getPackageName()));
    }

    @Override
    public void onDestroy() {
        if (running) stopMock();
        thread.quitSafely();
        super.onDestroy();
    }

    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }

    private Notification notification() {
        NotificationManager nm = (NotificationManager) getSystemService(NOTIFICATION_SERVICE);
        if (Build.VERSION.SDK_INT >= 26) {
            NotificationChannel ch = new NotificationChannel(CHANNEL, getString(R.string.gps_channel), NotificationManager.IMPORTANCE_LOW);
            ch.setShowBadge(false);
            nm.createNotificationChannel(ch);
        }
        int immutable = Build.VERSION.SDK_INT >= 23 ? PendingIntent.FLAG_IMMUTABLE : 0;
        PendingIntent open = PendingIntent.getActivity(this, 0, new Intent(this, GpsActivity.class), PendingIntent.FLAG_UPDATE_CURRENT | immutable);
        PendingIntent stop = PendingIntent.getService(this, 1, new Intent(this, MockService.class).setAction(ACTION_STOP), PendingIntent.FLAG_UPDATE_CURRENT | immutable);
        Notification.Builder b = Build.VERSION.SDK_INT >= 26 ? new Notification.Builder(this, CHANNEL) : new Notification.Builder(this);
        String where = label == null || label.isEmpty() ? String.format(java.util.Locale.US, "%.5f, %.5f", lat, lng) : label;
        return b.setSmallIcon(R.drawable.ic_pin)
                .setContentTitle(getString(R.string.gps_notif_title))
                .setContentText(where)
                .setColor(0xFF6D5EFC)
                .setOngoing(true)
                .setContentIntent(open)
                .addAction(new Notification.Action.Builder(null, getString(R.string.gps_notif_stop), stop).build())
                .build();
    }
}
