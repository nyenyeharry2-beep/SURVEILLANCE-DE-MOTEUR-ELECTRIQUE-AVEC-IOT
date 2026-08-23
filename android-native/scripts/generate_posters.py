#!/usr/bin/env python3
"""Generate realistic wedding poster assets for Android app."""

from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent.parent
DRAWABLE = ROOT / "app/src/main/res/drawable"
ASSETS = ROOT / "app/src/main/assets/posters"
DRAWABLE.mkdir(parents=True, exist_ok=True)
ASSETS.mkdir(parents=True, exist_ok=True)

W, H = 1200, 1700


def font(size, bold=False, italic=False):
    opts = []
    if bold and italic:
        opts = ["DejaVuSerif-BoldItalic.ttf"]
    elif bold:
        opts = ["DejaVuSerif-Bold.ttf", "LiberationSerif-Bold.ttf"]
    elif italic:
        opts = ["DejaVuSerif-Italic.ttf"]
    else:
        opts = ["DejaVuSerif.ttf", "LiberationSerif-Regular.ttf"]
    for name in opts:
        for base in ["/usr/share/fonts/truetype/dejavu/", "/usr/share/fonts/truetype/liberation/"]:
            try:
                return ImageFont.truetype(base + name, size)
            except OSError:
                continue
    return ImageFont.load_default()


def draw_roses(draw, cx, cy, scale=1.0, warm=False):
    colors = [(220, 120, 100), (240, 160, 130), (200, 80, 90), (255, 180, 150)] if warm else [
        (122, 46, 140), (160, 80, 180), (219, 112, 147), (140, 60, 160)]
    for i, (col, r) in enumerate(zip(colors, [24, 18, 14, 10])):
        ox, oy = (i % 2) * 18 * scale, (i // 2) * 14 * scale
        rs = int(r * scale)
        draw.ellipse([cx + ox - rs, cy + oy - rs, cx + ox + rs, cy + oy + rs], fill=col)


def create_affiche_blanche():
    """Main white poster — Moïse & Sarah 12.9.2026."""
    img = Image.new("RGB", (W, H), (252, 250, 246))
    draw = ImageDraw.Draw(img)

    # Swirl texture
    for i in range(0, W, 80):
        draw.arc([i - 40, 200, i + 200, 600], 0, 180, fill=(245, 240, 235), width=2)

    draw_roses(draw, 80, 80, 2.0, warm=True)
    draw_roses(draw, W - 80, 80, 2.0, warm=True)
    draw_roses(draw, 80, H - 80, 1.8, warm=True)
    draw_roses(draw, W - 80, H - 80, 1.8, warm=True)

    # Center dotted divider
    mid = W // 2
    for y in range(120, H - 120, 8):
        draw.ellipse([mid - 2, y, mid + 2, y + 4], fill=(180, 180, 180))

    # LEFT — couple zone
    draw.rectangle([40, 180, mid - 30, H - 200], fill=(248, 246, 242))
    # Couple silhouette placeholder
    draw.ellipse([120, 400, 220, 500], fill=(230, 225, 240))
    draw.rounded_rectangle([100, 480, 280, 900], radius=20, fill=(25, 55, 120))
    draw.ellipse([300, 350, 420, 470], fill=(245, 230, 220))
    draw.rounded_rectangle([280, 460, 450, 950], radius=25, fill=(210, 140, 60))

    # RIGHT — text zone
    gold = (180, 140, 80)
    draw.text((mid + 40, 100), "Invitation", fill=gold, font=font(72, italic=True))
    draw.line([(mid + 40, 175), (W - 60, 175)], fill=gold, width=2)

    draw.text((mid + 40, 200), "Mme, Mlle, M., Couple :", fill=(40, 40, 40), font=font(22))
    draw.text((mid + 40, 250), "Les familles NKUBA et BANZA ont le réel plaisir", fill=(35, 35, 35), font=font(20))
    draw.text((mid + 40, 285), "de vous inviter à prendre part au mariage", fill=(35, 35, 35), font=font(20))
    draw.text((mid + 40, 320), "de leur fils et fille", fill=(35, 35, 35), font=font(20))

    draw.text((mid + 40, 380), "MOISE NKUBA", fill=(25, 55, 120), font=font(36, bold=True))
    draw.line([(mid + 40, 430), (mid + 200, 430)], fill=gold, width=2)
    draw.text((mid + 40, 445), "SARAH KASONGO", fill=(160, 110, 60), font=font(36, bold=True))

    # Date badge
    draw.rounded_rectangle([mid + 40, 520, mid + 280, 580], radius=15, fill=(50, 50, 50))
    draw.text((mid + 70, 535), "📅  12. 9. 2026", fill=(255, 255, 255), font=font(28, bold=True))

    draw.text((mid + 40, 620), "À 14h : Bénédiction nuptiale", fill=(30, 30, 30), font=font(18, bold=True))
    draw.text((mid + 40, 650), "Église 30ème CPCO, paroisse Étoile du Matin,", fill=(50, 50, 50), font=font(15))
    draw.text((mid + 40, 675), "Av. du 24 novembre coin Kasumbalesa, Kipushi.", fill=(50, 50, 50), font=font(15))

    draw.text((mid + 40, 730), "À 19h : Soirée dansante", fill=(30, 30, 30), font=font(18, bold=True))
    draw.text((mid + 40, 760), "Salle Elvine (Chez Trésor), Av. du 30 Juin,", fill=(50, 50, 50), font=font(15))
    draw.text((mid + 40, 785), "Q/Kamarenge, Ville de Kipushi.", fill=(50, 50, 50), font=font(15))

    draw.text((mid + 40, H - 160), "NB : Cadeau non emballé", fill=(80, 80, 80), font=font(16))
    draw.text((mid + 40, H - 130), "Votre présence, est notre plus beau cadeau", fill=(80, 80, 80), font=font(16, italic=True))

    return img


def create_mariage_civil():
    """Purple civil marriage poster — Sarah & Moïse."""
    img = Image.new("RGB", (W, H), (255, 255, 255))
    draw = ImageDraw.Draw(img)
    draw_roses(draw, 90, 90, 2.2)
    draw_roses(draw, W - 90, H - 90, 2.2)
    draw.arc([480, 30, W + 80, H], 270, 90, fill=(230, 210, 240), width=3)

    draw.ellipse([120, 350, 220, 450], fill=(240, 235, 250))
    draw.rounded_rectangle([80, 430, 260, 850], radius=15, fill=(255, 255, 255))
    draw.ellipse([300, 280, 400, 380], fill=(245, 240, 250))
    draw.rounded_rectangle([260, 370, 440, 900], radius=15, fill=(255, 255, 255))

    gold = (198, 156, 78)
    pink = (219, 112, 147)
    draw.text((W - 350, 80), "Invitation", fill=gold, font=font(80, bold=True))
    draw.text((520, 200), "Mme, Mlle, Mr, Couple :", fill=(50, 50, 50), font=font(22))
    lines = ["Les familles NKUBA et BANZA vous invitent", "à prendre part au mariage civil", "de leur fils et fille :"]
    y = 280
    for line in lines:
        draw.text((520, y), line, fill=(40, 40, 40), font=font(24))
        y += 36
    draw.text((520, y + 10), "Moïse NKUBA & Sarah KASONGO", fill=pink, font=font(40, italic=True))
    y += 70
    for icon, txt in [("📅", "Date : Vendredi, le 11 Septembre 2026"), ("🕐", "Heure : 11h00"), ("📍", "Lieu : Commune de Kipushi, Ville de KIPUSHI")]:
        draw.text((520, y), f"{icon}  {txt}", fill=(45, 45, 45), font=font(22))
        y += 40
    draw.text((520, H - 150), "Votre présence fera notre immense joie.", fill=(55, 55, 55), font=font(22))
    return img


def create_icon(source: Image.Image, size=1024):
    crop = source.crop((source.width // 2 - 200, 0, source.width // 2 + 400, 400))
    icon = crop.resize((size, size), Image.Resampling.LANCZOS)
    padded = Image.new("RGB", (size, size), (252, 250, 246))
    inner = int(size * 0.9)
    resized = icon.resize((inner, inner), Image.Resampling.LANCZOS)
    padded.paste(resized, ((size - inner) // 2, (size - inner) // 2))
    return padded


def main():
    blanche = create_affiche_blanche()
    civil = create_mariage_civil()
    blanche.save(DRAWABLE / "template_affiche_blanche.png", quality=98)
    blanche.save(ASSETS / "affiche_blanche_bg.png", quality=98)
    civil.save(DRAWABLE / "template_mariage_civil.png", quality=98)
    civil.save(ASSETS / "mariage_civil_bg.png", quality=98)

    icon = create_icon(blanche)
    for folder in ["mipmap-mdpi", "mipmap-hdpi", "mipmap-xhdpi", "mipmap-xxhdpi", "mipmap-xxxhdpi"]:
        p = ROOT / "app/src/main/res" / folder
        p.mkdir(parents=True, exist_ok=True)
        icon.save(p / "ic_launcher.png")
    icon.save(DRAWABLE / "app_logo.png")

    # Floral corner for HTML template
    corner = Image.new("RGBA", (200, 200), (0, 0, 0, 0))
    cd = ImageDraw.Draw(corner)
    draw_roses(cd, 70, 70, 1.5, warm=True)
    corner.save(ASSETS / "floral_warm.png")
    print("Posters and icon generated.")


if __name__ == "__main__":
    main()
