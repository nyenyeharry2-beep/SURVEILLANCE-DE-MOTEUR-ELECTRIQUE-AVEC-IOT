#!/usr/bin/env python3
"""
Construit les assets officiels Moïse NKUBA & Sarah KASONGO
— affiche violette 1200×1700 avec photo couple intégrée.
"""
from __future__ import annotations

from pathlib import Path
from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = Path(__file__).resolve().parent.parent
HTDOCS = ROOT / "invitation-mariage-nkuba-kasongo/htdocs/assets"
ANDROID_DRAW = ROOT / "android-native/app/src/main/res/drawable"
ANDROID_POSTERS = ROOT / "android-native/app/src/main/assets/posters"
FONTS = HTDOCS / "fonts"

W, H = 1200, 1700


def load_font(name: str, size: int) -> ImageFont.FreeTypeFont:
    path = FONTS / name
    if path.exists():
        return ImageFont.truetype(str(path), size)
    fallback = "/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf"
    return ImageFont.truetype(fallback, size)


def draw_rose_cluster(draw: ImageDraw.ImageDraw, cx: int, cy: int, scale: float = 1.0, flip: bool = False) -> None:
    colors = [(107, 45, 130), (142, 68, 173), (186, 104, 200), (75, 35, 95)]
    offsets = [(-30, -20), (20, -25), (-15, 15), (25, 10), (0, -5), (-40, 5), (35, -5)]
    for i, (ox, oy) in enumerate(offsets):
        r = int((28 - i * 2) * scale)
        x = cx + (ox if not flip else -ox)
        y = cy + oy
        draw.ellipse([x - r, y - r, x + r, y + r], fill=colors[i % len(colors)])
    # Leaves
    green = (46, 125, 50)
    for lx, ly, lw, lh in [(-50, 10, 35, 18), (40, 20, 30, 16), (-20, 40, 28, 14)]:
        x = cx + (lx if not flip else -lx)
        draw.ellipse([x, cy + ly, x + lw, cy + ly + lh], fill=green)


def draw_vine_swirl(draw: ImageDraw.ImageDraw, x: int, y: int, h: int) -> None:
    purple = (107, 45, 130)
    for i in range(0, h, 8):
        ox = int(15 * __import__("math").sin(i / 25))
        draw.line([(x + ox, y + i), (x + ox + 3, y + i + 8)], fill=purple, width=2)


def create_couple_photo() -> Image.Image:
    """Photo couple Moïse & Sarah — tenue blanche, style photo mariage."""
    cw, ch = 800, 1100
    img = Image.new("RGB", (cw, ch), (235, 232, 228))
    draw = ImageDraw.Draw(img)

    # Patio carrelé
    for y in range(int(ch * 0.62), ch):
        shade = 225 + (y % 40) // 20 * 8
        draw.line([(0, y), (cw, y)], fill=(shade, shade - 3, shade - 6))
    for x in range(0, cw, 80):
        draw.line([(x, int(ch * 0.62)), (x, ch)], fill=(210, 208, 205), width=1)

    # Porte vitrée
    draw.rectangle([280, 60, 520, 720], fill=(248, 248, 252), outline=(200, 200, 205), width=4)
    for gx in range(300, 510, 35):
        draw.line([(gx, 70), (gx, 710)], fill=(225, 228, 232), width=1)

    # Plante
    draw.ellipse([40, 480, 160, 680], fill=(34, 110, 50))
    draw.ellipse([60, 430, 140, 530], fill=(50, 140, 60))

    skin = (95, 65, 45)
    white = (252, 252, 252)

    # Homme assis
    draw.ellipse([180, 380, 300, 500], fill=skin)
    draw.rounded_rectangle([150, 490, 330, 820], radius=18, fill=white)
    draw.rectangle([170, 520, 310, 590], fill=(245, 245, 245))
    draw.rounded_rectangle([130, 800, 350, 860], radius=12, fill=(248, 248, 248))

    # Femme debout
    draw.ellipse([360, 280, 490, 410], fill=(105, 72, 52))
    draw.ellipse([430, 265, 510, 340], fill=(25, 20, 18))
    draw.rounded_rectangle([340, 400, 520, 820], radius=14, fill=white)
    draw.rectangle([380, 430, 480, 470], fill=(240, 240, 240))
    draw.polygon([(350, 810), (390, 810), (380, 880)], fill=(18, 90, 48))
    draw.polygon([(480, 810), (520, 810), (510, 880)], fill=(18, 90, 48))
    draw.ellipse([260, 430, 310, 470], fill=skin)

    # Fondu bas léger seulement
    fade = Image.new("L", (cw, ch), 255)
    fd = ImageDraw.Draw(fade)
    for y in range(int(ch * 0.88), ch):
        v = int(255 * (y - ch * 0.88) / (ch * 0.12))
        fd.line([(0, y), (cw, y)], fill=v)
    white_bg = Image.new("RGB", (cw, ch), (255, 255, 255))
    img = Image.composite(white_bg, img, fade)
    return img


def create_official_poster(couple: Image.Image) -> Image.Image:
    """Affiche officielle violette complète — fond + couple intégré à gauche."""
    img = Image.new("RGB", (W, H), (255, 255, 255))
    draw = ImageDraw.Draw(img)

    # Filigrane rose central
    wm = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    wmd = ImageDraw.Draw(wm)
    wmd.ellipse([350, 500, 850, 1000], fill=(255, 200, 210, 35))
    wmd.ellipse([420, 580, 780, 920], fill=(255, 180, 200, 25))
    img = Image.alpha_composite(img.convert("RGBA"), wm).convert("RGB")
    draw = ImageDraw.Draw(img)

    # Bordure violette courbe droite
    draw.arc([900, 30, 1180, H - 30], -90, 90, fill=(107, 45, 130), width=3)

    # Roses coins
    draw_rose_cluster(draw, 100, 100, 1.3)
    draw_rose_cluster(draw, W - 100, 100, 1.3, flip=True)
    draw_rose_cluster(draw, 90, H - 180, 1.1)
    draw_rose_cluster(draw, W - 90, H - 180, 1.1, flip=True)
    draw_vine_swirl(draw, 35, H - 350, 200)
    draw_vine_swirl(draw, W - 45, H - 350, 200)

    # Ornement haut centre
    draw.arc([520, 15, 680, 80], 200, 340, fill=(107, 45, 130), width=2)

    # Photo couple à gauche
    target_h = H
    target_w = 480
    scale = min(target_w / couple.width, target_h / couple.height)
    nw, nh = int(couple.width * scale), int(couple.height * scale)
    couple_resized = couple.resize((nw, nh), Image.Resampling.LANCZOS)
    paste_y = 0
    img.paste(couple_resized, (0, paste_y))

    # Fondu photo vers fond blanc à droite et bas
    mask = Image.new("L", (nw, nh), 255)
    md = ImageDraw.Draw(mask)
    fade_x = max(0, nw - 130)
    for x in range(fade_x, nw):
        v = int(255 * (nw - x) / max(1, nw - fade_x))
        md.line([(x, 0), (x, nh)], fill=v)
    fade_y = int(nh * 0.75)
    for y in range(fade_y, nh):
        v = int(255 * (nh - y) / max(1, nh - fade_y))
        for x in range(nw):
            old = mask.getpixel((x, y))
            mask.putpixel((x, y), min(old, v))
    img.paste(couple_resized, (0, paste_y), mask)

    draw = ImageDraw.Draw(img)
    fv = load_font("GreatVibes.ttf", 96)
    fp = load_font("PlayfairDisplay-Regular.ttf", 22)
    fb = load_font("PlayfairDisplay.ttf", 22)
    fs = load_font("GreatVibes.ttf", 52)

    gold = (197, 160, 89)
    purple = (107, 45, 130)
    pink = (216, 27, 96)
    dark = (40, 40, 40)

    # Titre Invitation
    draw.text((720, 100), "Invitation", fill=gold, font=fv, anchor="mm")
    draw.line([(580, 155), (860, 155)], fill=gold, width=2)
    draw.text((720, 148), "❧", fill=gold, font=load_font("PlayfairDisplay-Regular.ttf", 18), anchor="mm")

    # Ligne invité (pointillés — le nom sera overlay)
    draw.text((530, 265), "Mme, Mlle, Mr, Couple :", fill=dark, font=fp)
    for dx in range(530, 1080, 14):
        draw.line([(dx, 310), (dx + 8, 310)], fill=(136, 136, 136), width=2)

    # Texte intro
    intro = "Les familles NKUBA et BANZA vous invitent\nà prendre part au mariage civil\nde leur fils et fille :"
    y = 340
    for line in intro.split("\n"):
        draw.text((720, y), line, fill=dark, font=fp, anchor="mm")
        y += 32

    # Noms couple
    draw.text((720, y + 30), "Moïse NKUBA & Sarah KASONGO", fill=pink, font=fs, anchor="mm")

    # Détails avec icônes
    details = [
        ((66, 133, 244), "Date", "Vendredi, le 11 Septembre 2026"),
        ((192, 57, 43), "Time", "11h00"),
        ((39, 174, 96), "Address", "Commune de Kipushi, Ville de KIPUSHI"),
    ]
    y = 920
    for color, label, value in details:
        draw.ellipse([530, y - 18, 574, y + 26], fill=color)
        draw.text((552, y + 4), label[0], fill=(255, 255, 255), font=fb, anchor="mm")
        draw.text((590, y), f"{label} : {value}", fill=dark, font=fb)
        y += 58

    # Message de clôture
    draw.text((720, 1180), "Votre présence fera notre immense joie.", fill=(68, 68, 68), font=fp, anchor="mm")

    return img


def create_blanche_poster(couple: Image.Image) -> Image.Image:
    """Affiche bénédiction blanche."""
    img = Image.new("RGB", (W, H), (255, 253, 249))
    draw = ImageDraw.Draw(img)
    draw.line([(600, 80), (600, H - 80)], fill=(196, 184, 168), width=2)

    left = couple.resize((560, 1200), Image.Resampling.LANCZOS)
    img.paste(left, (20, 200))

    fv = load_font("GreatVibes.ttf", 80)
    fb = load_font("PlayfairDisplay.ttf", 20)
    draw.text((880, 120), "Bénédiction", fill=(184, 149, 107), font=fv, anchor="mm")
    draw.text((880, 220), "Mme, Mlle, M., Couple :", fill=(40, 40, 40), font=fb, anchor="mm")
    draw.text((880, 320), "Moïse NKUBA", fill=(0, 35, 102), font=load_font("PlayfairDisplay.ttf", 34), anchor="mm")
    draw.text((880, 370), "Sarah KASONGO", fill=(255, 140, 0), font=load_font("PlayfairDisplay.ttf", 34), anchor="mm")
    draw.rounded_rectangle([750, 450, 1010, 510], radius=10, fill=(50, 50, 50))
    draw.text((880, 480), "11 Septembre 2026", fill=(255, 255, 255), font=fb, anchor="mm")
    return img


def create_icon(couple: Image.Image, size: int = 512) -> Image.Image:
    crop = couple.crop((120, 200, 520, 750)).resize((size, size), Image.Resampling.LANCZOS)
    out = Image.new("RGB", (size, size), (255, 255, 255))
    out.paste(crop, (0, 0))
    return out


def save_all() -> None:
    couple = create_couple_photo()
    civil = create_official_poster(couple)
    blanche = create_blanche_poster(couple)
    icon = create_icon(couple)

    targets = [
        (HTDOCS / "couple_photo.png", couple),
        (HTDOCS / "template_mariage_civil.png", civil),
        (HTDOCS / "template_affiche_blanche.png", blanche),
        (HTDOCS / "app-icon.png", icon),
        (HTDOCS / "uploads" / "poster_civil.jpg", civil),
        (HTDOCS / "uploads" / "couple_logo.jpg", couple.crop((120, 200, 520, 750))),
        (HTDOCS / "uploads" / "poster_blanche.jpg", blanche),
        (ANDROID_DRAW / "couple_photo.png", couple),
        (ANDROID_DRAW / "template_mariage_civil.png", civil),
        (ANDROID_DRAW / "template_affiche_blanche.png", blanche),
        (ANDROID_DRAW / "app_logo.png", icon),
        (ANDROID_POSTERS / "couple_photo.png", couple),
        (ANDROID_POSTERS / "mariage_civil_bg.png", civil),
        (ANDROID_POSTERS / "affiche_blanche_bg.png", blanche),
    ]

    for path, im in targets:
        path.parent.mkdir(parents=True, exist_ok=True)
        if path.suffix.lower() == ".jpg":
            im.convert("RGB").save(path, quality=92)
        else:
            im.save(path, quality=95)
        print(f"  ✓ {path.relative_to(ROOT)}")

    # Sync php-backend
    php = ROOT / "php-backend/assets"
    for name in ["couple_photo.png", "template_mariage_civil.png", "template_affiche_blanche.png", "app-icon.png"]:
        src = HTDOCS / name
        if src.exists():
            dst = php / name
            dst.parent.mkdir(parents=True, exist_ok=True)
            Image.open(src).save(dst, quality=95)
            print(f"  ✓ {dst.relative_to(ROOT)}")


if __name__ == "__main__":
    print("Construction assets officiels Moïse & Sarah…")
    save_all()
    print("Terminé.")
