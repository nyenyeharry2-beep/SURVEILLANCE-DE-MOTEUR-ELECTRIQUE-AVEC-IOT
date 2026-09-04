package com.kipushi.invitations;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.net.Uri;

import java.io.File;
import java.io.FileOutputStream;
import java.io.InputStream;

/** Photos personnalisées — logo couple + affiches uploadées par l'utilisateur. */
public final class PhotoManager {
    public static final String COUPLE = "couple_photo.jpg";
    public static final String POSTER_CIVIL = "poster_civil.jpg";
    public static final String POSTER_BLANCHE = "poster_blanche.jpg";

    private PhotoManager() {}

    public static File file(Context ctx, String name) {
        return new File(ctx.getFilesDir(), name);
    }

    public static boolean hasCustomCouple(Context ctx) {
        return file(ctx, COUPLE).exists();
    }

    public static boolean hasCustomPoster(Context ctx, String styleId) {
        return file(ctx, posterFileName(styleId)).exists();
    }

    public static String posterFileName(String styleId) {
        return "affiche-blanche".equals(styleId) ? POSTER_BLANCHE : POSTER_CIVIL;
    }

    public static String couplePhotoUri(Context ctx) {
        File custom = file(ctx, COUPLE);
        if (custom.exists()) {
            return "file://" + custom.getAbsolutePath();
        }
        return "file:///android_asset/posters/couple_photo.png";
    }

    public static String posterBgUri(Context ctx, String styleId) {
        File custom = file(ctx, posterFileName(styleId));
        if (custom.exists()) {
            return "file://" + custom.getAbsolutePath();
        }
        String asset = "affiche-blanche".equals(styleId)
            ? "posters/affiche_blanche_bg.png"
            : "posters/mariage_civil_bg.png";
        return "file:///android_asset/" + asset;
    }

    public static boolean saveFromUri(Context ctx, Uri uri, String fileName) throws Exception {
        InputStream in = ctx.getContentResolver().openInputStream(uri);
        if (in == null) throw new IllegalStateException("Impossible de lire l'image");

        Bitmap bmp = BitmapFactory.decodeStream(in);
        in.close();
        if (bmp == null) throw new IllegalStateException("Image invalide");

        File out = file(ctx, fileName);
        FileOutputStream fos = new FileOutputStream(out);
        bmp.compress(Bitmap.CompressFormat.JPEG, 92, fos);
        fos.close();
        bmp.recycle();
        return true;
    }

    public static void updateLauncherIcon(Context ctx) {
        /* L'icône système reste celle du launcher ; le logo in-app utilise couple_photo.jpg */
    }
}
