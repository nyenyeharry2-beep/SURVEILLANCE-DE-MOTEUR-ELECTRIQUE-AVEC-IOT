package com.kipushi.invitations;

import android.content.Intent;
import android.database.Cursor;
import android.net.Uri;
import android.os.Bundle;
import android.provider.ContactsContract;
import android.widget.Toast;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

public class AddGuestActivity extends AppCompatActivity implements StyleAdapter.OnStyleSelected {
    private String selectedStyleId = "affiche-blanche";
    private DatabaseHelper db;
    private PrefsHelper prefs;
    private ActivityResultLauncher<Void> pickContactLauncher;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_add_guest);
        db = new DatabaseHelper(this);
        prefs = new PrefsHelper(this);
        selectedStyleId = prefs.getDefaultStyle();

        pickContactLauncher = registerForActivityResult(
            new ActivityResultContracts.PickContact(),
            this::handlePickedContact
        );

        findViewById(R.id.btnSettings).setOnClickListener(v ->
            startActivity(new Intent(this, ConfigActivity.class)));

        findViewById(R.id.btnImportContact).setOnClickListener(v -> {
            try {
                pickContactLauncher.launch(null);
            } catch (Exception e) {
                Toast.makeText(this, "Contacts non disponibles", Toast.LENGTH_SHORT).show();
            }
        });

        RecyclerView recycler = findViewById(R.id.styleRecycler);
        recycler.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        StyleAdapter adapter = new StyleAdapter(InvitationStyle.all(), selectedStyleId, this);
        recycler.setAdapter(adapter);

        findViewById(R.id.btnGenerate).setOnClickListener(v -> generateInvitation());
    }

    private void handlePickedContact(Uri contactUri) {
        if (contactUri == null) return;
        Cursor cursor = getContentResolver().query(contactUri, null, null, null, null);
        if (cursor == null || !cursor.moveToFirst()) return;

        String id = cursor.getString(cursor.getColumnIndexOrThrow(ContactsContract.Contacts._ID));
        String name = cursor.getString(cursor.getColumnIndexOrThrow(ContactsContract.Contacts.DISPLAY_NAME));
        cursor.close();

        TextInputEditText inputName = findViewById(R.id.inputName);
        TextInputEditText inputWhatsapp = findViewById(R.id.inputWhatsapp);
        inputName.setText(name);

        Cursor phones = getContentResolver().query(
            ContactsContract.CommonDataKinds.Phone.CONTENT_URI,
            null,
            ContactsContract.CommonDataKinds.Phone.CONTACT_ID + "=?",
            new String[]{id}, null);

        if (phones != null && phones.moveToFirst()) {
            String phone = phones.getString(phones.getColumnIndexOrThrow(ContactsContract.CommonDataKinds.Phone.NUMBER));
            inputWhatsapp.setText(phone.replaceAll("\\D", ""));
            phones.close();
        }
        Toast.makeText(this, "Contact importé : " + name, Toast.LENGTH_SHORT).show();
    }

    private void generateInvitation() {
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
