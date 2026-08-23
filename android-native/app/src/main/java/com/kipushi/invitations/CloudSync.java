package com.kipushi.invitations;

import android.content.Context;
import android.util.Log;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.List;

/** Synchronisation invités ↔ site marriage1.site.je (silencieuse) */
public final class CloudSync {
    private static final String TAG = "NkubaSync";

    private CloudSync() {}

    public static void pushGuestsAsync(Context ctx) {
        new Thread(() -> {
            try {
                pushGuests(ctx);
            } catch (Exception e) {
                Log.w(TAG, "Sync différée");
            }
        }).start();
    }

    public static void pushGuests(Context ctx) throws Exception {
        DatabaseHelper db = new DatabaseHelper(ctx);
        PrefsHelper prefs = new PrefsHelper(ctx);
        List<Guest> guests = db.getAllGuests("", "Toutes");

        JSONArray arr = new JSONArray();
        for (Guest g : guests) {
            JSONObject o = new JSONObject();
            o.put("id", g.id);
            o.put("fullName", g.fullName);
            o.put("whatsapp", g.whatsapp);
            o.put("tableZone", g.tableZone != null ? g.tableZone : "");
            o.put("seats", g.seats);
            o.put("styleId", g.styleId);
            o.put("sent", g.sent);
            o.put("createdAt", g.createdAt != null ? g.createdAt : "");
            arr.put(o);
        }

        JSONObject body = new JSONObject();
        body.put("guests", arr);
        JSONObject cfg = new JSONObject();
        cfg.put("event_date", prefs.getDate());
        cfg.put("event_time", prefs.getTime());
        cfg.put("event_venue", prefs.getVenue());
        cfg.put("whatsapp_message", prefs.getMessage());
        body.put("config", cfg);

        URL url = new URL(AppConstants.CLOUD_API + "guests.php?action=sync");
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("POST");
        conn.setRequestProperty("Content-Type", "application/json; charset=utf-8");
        conn.setDoOutput(true);
        conn.setConnectTimeout(15000);
        conn.setReadTimeout(20000);

        byte[] bytes = body.toString().getBytes(StandardCharsets.UTF_8);
        try (OutputStream os = conn.getOutputStream()) {
            os.write(bytes);
        }

        int code = conn.getResponseCode();
        if (code < 200 || code >= 300) {
            throw new IllegalStateException("HTTP " + code);
        }
        conn.disconnect();
    }
}
