package com.kipushi.invitations;

import android.content.Context;
import android.graphics.Bitmap;
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

    /** Canvas fallback when HTML fails. */
    public static Bitmap renderCanvas(Context ctx, Guest guest, PrefsHelper prefs) {
        InvitationStyle style = InvitationStyle.getById(guest.styleId);
        if (style.templateRes == 0) {
            throw new RuntimeException("No template resource");
        }
        android.graphics.Bitmap template = android.graphics.BitmapFactory.decodeResource(ctx.getResources(), style.templateRes);
        int w = template.getWidth();
        int h = template.getHeight();
        Bitmap result = Bitmap.createBitmap(w, h, Bitmap.Config.ARGB_8888);
        Canvas canvas = new Canvas(result);
        canvas.drawBitmap(template, 0, 0, null);

        if (prefs.embedName()) drawGuestName(canvas, guest, style, w, h);
        if (guest.tableZone != null && !guest.tableZone.isEmpty()) drawPlacement(canvas, guest, w, h);
        drawQrCode(canvas, guest, style, w, h);
        return result;
    }

    public static Bitmap render(Context ctx, Guest guest, PrefsHelper prefs) {
        InvitationStyle style = InvitationStyle.getById(guest.styleId);
        if (style.htmlAsset != null) {
            try {
                return HtmlInvitationRenderer.renderBlocking(ctx, guest, prefs);
            } catch (Exception e) {
                return renderCanvas(ctx, guest, prefs);
            }
        }
        return renderCanvas(ctx, guest, prefs);
    }

    private static void drawGuestName(Canvas canvas, Guest guest, InvitationStyle style, int w, int h) {
        Paint paint = new Paint(Paint.ANTI_ALIAS_FLAG);
        paint.setColor(style.nameColor);
        paint.setTextSize(style.nameSize * (w / 340f));
        paint.setTypeface(Typeface.create(Typeface.SERIF, Typeface.BOLD));
        String name = guest.fullName;
        float y = style.nameY * h;
        if (style.centerName) {
            paint.setTextAlign(Paint.Align.CENTER);
            canvas.drawText(name, w / 2f, y + paint.getTextSize(), paint);
        } else {
            paint.setTextAlign(Paint.Align.LEFT);
            canvas.drawText(name, style.nameX * w, y + paint.getTextSize(), paint);
        }
    }

    private static void drawPlacement(Canvas canvas, Guest guest, int w, int h) {
        Paint paint = new Paint(Paint.ANTI_ALIAS_FLAG);
        paint.setColor(Color.parseColor("#6B2D82"));
        paint.setTextSize(14f * (w / 340f));
        paint.setTextAlign(Paint.Align.CENTER);
        String text = guest.seats + " place(s)";
        if (guest.tableZone != null && !guest.tableZone.isEmpty()) text += " • " + guest.tableZone;
        canvas.drawText(text, w / 2f, h * 0.915f, paint);
    }

    private static void drawQrCode(Canvas canvas, Guest guest, InvitationStyle style, int w, int h) {
        try {
            int size = (int) (style.qrSize * w);
            Bitmap qr = generateQr("{\"id\":" + guest.id + "}", size, style.qrColor);
            float left = style.qrX * w;
            float top = style.qrY * h;
            canvas.drawBitmap(qr, left, top, null);
        } catch (WriterException e) {
            e.printStackTrace();
        }
    }

    private static Bitmap generateQr(String data, int size, String color) throws WriterException {
        Map<EncodeHintType, Object> hints = new HashMap<>();
        hints.put(EncodeHintType.MARGIN, 1);
        BitMatrix matrix = new QRCodeWriter().encode(data, BarcodeFormat.QR_CODE, size, size, hints);
        Bitmap bmp = Bitmap.createBitmap(size, size, Bitmap.Config.RGB_565);
        int dark = Color.parseColor(color);
        for (int x = 0; x < size; x++)
            for (int y = 0; y < size; y++)
                bmp.setPixel(x, y, matrix.get(x, y) ? dark : Color.WHITE);
        return bmp;
    }
}
