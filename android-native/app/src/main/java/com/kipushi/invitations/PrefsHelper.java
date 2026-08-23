package com.kipushi.invitations;

import android.content.Context;
import android.content.SharedPreferences;

public class PrefsHelper {
    private static final String PREFS = "invitation_prefs";

    private final SharedPreferences prefs;

    public PrefsHelper(Context ctx) {
        prefs = ctx.getSharedPreferences(PREFS, Context.MODE_PRIVATE);
    }

    public String getDate() {
        return prefs.getString("date", "Vendredi, le 11 Septembre 2026");
    }

    public String getTime() {
        return prefs.getString("time", "11h00");
    }

    public void setDate(String v) { prefs.edit().putString("date", v).apply(); }

    public void setTime(String v) { prefs.edit().putString("time", v).apply(); }

    public String getVenue() {
        return prefs.getString("venue", "Commune de Kipushi, Ville de KIPUSHI");
    }

    public void setVenue(String v) { prefs.edit().putString("venue", v).apply(); }

    public String getMessage() {
        return prefs.getString("message",
            "Bonjour {NAME}, nous avons l'honneur de vous inviter au mariage civil de nos enfants, Moïse NKUBA & Sarah KASONGO, le {DATE} à {VENUE}. Votre présence fera notre immense joie.");
    }

    public void setMessage(String v) { prefs.edit().putString("message", v).apply(); }

    public boolean embedName() {
        return prefs.getBoolean("embed_name", true);
    }

    public void setEmbedName(boolean v) { prefs.edit().putBoolean("embed_name", v).apply(); }

    public String getDefaultStyle() {
        return prefs.getString("default_style", "mariage-civil");
    }

    public void setDefaultStyle(String v) { prefs.edit().putString("default_style", v).apply(); }

    public String formatMessage(Guest guest) {
        return getMessage()
            .replace("{NAME}", guest.fullName)
            .replace("{DATE}", getDate())
            .replace("{VENUE}", getVenue())
            .replace("{TABLE}", guest.tableZone != null ? guest.tableZone : "—")
            .replace("{SEATS}", String.valueOf(guest.seats));
    }
}
