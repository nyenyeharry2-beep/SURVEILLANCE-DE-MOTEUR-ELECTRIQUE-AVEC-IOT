#!/usr/bin/env python3
"""Generate couple photo, poster previews and app icon."""

from pathlib import Path
from PIL import Image, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parent.parent
POSTERS = ROOT / "app/src/main/res/drawable"
ASSETS = ROOT / "app/src/main/assets/posters"
POSTERS.mkdir(parents=True, exist_ok=True)
ASSETS.mkdir(parents=True, exist_ok=True)

COUPLE_W, COUPLE_H = 800, 1100


def font(size, bold=False):
    paths = [
        "/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf" if bold else "/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf",
    ]
    for p in paths:
        try:
            return ImageFont.truetype(p, size)
        except Exception:
            pass
    from PIL import ImageFont
    return ImageFont.load_default()


from PIL import ImageFont  # noqa: E402


def create_couple_photo() -> Image.Image:
    """Portrait couple — homme bleu, femme orange (logo + affiche)."""
    img = Image.new("RGB", (COUPLE_W, COUPLE_H), (253, 250, 245))
    draw = ImageDraw.Draw(img)

    # Soft swirl watermark
    for i in range(0, COUPLE_W, 100):
        draw.arc([i - 50, 200, i + 250, 500], 0, 180, fill=(248, 244, 238), width=2)

    # Bottom fade to white
    for y in range(COUPLE_H - 200, COUPLE_H):
        alpha = int(255 * (y - (COUPLE_H - 200)) / 200)
        draw.line([(0, y), (COUPLE_W, y)], fill=(253, 250, 245))

    # Man — royal blue suit
    draw.ellipse([180, 280, 320, 420], fill=(235, 225, 210))  # head
    draw.rounded_rectangle([140, 400, 360, 950], radius=30, fill=(0, 35, 120))  # suit
    draw.rectangle([180, 430, 320, 520], fill=(200, 220, 245))  # shirt
    draw.polygon([(220, 520), (280, 520), (250, 620)], fill=(20, 50, 130))  # tie

    # Woman — orange dress
    draw.ellipse([420, 220, 580, 380], fill=(245, 230, 220))
    draw.ellipse([500, 250, 580, 320], fill=(30, 25, 25))  # hair
    # Dress body
    draw.polygon([(450, 380), (620, 380), (650, 980), (400, 980)], fill=(230, 100, 30))
    # Ruffle shoulder
    draw.ellipse([400, 360, 520, 480], fill=(255, 140, 50))
    # Ring hand
    draw.ellipse([350, 500, 400, 550], fill=(235, 210, 190))
    draw.ellipse([365, 520, 378, 533], fill=(212, 175, 55))

    # Corner flowers bottom-left
    for cx, cy, c in [(60, COUPLE_H - 80, (240, 140, 120)), (100, COUPLE_H - 60, (255, 100, 80)), (40, COUPLE_H - 50, (220, 120, 100))]:
        draw.ellipse([cx - 25, cy - 25, cx + 25, cy + 25], fill=c)

    # Bronze accent lines
    draw.line([(0, COUPLE_H - 20), (COUPLE_W, COUPLE_H - 20)], fill=(180, 130, 80), width=3)
    draw.line([(30, 0), (30, COUPLE_H)], fill=(180, 130, 80), width=2)

    return img


def create_poster_civil_preview() -> Image.Image:
    """Miniature affiche violette pour config / styles."""
    W, H = 600, 850
    img = Image.new("RGB", (W, H), (255, 255, 255))
    draw = ImageDraw.Draw(img)
    # Purple roses corners
    purple = [(107, 45, 131), (160, 80, 180), (219, 112, 147)]
    for cx, cy in [(60, 60), (W - 60, H - 60)]:
        for i, c in enumerate(purple):
            r = 20 - i * 4
            draw.ellipse([cx - r + i * 8, cy - r, cx + r + i * 8, cy + r], fill=c)
    draw.text((W // 2, 70), "Invitation", fill=(198, 156, 78), anchor="mm", font=font(36))
    draw.text((W // 2, 400), "Moïse & Sarah", fill=(219, 112, 147), anchor="mm", font=font(28, True))
    draw.text((W // 2, 460), "11 Septembre 2026", fill=(50, 50, 50), anchor="mm", font=font(18))
    return img


def create_icon(couple: Image.Image, size=512) -> Image.Image:
    """Logo app = photo couple recadrée."""
    cropped = couple.crop((150, 150, 650, 750)).resize((size, size), Image.Resampling.LANCZOS)
    out = Image.new("RGB", (size, size), (253, 250, 245))
    out.paste(cropped, (0, 0))
    return out


def main():
    couple = create_couple_photo()
    couple.save(ASSETS / "couple_photo.png", quality=98)
    couple.save(POSTERS / "couple_photo.png", quality=98)

    civil = create_poster_civil_preview()
    civil.save(POSTERS / "template_mariage_civil.png", quality=95)
    civil.save(ASSETS / "poster_civil_preview.png", quality=95)

    # Affiche blanche preview = couple wide
    blanche = couple.copy()
    blanche.save(POSTERS / "template_affiche_blanche.png", quality=95)

    icon = create_icon(couple)
    for d in ["mipmap-mdpi", "mipmap-hdpi", "mipmap-xhdpi", "mipmap-xxhdpi", "mipmap-xxxhdpi"]:
        p = ROOT / "app/src/main/res" / d
        p.mkdir(parents=True, exist_ok=True)
        icon.save(p / "ic_launcher.png")
    icon.save(POSTERS / "app_logo.png")
    print("Assets OK: couple_photo, icons, previews")


if __name__ == "__main__":
    main()
