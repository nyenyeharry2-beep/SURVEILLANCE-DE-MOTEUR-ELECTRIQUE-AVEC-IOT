package com.kipushi.invitations;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.Canvas;
import android.os.Handler;
import android.os.Looper;
import android.util.Base64;
import android.webkit.WebView;
import android.webkit.WebViewClient;

import com.google.zxing.BarcodeFormat;
import com.google.zxing.EncodeHintType;
import com.google.zxing.WriterException;
import com.google.zxing.common.BitMatrix;
import com.google.zxing.qrcode.QRCodeWriter;

import java.io.BufferedReader;
import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.util.HashMap;
import java.util.Map;
import java.util.concurrent.CountDownLatch;
import java.util.concurrent.atomic.AtomicReference;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class HtmlInvitationRenderer {

    public interface Callback {
        void onRendered(Bitmap bitmap);
        void onError(Exception e);
    }

    private static final int WIDTH = 1200;
    private static final int HEIGHT = 1700;

    public static void renderAsync(Context ctx, Guest guest, PrefsHelper prefs, Callback callback) {
        new Thread(() -> {
            try {
                Bitmap bmp = renderBlocking(ctx, guest, prefs);
                new Handler(Looper.getMainLooper()).post(() -> callback.onRendered(bmp));
            } catch (Exception e) {
                new Handler(Looper.getMainLooper()).post(() -> callback.onError(e));
            }
        }).start();
    }

    public static Bitmap renderBlocking(Context ctx, Guest guest, PrefsHelper prefs) throws Exception {
        InvitationStyle style = InvitationStyle.getById(guest.styleId);
        if (style.htmlAsset == null) {
            return InvitationRenderer.renderCanvas(ctx, guest, prefs);
        }

        String fragment = loadAsset(ctx, style.htmlAsset);
        String css = loadAssetSafe(ctx, "css/invitation.css");
        String coupleUri = PhotoManager.couplePhotoUri(ctx);
        String guestName = escapeHtml(prefs.embedName() ? guest.fullName : "Invité");
        String table = escapeHtml(empty(guest.tableZone, "—"));
        String seats = String.valueOf(guest.seats);
        String date = escapeHtml(prefs.getDate());
        String time = escapeHtml(prefs.getTime());
        String venue = escapeHtml(prefs.getVenue());
        String qrImg = qrDataUri(guest, prefs, style.qrColor);

        fragment = bindAttr(fragment, "couple", coupleUri, true);
        fragment = bindAttr(fragment, "guest", guestName, false);
        fragment = bindAttr(fragment, "table", table, false);
        fragment = bindAttr(fragment, "table2", table, false);
        fragment = bindAttr(fragment, "seats", seats, false);
        fragment = bindAttr(fragment, "date", date, false);
        fragment = bindAttr(fragment, "time", time, false);
        fragment = bindAttr(fragment, "venue", venue, false);

        if (isBlankTable(table)) {
            fragment = fragment.replaceAll("(?s)(<[^>]+data-bind-table[^>]*>)", "$1 style=\"display:none\"");
        }

        fragment = fragment.replaceFirst(
            "(<div data-bind=\"qr\" class=\"qr-canvas\"[^>]*>)\\s*</div>",
            "$1<img src=\"" + qrImg + "\" width=\"184\" height=\"184\" alt=\"QR\"/></div>"
        );

        String html = "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"/>"
            + "<link href=\"https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@400;700&display=swap\" rel=\"stylesheet\"/>"
            + "<style>" + css + "body{margin:0;padding:0;background:#fff;}</style>"
            + "</head><body>" + fragment + "</body></html>";

        return captureWebView(ctx, html);
    }

    private static String bindAttr(String html, String key, String value, boolean isSrc) {
        if (isSrc) {
            return html.replaceAll(
                "(<[^>]+data-bind=\"" + key + "\"[^>]*src=\")[^\"]*(\")",
                "$1" + Matcher.quoteReplacement(value) + "$2"
            );
        }
        Pattern p = Pattern.compile(
            "(<[^>]+data-bind=\"" + key + "\"[^>]*>)([^<]*)(</[^>]+>)",
            Pattern.DOTALL
        );
        Matcher m = p.matcher(html);
        StringBuffer sb = new StringBuffer();
        while (m.find()) {
            m.appendReplacement(sb, Matcher.quoteReplacement(m.group(1) + value + m.group(3)));
        }
        m.appendTail(sb);
        return sb.toString();
    }

    private static boolean isBlankTable(String table) {
        return table == null || table.trim().isEmpty() || "—".equals(table.trim()) || "-".equals(table.trim());
    }

    private static Bitmap captureWebView(Context ctx, String html) throws InterruptedException {
        AtomicReference<Bitmap> result = new AtomicReference<>();
        AtomicReference<Exception> error = new AtomicReference<>();
        CountDownLatch latch = new CountDownLatch(1);

        new Handler(Looper.getMainLooper()).post(() -> {
            try {
                WebView webView = new WebView(ctx);
                webView.getSettings().setJavaScriptEnabled(false);
                webView.getSettings().setAllowFileAccess(true);
                webView.getSettings().setAllowFileAccessFromFileURLs(true);
                webView.getSettings().setLoadWithOverviewMode(true);
                webView.getSettings().setUseWideViewPort(true);
                webView.setInitialScale(100);
                webView.layout(0, 0, WIDTH, HEIGHT);

                webView.setWebViewClient(new WebViewClient() {
                    @Override
                    public void onPageFinished(WebView view, String url) {
                        view.postDelayed(() -> {
                            try {
                                Bitmap bmp = Bitmap.createBitmap(WIDTH, HEIGHT, Bitmap.Config.ARGB_8888);
                                Canvas canvas = new Canvas(bmp);
                                view.draw(canvas);
                                result.set(bmp);
                            } catch (Exception e) {
                                error.set(e);
                            } finally {
                                view.destroy();
                                latch.countDown();
                            }
                        }, 2800);
                    }
                });

                webView.loadDataWithBaseURL(
                    "file:///android_asset/",
                    html,
                    "text/html",
                    "UTF-8",
                    null
                );
            } catch (Exception e) {
                error.set(e);
                latch.countDown();
            }
        });

        latch.await();
        if (error.get() != null) throw new RuntimeException(error.get());
        return result.get();
    }

    private static String loadAsset(Context ctx, String path) throws Exception {
        InputStream is = ctx.getAssets().open(path);
        BufferedReader reader = new BufferedReader(new InputStreamReader(is, "UTF-8"));
        StringBuilder sb = new StringBuilder();
        String line;
        while ((line = reader.readLine()) != null) sb.append(line).append('\n');
        reader.close();
        return sb.toString();
    }

    private static String loadAssetSafe(Context ctx, String path) {
        try {
            return loadAsset(ctx, path);
        } catch (Exception e) {
            return "";
        }
    }

    private static String qrDataUri(Guest guest, PrefsHelper prefs, String color) throws WriterException {
        int size = 240;
        String data = "INVITE|id:" + guest.id
            + "|nom:" + guest.fullName
            + "|table:" + empty(guest.tableZone, "-")
            + "|date:" + prefs.getDate()
            + "|places:" + guest.seats;
        Map<EncodeHintType, Object> hints = new HashMap<>();
        hints.put(EncodeHintType.MARGIN, 1);
        BitMatrix matrix = new QRCodeWriter().encode(data, BarcodeFormat.QR_CODE, size, size, hints);
        Bitmap bmp = Bitmap.createBitmap(size, size, Bitmap.Config.RGB_565);
        int dark = android.graphics.Color.parseColor(color);
        for (int x = 0; x < size; x++) {
            for (int y = 0; y < size; y++) {
                bmp.setPixel(x, y, matrix.get(x, y) ? dark : android.graphics.Color.WHITE);
            }
        }
        ByteArrayOutputStream baos = new ByteArrayOutputStream();
        bmp.compress(Bitmap.CompressFormat.PNG, 100, baos);
        return "data:image/png;base64," + Base64.encodeToString(baos.toByteArray(), Base64.NO_WRAP);
    }

    private static String escapeHtml(String s) {
        return s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;").replace("\"", "&quot;");
    }

    private static String empty(String s, String fallback) {
        return s == null || s.trim().isEmpty() ? fallback : s.trim();
    }
}
