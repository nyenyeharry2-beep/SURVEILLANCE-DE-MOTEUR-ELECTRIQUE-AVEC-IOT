package com.kipushi.invitations;

import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;

import android.content.ContentValues;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashSet;
import java.util.List;
import java.util.Locale;
import java.util.Set;

public class DatabaseHelper extends SQLiteOpenHelper {
    private static final String DB = "invitations.db";
    private static final int VER = 1;

    public DatabaseHelper(Context ctx) {
        super(ctx, DB, null, VER);
    }

    @Override
    public void onCreate(SQLiteDatabase db) {
        db.execSQL("CREATE TABLE guests (" +
            "id INTEGER PRIMARY KEY AUTOINCREMENT," +
            "full_name TEXT," +
            "whatsapp TEXT," +
            "seats INTEGER," +
            "table_zone TEXT," +
            "style_id TEXT," +
            "sent INTEGER," +
            "created_at TEXT)");
    }

    @Override
    public void onUpgrade(SQLiteDatabase db, int oldV, int newV) {}

    public long insertGuest(Guest g) {
        ContentValues cv = toValues(g);
        if (g.createdAt == null) {
            cv.put("created_at", new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.FRANCE).format(new Date()));
        }
        return getWritableDatabase().insert("guests", null, cv);
    }

    public int updateGuest(Guest g) {
        return getWritableDatabase().update("guests", toValues(g), "id=?", new String[]{String.valueOf(g.id)});
    }

    public void deleteGuest(long id) {
        getWritableDatabase().delete("guests", "id=?", new String[]{String.valueOf(id)});
    }

    public Guest getGuest(long id) {
        Cursor c = getReadableDatabase().rawQuery("SELECT * FROM guests WHERE id=?", new String[]{String.valueOf(id)});
        Guest g = null;
        if (c.moveToFirst()) g = fromCursor(c);
        c.close();
        return g;
    }

    public List<Guest> getAllGuests(String search, String tableFilter) {
        StringBuilder sql = new StringBuilder("SELECT * FROM guests WHERE 1=1");
        List<String> args = new ArrayList<>();

        if (search != null && !search.trim().isEmpty()) {
            sql.append(" AND (full_name LIKE ? OR whatsapp LIKE ?)");
            String like = "%" + search.trim() + "%";
            args.add(like);
            args.add(like);
        }
        if (tableFilter != null && !tableFilter.isEmpty() && !"Toutes".equals(tableFilter)) {
            sql.append(" AND table_zone = ?");
            args.add(tableFilter);
        }
        sql.append(" ORDER BY id DESC");

        Cursor c = getReadableDatabase().rawQuery(sql.toString(), args.toArray(new String[0]));
        List<Guest> list = new ArrayList<>();
        while (c.moveToNext()) list.add(fromCursor(c));
        c.close();
        return list;
    }

    public List<String> getDistinctTables() {
        Cursor c = getReadableDatabase().rawQuery(
            "SELECT DISTINCT table_zone FROM guests WHERE table_zone IS NOT NULL AND table_zone != '' ORDER BY table_zone",
            null);
        Set<String> tables = new HashSet<>();
        tables.add("Toutes");
        while (c.moveToNext()) {
            String t = c.getString(0);
            if (t != null && !t.isEmpty()) tables.add(t);
        }
        c.close();
        return new ArrayList<>(tables);
    }

    private ContentValues toValues(Guest g) {
        ContentValues cv = new ContentValues();
        cv.put("full_name", g.fullName);
        cv.put("whatsapp", g.whatsapp);
        cv.put("seats", g.seats);
        cv.put("table_zone", g.tableZone);
        cv.put("style_id", g.styleId);
        cv.put("sent", g.sent ? 1 : 0);
        cv.put("created_at", g.createdAt);
        return cv;
    }

    private Guest fromCursor(Cursor c) {
        Guest g = new Guest();
        g.id = c.getLong(c.getColumnIndexOrThrow("id"));
        g.fullName = c.getString(c.getColumnIndexOrThrow("full_name"));
        g.whatsapp = c.getString(c.getColumnIndexOrThrow("whatsapp"));
        g.seats = c.getInt(c.getColumnIndexOrThrow("seats"));
        g.tableZone = c.getString(c.getColumnIndexOrThrow("table_zone"));
        g.styleId = c.getString(c.getColumnIndexOrThrow("style_id"));
        if (g.styleId == null || g.styleId.isEmpty()) g.styleId = "affiche-blanche";
        g.sent = c.getInt(c.getColumnIndexOrThrow("sent")) == 1;
        g.createdAt = c.getString(c.getColumnIndexOrThrow("created_at"));
        return g;
    }
}
