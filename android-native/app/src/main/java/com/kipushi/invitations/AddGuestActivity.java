package com.kipushi.invitations;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

public class AddGuestActivity extends AppCompatActivity implements StyleAdapter.OnStyleSelected {
    private String selectedStyleId = "kipushi-floral";
    private DatabaseHelper db;
    private PrefsHelper prefs;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_add_guest);
        db = new DatabaseHelper(this);
        prefs = new PrefsHelper(this);
        selectedStyleId = prefs.getDefaultStyle();

        findViewById(R.id.btnSettings).setOnClickListener(v ->
            startActivity(new Intent(this, ConfigActivity.class)));

        RecyclerView recycler = findViewById(R.id.styleRecycler);
        recycler.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        StyleAdapter adapter = new StyleAdapter(InvitationStyle.all(), selectedStyleId, this);
        recycler.setAdapter(adapter);

        MaterialButton btnGenerate = findViewById(R.id.btnGenerate);
        btnGenerate.setOnClickListener(v -> generateInvitation(adapter));
    }

    private void generateInvitation(StyleAdapter adapter) {
        TextInputEditText inputName = findViewById(R.id.inputName);
        TextInputEditText inputWhatsapp = findViewById(R.id.inputWhatsapp);
        TextInputEditText inputSeats = findViewById(R.id.inputSeats);
        TextInputEditText inputTable = findViewById(R.id.inputTable);

        String name = inputName.getText() != null ? inputName.getText().toString().trim() : "";
        String phone = inputWhatsapp.getText() != null ? inputWhatsapp.getText().toString().trim() : "";

        if (name.isEmpty() || phone.isEmpty()) {
            Toast.makeText(this, "Nom et WhatsApp requis", Toast.LENGTH_SHORT).show();
            return;
        }

        Guest guest = new Guest();
        guest.fullName = name;
        guest.whatsapp = phone.replaceAll("\\D", "");
        guest.seats = 1;
        try {
            guest.seats = Integer.parseInt(inputSeats.getText().toString().trim());
        } catch (Exception ignored) {}
        guest.tableZone = inputTable.getText() != null ? inputTable.getText().toString().trim() : "";
        guest.styleId = selectedStyleId;
        guest.sent = false;

        long id = db.insertGuest(guest);
        guest.id = id;
        prefs.setDefaultStyle(selectedStyleId);

        Intent intent = new Intent(this, PreviewActivity.class);
        intent.putExtra("guest_id", id);
        startActivity(intent);
    }

    @Override
    public void onStyleSelected(String styleId) {
        selectedStyleId = styleId;
    }
}
