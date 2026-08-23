package com.kipushi.invitations;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.graphics.Canvas;
import android.graphics.Color;
import android.graphics.Paint;
import android.graphics.Typeface;

import com.google.zxing.BarcodeFormat;
import com.google.zxing.EncodeHintType;
import com.google.zxing.WriterException;
import com.google.zxing.common.BitMatrix;
import com.google.zxing.qrcode.QRCodeWriter;

import java.util.HashMap;
import java.util.Map;

public class InvitationRenderer {

    public static Bitmap render(Context ctx, Guest guest, PrefsHelper prefs) {
        InvitationStyle style = InvitationStyle.getById(guest.styleId);
        Bitmap template = BitmapFactory.decodeResource(ctx.getResources(), style.templateRes);
        int w = template.getWidth();
        int h = template.getHeight();
        Bitmap result = Bitmap.createBitmap(w, h, Bitmap.Config.ARGB_8888);
        Canvas canvas = new Canvas(result);
        canvas.drawBitmap(template, 0, 0, null);

        if (prefs.embedName()) {
            drawGuestName(canvas, guest, style, w, h);
        }

        if (guest.tableZone != null && !guest.tableZone.isEmpty() && !"royal-bordeaux".equals(style.id)) {
            drawPlacement(canvas, guest, w, h);
        }

        drawQrCode(canvas, guest, style, w, h);
        return result;
    }

    private static void drawGuestName(Canvas canvas, Guest guest, InvitationStyle style, int w, int h) {
        Paint paint = new Paint(Paint.ANTI_ALIAS_FLAG);
        paint.setColor(style.nameColor);
        paint.setTextSize(style.nameSize * (w / 340f));
        paint.setTypeface(Typeface.create(Typeface.SERIF, Typeface.BOLD));

        String name = guest.fullName;
        if ("royal-bordeaux".equals(style.id)) {
            name = "~ ~ " + guest.fullName.toUpperCase() + " ~ ~";
            paint.setLetterSpacing(0.08f);
        }

        float x = style.nameX * w;
        float y = style.nameY * h;
        if (style.centerName) {
            paint.setTextAlign(Paint.Align.CENTER);
            canvas.drawText(name, w / 2f, y + paint.getTextSize(), paint);
        } else {
            paint.setTextAlign(Paint.Align.LEFT);
            canvas.drawText(name, x, y + paint.getTextSize(), paint);
        }
    }

    private static void drawPlacement(Canvas canvas, Guest guest, int w, int h) {
        Paint paint = new Paint(Paint.ANTI_ALIAS_FLAG);
        paint.setColor(Color.parseColor("#6B2D82"));
        paint.setTextSize(14f * (w / 340f));
        paint.setTextAlign(Paint.Align.CENTER);
        String text = guest.seats + " place(s)";
        if (guest.tableZone != null && !guest.tableZone.isEmpty()) {
            text += " • " + guest.tableZone;
        }
        canvas.drawText(text, w / 2f, h * 0.915f, paint);
    }

    private static void drawQrCode(Canvas canvas, Guest guest, InvitationStyle style, int w, int h) {
        try {
            int size = (int) (style.qrSize * w);
            String data = "{\"id\":" + guest.id + ",\"name\":\"" + guest.fullName + "\"}";
            Bitmap qr = generateQr(data, size);
            float left = style.qrX * w;
            float top = style.qrY * h;
            canvas.drawRect(left - 4, top - 4, left + size + 4, top + size + 4, whitePaint());
            canvas.drawBitmap(qr, left, top, null);
        } catch (WriterException e) {
            e.printStackTrace();
        }
    }

    private static Paint whitePaint() {
        Paint p = new Paint();
        p.setColor(Color.WHITE);
        p.setStyle(Paint.Style.FILL);
        return p;
    }

    private static Bitmap generateQr(String data, int size) throws WriterException {
        Map<EncodeHintType, Object> hints = new HashMap<>();
        hints.put(EncodeHintType.MARGIN, 1);
        BitMatrix matrix = new QRCodeWriter().encode(data, BarcodeFormat.QR_CODE, size, size, hints);
        Bitmap bmp = Bitmap.createBitmap(size, size, Bitmap.Config.RGB_565);
        for (int x = 0; x < size; x++) {
            for (int y = 0; y < size; y++) {
                bmp.setPixel(x, y, matrix.get(x, y) ? Color.parseColor("#5a2d82") : Color.WHITE);
            }
        }
        return bmp;
    }
}
