package com.kipushi.invitations;

import android.os.Bundle;
import android.widget.CheckBox;

import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

public class ConfigActivity extends AppCompatActivity {
    private PrefsHelper prefs;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setTheme(R.style.Theme_Invitations_Dark);
        setContentView(R.layout.activity_config);
        prefs = new PrefsHelper(this);

        TextInputEditText inputDate = findViewById(R.id.inputDate);
        TextInputEditText inputVenue = findViewById(R.id.inputVenue);
        TextInputEditText inputMessage = findViewById(R.id.inputMessage);
        CheckBox checkEmbed = findViewById(R.id.checkEmbedName);

        inputDate.setText(prefs.getDate());
        inputVenue.setText(prefs.getVenue());
        inputMessage.setText(prefs.getMessage());
        checkEmbed.setChecked(prefs.embedName());

        MaterialButton btnSave = findViewById(R.id.btnSave);
        btnSave.setOnClickListener(v -> {
            prefs.setDate(inputDate.getText() != null ? inputDate.getText().toString() : "");
            prefs.setVenue(inputVenue.getText() != null ? inputVenue.getText().toString() : "");
            prefs.setMessage(inputMessage.getText() != null ? inputMessage.getText().toString() : "");
            prefs.setEmbedName(checkEmbed.isChecked());
            startActivity(new android.content.Intent(this, AddGuestActivity.class));
            finish();
        });
    }
}
