package com.kipushi.invitations;

import android.content.Intent;
import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.widget.ArrayAdapter;
import android.widget.EditText;
import android.widget.ListView;

import androidx.appcompat.app.AppCompatActivity;

import java.util.ArrayList;
import java.util.List;

public class DashboardActivity extends AppCompatActivity {
    private DatabaseHelper db;
    private ArrayAdapter<String> adapter;
    private List<Guest> guests = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_dashboard);
        db = new DatabaseHelper(this);

        adapter = new ArrayAdapter<>(this, android.R.layout.simple_list_item_1, new ArrayList<>());
        ListView list = findViewById(R.id.guestList);
        list.setAdapter(adapter);

        list.setOnItemClickListener((parent, view, position, id) -> {
            Guest g = guests.get(position);
            Intent intent = new Intent(this, PreviewActivity.class);
            intent.putExtra("guest_id", g.id);
            startActivity(intent);
        });

        EditText search = findViewById(R.id.searchInput);
        search.addTextChangedListener(new TextWatcher() {
            @Override public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override public void onTextChanged(CharSequence s, int start, int before, int count) { refresh(s.toString()); }
            @Override public void afterTextChanged(Editable s) {}
        });

        refresh("");
    }

    @Override
    protected void onResume() {
        super.onResume();
        EditText search = findViewById(R.id.searchInput);
        refresh(search.getText().toString());
    }

    private void refresh(String query) {
        guests = db.getAllGuests(query);
        List<String> labels = new ArrayList<>();
        for (Guest g : guests) {
            String status = g.sent ? "✓" : "○";
            labels.add(status + " " + g.fullName + " • " + g.whatsapp + " • " + g.tableZone);
        }
        adapter.clear();
        adapter.addAll(labels);
        adapter.notifyDataSetChanged();
    }
}
