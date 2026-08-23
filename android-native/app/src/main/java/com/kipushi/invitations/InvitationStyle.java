package com.kipushi.invitations;

public class InvitationStyle {
    public final String id;
    public final String name;
    public final String subtitle;
    public final int templateRes;
    public final float nameX, nameY, nameW, nameSize;
    public final int nameColor;
    public final float qrX, qrY, qrSize;
    public final boolean centerName;

    public InvitationStyle(String id, String name, String subtitle, int templateRes,
                           float nameX, float nameY, float nameW, float nameSize, int nameColor,
                           float qrX, float qrY, float qrSize, boolean centerName) {
        this.id = id;
        this.name = name;
        this.subtitle = subtitle;
        this.templateRes = templateRes;
        this.nameX = nameX;
        this.nameY = nameY;
        this.nameW = nameW;
        this.nameSize = nameSize;
        this.nameColor = nameColor;
        this.qrX = qrX;
        this.qrY = qrY;
        this.qrSize = qrSize;
        this.centerName = centerName;
    }

    public static InvitationStyle[] all() {
        return new InvitationStyle[]{
            new InvitationStyle("kipushi-floral", "Style Kipushi Floral", "Élégant & Romantique",
                R.drawable.template_sarah, 0.48f, 0.168f, 0.48f, 22f, 0xFF2c2c2c, 0.065f, 0.78f, 0.16f, false),
            new InvitationStyle("royal-bordeaux", "Royal Bordeaux", "Classique & noble",
                R.drawable.template_royal_bordeaux, 0.08f, 0.42f, 0.84f, 38f, 0xFFFFFFFF, 0.35f, 0.72f, 0.22f, true),
            new InvitationStyle("ivory-prestige", "Ivory Prestige", "Lumineux & raffiné",
                R.drawable.template_ivory, 0.1f, 0.38f, 0.8f, 32f, 0xFF5C1A1A, 0.38f, 0.7f, 0.2f, true),
            new InvitationStyle("ville-kipushi", "Style Ville de Kipushi", "Moderne & Raffiné",
                R.drawable.template_ville, 0.08f, 0.35f, 0.84f, 30f, 0xFFFFFFFF, 0.72f, 0.78f, 0.18f, true),
        };
    }

    public static InvitationStyle getById(String id) {
        for (InvitationStyle s : all()) {
            if (s.id.equals(id)) return s;
        }
        return all()[0];
    }
}
