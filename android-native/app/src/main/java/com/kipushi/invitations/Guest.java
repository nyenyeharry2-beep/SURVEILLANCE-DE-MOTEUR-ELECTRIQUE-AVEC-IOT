package com.kipushi.invitations;

public class Guest {
    public long id;
    public String fullName;
    public String whatsapp;
    public int seats;
    public String tableZone;
    public String styleId;
    public boolean sent;
    public String createdAt;

    public Guest() {
        styleId = "kipushi-floral";
        seats = 1;
    }
}
