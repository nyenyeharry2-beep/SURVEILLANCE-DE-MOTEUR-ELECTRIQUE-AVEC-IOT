package com.kipushi.invitations;

import android.content.Intent;
import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.EditText;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.google.android.material.chip.Chip;
import com.google.android.material.chip.ChipGroup;

import java.util.ArrayList;
import java.util.List;

public class DashboardActivity extends AppCompatActivity {
    private DatabaseHelper db;
    private GuestAdapter adapter;
    private List<Guest> guests = new ArrayList<>();
    private String tableFilter = "Toutes";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_dashboard);
        db = new DatabaseHelper(this);

        adapter = new GuestAdapter();
        RecyclerView list = findViewById(R.id.guestRecycler);
        list.setLayoutManager(new LinearLayoutManager(this));
        list.setAdapter(adapter);

        EditText search = findViewById(R.id.searchInput);
        search.addTextChangedListener(new TextWatcher() {
            @Override public void beforeTextChanged(CharSequence s, int a, int b, int c) {}
            @Override public void onTextChanged(CharSequence s, int a, int b, int c) { refresh(s.toString()); }
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
        guests = db.getAllGuests(query, tableFilter);
        adapter.notifyChanged(guests);
        updateTableChips();
        TextView stats = findViewById(R.id.statsText);
        int seats = 0;
        for (Guest g : guests) seats += g.seats;
        stats.setText(guests.size() + " invité(s) • " + seats + " place(s)");
    }

    private void updateTableChips() {
        ChipGroup group = findViewById(R.id.tableChips);
        group.removeAllViews();
        for (String table : db.getDistinctTables()) {
            Chip chip = new Chip(this);
            chip.setText(table);
            chip.setCheckable(true);
            chip.setChecked(table.equals(tableFilter));
            chip.setOnClickListener(v -> {
                tableFilter = table;
                EditText search = findViewById(R.id.searchInput);
                refresh(search.getText().toString());
            });
            group.addView(chip);
        }
    }

    private class GuestAdapter extends RecyclerView.Adapter<GuestAdapter.VH> {
        private List<Guest> items = new ArrayList<>();

        void notifyChanged(List<Guest> data) {
            items = data;
            notifyDataSetChanged();
        }

        @NonNull
        @Override
        public VH onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
            View v = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_guest, parent, false);
            return new VH(v);
        }

        @Override
        public void onBindViewHolder(@NonNull VH h, int pos) {
            Guest g = items.get(pos);
            h.name.setText(g.fullName);
            h.phone.setText(g.whatsapp);
            h.meta.setText((g.tableZone != null && !g.tableZone.isEmpty() ? g.tableZone : "Sans table")
                + " • " + g.seats + " place(s) • " + InvitationStyle.getById(g.styleId).name);
            h.badge.setText(g.sent ? "Envoyé ✓" : "Non envoyé");
            h.badge.setBackgroundResource(g.sent ? R.drawable.bg_chip_active : R.drawable.bg_chip_normal);

            h.itemView.setOnClickListener(v -> {
                Intent i = new Intent(DashboardActivity.this, PreviewActivity.class);
                i.putExtra("guest_id", g.id);
                startActivity(i);
            });
            h.btnDelete.setOnClickListener(v -> new AlertDialog.Builder(DashboardActivity.this)
                .setTitle("Supprimer")
                .setMessage("Supprimer " + g.fullName + " ?")
                .setPositiveButton("Supprimer", (d, w) -> { db.deleteGuest(g.id); refresh(""); })
                .setNegativeButton("Annuler", null)
                .show());
        }

        @Override
        public int getItemCount() { return items.size(); }

        class VH extends RecyclerView.ViewHolder {
            TextView name, phone, meta, badge;
            View btnDelete;
            VH(View v) {
                super(v);
                name = v.findViewById(R.id.guestName);
                phone = v.findViewById(R.id.guestPhone);
                meta = v.findViewById(R.id.guestMeta);
                badge = v.findViewById(R.id.guestBadge);
                btnDelete = v.findViewById(R.id.btnDelete);
            }
        }
    }
}
