package com.kipushi.invitations;

import android.content.Intent;
import android.os.Bundle;

import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.card.MaterialCardView;

public class MainActivity extends AppCompatActivity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        findViewById(R.id.btnConfig).setOnClickListener(v ->
            startActivity(new Intent(this, ConfigActivity.class)));
        findViewById(R.id.btnAddGuest).setOnClickListener(v ->
            startActivity(new Intent(this, AddGuestActivity.class)));
        findViewById(R.id.btnDashboard).setOnClickListener(v ->
            startActivity(new Intent(this, DashboardActivity.class)));
    }
}
