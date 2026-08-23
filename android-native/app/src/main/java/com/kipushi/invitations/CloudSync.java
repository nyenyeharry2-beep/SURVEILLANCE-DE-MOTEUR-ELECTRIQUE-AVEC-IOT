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
import java.util.ArrayList;
import java.util.List;

/** Sync bidirectionnelle invités + config ↔ marriage1.site.je */
public final class CloudSync {
    private static final String TAG = "NkubaSync";

    private CloudSync() {}

    public interface PullCallback {
        void onDone();
        void onError(Exception e);
    }

    public static void syncAsync(Context ctx) {
        new Thread(() -> {
            try {
                pullFromServer(ctx);
                pushGuests(ctx);
            } catch (Exception e) {
                Log.w(TAG, "Sync différée");
            }
        }).start();
    }

    public static void pushGuestsAsync(Context ctx) {
        new Thread(() -> {
            try {
                pushGuests(ctx);
            } catch (Exception e) {
                Log.w(TAG, "Push différé");
            }
        }).start();
    }

    public static void pullFromServer(Context ctx) throws Exception {
        DatabaseHelper db = new DatabaseHelper(ctx);
        PrefsHelper prefs = new PrefsHelper(ctx);

        URL listUrl = new URL(AppConstants.CLOUD_API + "guests.php?action=list&sort=name");
        HttpURLConnection listConn = (HttpURLConnection) listUrl.openConnection();
        listConn.setConnectTimeout(12000);
        listConn.setReadTimeout(15000);
        String listBody = readBody(listConn);
        listConn.disconnect();

        JSONObject listJson = new JSONObject(listBody);
        if (listJson.optBoolean("success")) {
            JSONArray arr = listJson.getJSONArray("guests");
            List<Guest> guests = new ArrayList<>();
            for (int i = 0; i < arr.length(); i++) {
                JSONObject o = arr.getJSONObject(i);
                Guest g = new Guest();
                g.id = o.optLong("id", 0);
                g.fullName = o.optString("fullName", "");
                g.whatsapp = o.optString("whatsapp", "");
                g.tableZone = o.optString("tableZone", "");
                g.seats = o.optInt("seats", 1);
                g.styleId = o.optString("styleId", "mariage-civil");
                g.sent = o.optBoolean("sent", false);
                g.createdAt = o.optString("createdAt", "");
                guests.add(g);
            }
            db.replaceAllGuests(guests);
        }

        URL cfgUrl = new URL(AppConstants.CLOUD_API + "config.php");
        HttpURLConnection cfgConn = (HttpURLConnection) cfgUrl.openConnection();
        cfgConn.setConnectTimeout(12000);
        String cfgBody = readBody(cfgConn);
        cfgConn.disconnect();

        JSONObject cfgJson = new JSONObject(cfgBody);
        if (cfgJson.optBoolean("success")) {
            JSONObject c = cfgJson.getJSONObject("config");
            if (c.has("event_date")) prefs.setDate(c.getString("event_date"));
            if (c.has("event_time")) prefs.setTime(c.getString("event_time"));
            if (c.has("event_venue")) prefs.setVenue(c.getString("event_venue"));
            if (c.has("whatsapp_message")) prefs.setMessage(c.getString("whatsapp_message"));
        }
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
        conn.disconnect();
        if (code < 200 || code >= 300) {
            throw new IllegalStateException("HTTP " + code);
        }
    }

    private static String readBody(HttpURLConnection conn) throws Exception {
        int code = conn.getResponseCode();
        var stream = code >= 400 ? conn.getErrorStream() : conn.getInputStream();
        if (stream == null) return "{}";
        BufferedReader reader = new BufferedReader(new InputStreamReader(stream, StandardCharsets.UTF_8));
        StringBuilder sb = new StringBuilder();
        String line;
        while ((line = reader.readLine()) != null) sb.append(line);
        reader.close();
        return sb.toString();
    }
}
