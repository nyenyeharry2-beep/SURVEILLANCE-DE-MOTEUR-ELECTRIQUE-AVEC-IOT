package com.kipushi.invitations;

import android.content.Intent;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.FileProvider;

import com.google.android.material.button.MaterialButton;

import java.io.File;
import java.io.FileOutputStream;

public class PreviewActivity extends AppCompatActivity {
    private Guest guest;
    private Bitmap invitationBitmap;
    private PrefsHelper prefs;
    private DatabaseHelper db;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_preview);
        prefs = new PrefsHelper(this);
        db = new DatabaseHelper(this);

        ProgressBar progress = findViewById(R.id.progressRender);
        ImageView preview = findViewById(R.id.invitationPreview);

        long guestId = getIntent().getLongExtra("guest_id", -1);
        guest = db.getGuest(guestId);
        if (guest == null) {
            finish();
            return;
        }

        progress.setVisibility(View.VISIBLE);
        HtmlInvitationRenderer.renderAsync(this, guest, prefs, new HtmlInvitationRenderer.Callback() {
            @Override
            public void onRendered(Bitmap bitmap) {
                progress.setVisibility(View.GONE);
                invitationBitmap = bitmap;
                preview.setImageBitmap(bitmap);
            }

            @Override
            public void onError(Exception e) {
                progress.setVisibility(View.GONE);
                invitationBitmap = InvitationRenderer.renderCanvas(PreviewActivity.this, guest, prefs);
                preview.setImageBitmap(invitationBitmap);
            }
        });

        findViewById(R.id.btnSend).setOnClickListener(v -> sendWhatsApp());
        findViewById(R.id.btnClose).setOnClickListener(v -> finish());
    }

    private void sendWhatsApp() {
        if (invitationBitmap == null || guest == null) return;
        try {
            File file = saveBitmapToCache(invitationBitmap, guest.id);
            Uri uri = FileProvider.getUriForFile(this, getPackageName() + ".fileprovider", file);
            String phone = guest.whatsapp.replaceAll("\\D", "");
            String message = prefs.formatMessage(guest);

            Intent share = new Intent(Intent.ACTION_SEND);
            share.setType("image/png");
            share.putExtra(Intent.EXTRA_STREAM, uri);
            share.putExtra(Intent.EXTRA_TEXT, message);
            share.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION);
            share.setPackage("com.whatsapp");

            try {
                startActivity(share);
            } catch (android.content.ActivityNotFoundException e) {
                startActivity(new Intent(Intent.ACTION_VIEW,
                    Uri.parse("https://wa.me/" + phone + "?text=" + Uri.encode(message))));
            }

            guest.sent = true;
            db.updateGuest(guest);
            CloudSync.pushGuestsAsync(this);
        } catch (Exception e) {
            Toast.makeText(this, "Erreur partage", Toast.LENGTH_SHORT).show();
        }
    }

    private File saveBitmapToCache(Bitmap bmp, long id) throws Exception {
        File dir = new File(getCacheDir(), "invitations");
        if (!dir.exists()) dir.mkdirs();
        File file = new File(dir, "invitation_" + id + ".png");
        FileOutputStream fos = new FileOutputStream(file);
        bmp.compress(Bitmap.CompressFormat.PNG, 100, fos);
        fos.close();
        return file;
    }
}
