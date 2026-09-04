package com.kipushi.invitations;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

public class StyleAdapter extends RecyclerView.Adapter<StyleAdapter.VH> {
    public interface OnStyleSelected {
        void onStyleSelected(String styleId);
    }

    private final InvitationStyle[] styles;
    private String selectedId;
    private final OnStyleSelected listener;

    public StyleAdapter(InvitationStyle[] styles, String selectedId, OnStyleSelected listener) {
        this.styles = styles;
        this.selectedId = selectedId;
        this.listener = listener;
    }

    @NonNull
    @Override
    public VH onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View v = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_style, parent, false);
        return new VH(v);
    }

    @Override
    public void onBindViewHolder(@NonNull VH holder, int position) {
        InvitationStyle style = styles[position];
        holder.preview.setImageResource(style.templateRes);
        holder.name.setText(style.name);
        holder.subtitle.setText(style.subtitle);
        boolean selected = style.id.equals(selectedId);
        holder.itemView.setBackgroundResource(selected ? R.drawable.bg_style_selected : R.drawable.bg_style_normal);
        holder.check.setVisibility(selected ? View.VISIBLE : View.GONE);
        holder.itemView.setOnClickListener(v -> {
            selectedId = style.id;
            listener.onStyleSelected(style.id);
            notifyDataSetChanged();
        });
    }

    @Override
    public int getItemCount() {
        return styles.length;
    }

    static class VH extends RecyclerView.ViewHolder {
        ImageView preview, check;
        TextView name, subtitle;

        VH(@NonNull View itemView) {
            super(itemView);
            preview = itemView.findViewById(R.id.stylePreview);
            check = itemView.findViewById(R.id.styleCheck);
            name = itemView.findViewById(R.id.styleName);
            subtitle = itemView.findViewById(R.id.styleSubtitle);
        }
    }
}
