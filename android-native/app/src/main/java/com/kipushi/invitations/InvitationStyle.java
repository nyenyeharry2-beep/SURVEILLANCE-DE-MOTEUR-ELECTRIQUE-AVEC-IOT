package com.kipushi.invitations;

public class InvitationStyle {
    public final String id;
    public final String name;
    public final String subtitle;
    public final String htmlAsset;
    public final int templateRes;
    public final float nameX, nameY, nameW, nameSize;
    public final int nameColor;
    public final float qrX, qrY, qrSize;
    public final int qrSizePx;
    public final String qrColor;
    public final boolean centerName;

    public InvitationStyle(String id, String name, String subtitle,
                           String htmlAsset, int templateRes,
                           float nameX, float nameY, float nameW, float nameSize, int nameColor,
                           float qrX, float qrY, float qrSize, int qrSizePx, String qrColor,
                           boolean centerName) {
        this.id = id;
        this.name = name;
        this.subtitle = subtitle;
        this.htmlAsset = htmlAsset;
        this.templateRes = templateRes;
        this.nameX = nameX;
        this.nameY = nameY;
        this.nameW = nameW;
        this.nameSize = nameSize;
        this.nameColor = nameColor;
        this.qrX = qrX;
        this.qrY = qrY;
        this.qrSize = qrSize;
        this.qrSizePx = qrSizePx;
        this.qrColor = qrColor;
        this.centerName = centerName;
    }

    public static InvitationStyle[] all() {
        return new InvitationStyle[]{
            new InvitationStyle(
                "mariage-civil", "Mariage Civil", "Violet & or — classique",
                "invitations/mariage_civil.html", R.drawable.template_mariage_civil,
                0.43f, 0.135f, 0.55f, 26f, 0xFF2c2c2c,
                0.06f, 0.78f, 0.15f, 200, "#6B2D82", false),
            new InvitationStyle(
                "affiche-blanche", "Bénédiction", "Blanche & bronze",
                "invitations/affiche_blanche.html", R.drawable.template_affiche_blanche,
                0.52f, 0.14f, 0.45f, 28f, 0xFF1a1a1a,
                0.05f, 0.78f, 0.14f, 200, "#002366", false),
            new InvitationStyle(
                "arc-dore", "Arc Doré", "Photo en arche romantique",
                "invitations/arc_dore.html", R.drawable.template_mariage_civil,
                0.5f, 0.22f, 0.8f, 28f, 0xFF1a1a1a,
                0.04f, 0.82f, 0.15f, 200, "#B8956B", true),
            new InvitationStyle(
                "cercle-royal", "Cercle Royal", "Portrait circulaire violet",
                "invitations/cercle_royal.html", R.drawable.template_mariage_civil,
                0.5f, 0.38f, 0.8f, 28f, 0xFFFFFFFF,
                0.04f, 0.82f, 0.15f, 200, "#FFD700", true),
            new InvitationStyle(
                "ticket-chic", "Ticket Chic", "Format billet élégant",
                "invitations/ticket_chic.html", R.drawable.template_affiche_blanche,
                0.55f, 0.28f, 0.4f, 30f, 0xFF111111,
                0.04f, 0.78f, 0.12f, 180, "#1A237E", false),
            new InvitationStyle(
                "jardin-floral", "Jardin Floral", "Cadre fleuri vert sauge",
                "invitations/jardin_floral.html", R.drawable.template_mariage_civil,
                0.5f, 0.48f, 0.8f, 28f, 0xFF1b5e20,
                0.04f, 0.82f, 0.15f, 200, "#2E7D32", true),
        };
    }

    public static InvitationStyle getById(String id) {
        for (InvitationStyle s : all()) {
            if (s.id.equals(id)) return s;
        }
        return all()[0];
    }
}
