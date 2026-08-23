package com.kipushi.invitations;

import android.content.ContentValues;
import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;
import java.util.Locale;

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
        SQLiteDatabase db = getWritableDatabase();
        ContentValues cv = toValues(g);
        if (g.createdAt == null) {
            cv.put("created_at", new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.FRANCE).format(new Date()));
        }
        return db.insert("guests", null, cv);
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

    public List<Guest> getAllGuests(String search) {
        String q = "SELECT * FROM guests ORDER BY id DESC";
        Cursor c;
        if (search != null && !search.trim().isEmpty()) {
            String like = "%" + search.trim() + "%";
            c = getReadableDatabase().rawQuery(
                "SELECT * FROM guests WHERE full_name LIKE ? OR whatsapp LIKE ? ORDER BY id DESC",
                new String[]{like, like});
        } else {
            c = getReadableDatabase().rawQuery(q, null);
        }
        List<Guest> list = new ArrayList<>();
        while (c.moveToNext()) list.add(fromCursor(c));
        c.close();
        return list;
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
        g.sent = c.getInt(c.getColumnIndexOrThrow("sent")) == 1;
        g.createdAt = c.getString(c.getColumnIndexOrThrow("created_at"));
        return g;
    }
}
