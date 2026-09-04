package com.kipushi.invitations;

import android.net.Uri;
import android.os.Bundle;
import android.widget.ImageView;

import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.card.MaterialCardView;

public class MainActivity extends AppCompatActivity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        refreshLogo();

        findViewById(R.id.btnConfig).setOnClickListener(v ->
            startActivity(new android.content.Intent(this, ConfigActivity.class)));
        findViewById(R.id.btnAddGuest).setOnClickListener(v ->
            startActivity(new android.content.Intent(this, AddGuestActivity.class)));
        findViewById(R.id.btnDashboard).setOnClickListener(v ->
            startActivity(new android.content.Intent(this, DashboardActivity.class)));

        findViewById(R.id.btnDownloadApk).setOnClickListener(v -> {
            android.content.Intent i = new android.content.Intent(
                android.content.Intent.ACTION_VIEW,
                android.net.Uri.parse(AppConstants.APK_PAGE));
            startActivity(i);
        });
    }

    @Override
    protected void onResume() {
        super.onResume();
        refreshLogo();
        CloudSync.syncAsync(this);
    }

    private void refreshLogo() {
        ImageView logo = findViewById(R.id.logoImage);
        if (logo == null) return;
        if (PhotoManager.hasCustomCouple(this)) {
            logo.setImageURI(Uri.fromFile(PhotoManager.file(this, PhotoManager.COUPLE)));
        } else {
            logo.setImageResource(R.drawable.couple_photo);
        }
    }
}
