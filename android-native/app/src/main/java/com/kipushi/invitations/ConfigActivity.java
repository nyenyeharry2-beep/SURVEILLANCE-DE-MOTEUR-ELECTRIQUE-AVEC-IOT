package com.kipushi.invitations;

import android.net.Uri;
import android.os.Bundle;
import android.widget.CheckBox;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

public class ConfigActivity extends AppCompatActivity {
    private PrefsHelper prefs;
    private ImageView posterPreview;
    private ActivityResultLauncher<String> pickCouple;
    private ActivityResultLauncher<String> pickPosterCivil;
    private ActivityResultLauncher<String> pickPosterBlanche;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setTheme(R.style.Theme_Invitations_Dark);
        setContentView(R.layout.activity_config);
        prefs = new PrefsHelper(this);
        posterPreview = findViewById(R.id.posterPreview);

        pickCouple = registerForActivityResult(
            new ActivityResultContracts.GetContent(),
            uri -> savePhoto(uri, PhotoManager.COUPLE, "Photo du couple enregistrée — utilisée comme logo")
        );
        pickPosterCivil = registerForActivityResult(
            new ActivityResultContracts.GetContent(),
            uri -> savePhoto(uri, PhotoManager.POSTER_CIVIL, "Affiche mariage civil enregistrée")
        );
        pickPosterBlanche = registerForActivityResult(
            new ActivityResultContracts.GetContent(),
            uri -> savePhoto(uri, PhotoManager.POSTER_BLANCHE, "Affiche bénédiction enregistrée")
        );

        TextInputEditText inputDate = findViewById(R.id.inputDate);
        TextInputEditText inputTime = findViewById(R.id.inputTime);
        TextInputEditText inputVenue = findViewById(R.id.inputVenue);
        TextInputEditText inputMessage = findViewById(R.id.inputMessage);
        CheckBox checkEmbed = findViewById(R.id.checkEmbedName);

        inputDate.setText(prefs.getDate());
        inputTime.setText(prefs.getTime());
        inputVenue.setText(prefs.getVenue());
        inputMessage.setText(prefs.getMessage());
        checkEmbed.setChecked(prefs.embedName());

        refreshPreviews();

        findViewById(R.id.btnPickCouple).setOnClickListener(v -> pickCouple.launch("image/*"));
        findViewById(R.id.btnPickPosterCivil).setOnClickListener(v -> pickPosterCivil.launch("image/*"));
        findViewById(R.id.btnPickPosterBlanche).setOnClickListener(v -> pickPosterBlanche.launch("image/*"));

        MaterialButton btnSave = findViewById(R.id.btnSave);
        btnSave.setOnClickListener(v -> {
            prefs.setDate(inputDate.getText() != null ? inputDate.getText().toString() : "");
            prefs.setTime(inputTime.getText() != null ? inputTime.getText().toString() : "");
            prefs.setVenue(inputVenue.getText() != null ? inputVenue.getText().toString() : "");
            prefs.setMessage(inputMessage.getText() != null ? inputMessage.getText().toString() : "");
            prefs.setEmbedName(checkEmbed.isChecked());
            CloudSync.pushGuestsAsync(this);
            startActivity(new android.content.Intent(this, AddGuestActivity.class));
            finish();
        });
    }

    private void savePhoto(Uri uri, String fileName, String message) {
        if (uri == null) return;
        try {
            PhotoManager.saveFromUri(this, uri, fileName);
            Toast.makeText(this, message, Toast.LENGTH_LONG).show();
            refreshPreviews();
        } catch (Exception e) {
            Toast.makeText(this, "Erreur : " + e.getMessage(), Toast.LENGTH_LONG).show();
        }
    }

    private void refreshPreviews() {
        ImageView logoPreview = findViewById(R.id.logoPreview);
        TextView photoStatus = findViewById(R.id.photoStatus);

        if (PhotoManager.hasCustomCouple(this)) {
            logoPreview.setImageURI(Uri.fromFile(PhotoManager.file(this, PhotoManager.COUPLE)));
            photoStatus.setText("✓ Votre photo est active (logo + invitation)");
        } else {
            logoPreview.setImageResource(R.drawable.couple_photo);
            photoStatus.setText("⚠ Utilisez VOS photos — appuyez sur les boutons ci-dessous");
        }

        if (PhotoManager.hasCustomPoster(this, "mariage-civil")) {
            posterPreview.setImageURI(Uri.fromFile(PhotoManager.file(this, PhotoManager.POSTER_CIVIL)));
        } else {
            posterPreview.setImageResource(R.drawable.template_mariage_civil);
        }
    }
}
