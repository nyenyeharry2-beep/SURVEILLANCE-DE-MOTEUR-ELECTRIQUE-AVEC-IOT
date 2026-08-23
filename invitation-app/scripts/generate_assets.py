#!/usr/bin/env python3
"""Generate invitation template and app icon assets."""

from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

ASSETS = Path(__file__).resolve().parent.parent / "assets"
ASSETS.mkdir(exist_ok=True)

W, H = 1080, 1520
PURPLE = (107, 45, 131)
GOLD = (198, 156, 78)
PINK = (219, 112, 147)
WHITE = (255, 255, 255)
LIGHT_PURPLE = (230, 210, 240)


def get_font(size: int, bold: bool = False):
    candidates = [
        "/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf" if bold else "/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf",
        "/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf" if bold else "/usr/share/fonts/truetype/liberation/LiberationSerif-Regular.ttf",
    ]
    for path in candidates:
        try:
            return ImageFont.truetype(path, size)
        except OSError:
            continue
    return ImageFont.load_default()


def draw_floral_corner(draw: ImageDraw.ImageDraw, x: int, y: int, scale: int = 1, flip_x=False, flip_y=False):
    colors = [PURPLE, (140, 60, 160), PINK, (180, 100, 200)]
    for i, r in enumerate([18, 14, 11, 9]):
        ox = (i % 2) * 12 * scale
        oy = (i // 2) * 10 * scale
        cx = x + (ox if not flip_x else -ox)
        cy = y + (oy if not flip_y else -oy)
        draw.ellipse([cx - r * scale, cy - r * scale, cx + r * scale, cy + r * scale], fill=colors[i % len(colors)])


def create_invitation_template() -> Image.Image:
    img = Image.new("RGB", (W, H), WHITE)
    draw = ImageDraw.Draw(img)

    # Border
    draw.rectangle([20, 20, W - 20, H - 20], outline=LIGHT_PURPLE, width=4)

    # Floral corners
    draw_floral_corner(draw, 60, 60, scale=2)
    draw_floral_corner(draw, W - 60, H - 60, scale=2, flip_x=True, flip_y=True)
    draw_floral_corner(draw, 60, H - 120, scale=1, flip_y=True)

    # Header flourish
    draw.arc([W // 2 - 80, 40, W // 2 + 80, 120], 200, 340, fill=PURPLE, width=3)

    title_font = get_font(72, bold=True)
    script_font = get_font(48)
    body_font = get_font(28)
    small_font = get_font(24)
    name_font = get_font(36, bold=True)

    draw.text((W - 320, 80), "Invitation", fill=GOLD, font=title_font)

    # Guest name line (overlay zone marker - dotted line area)
    draw.text((520, 200), "Mme, Mlle, Mr, Couple :", fill=(60, 60, 60), font=small_font)
    draw.line([(520, 250), (W - 60, 250)], fill=(180, 180, 180), width=2)

    # Body text
    lines = [
        "Les familles NKUBA et BANZA vous invitent",
        "à prendre part au mariage civil de leur fils et fille :",
    ]
    y = 290
    for line in lines:
        draw.text((520, y), line, fill=(40, 40, 40), font=body_font)
        y += 38

    draw.text((520, y + 10), "Moïse NKUBA", fill=PINK, font=script_font)
    draw.text((520, y + 60), "& Sarah KASONGO", fill=PINK, font=script_font)

    y += 140
    details = [
        ("📅", "Date : Vendredi, le 11 Septembre 2026"),
        ("🕐", "Heure : 11h00"),
        ("📍", "Lieu : Commune de Kipushi, Ville de KIPUSHI"),
    ]
    for icon, text in details:
        draw.text((520, y), f"{icon}  {text}", fill=(50, 50, 50), font=body_font)
        y += 42

    draw.text((520, H - 180), "Votre présence fera notre immense joie.", fill=(60, 60, 60), font=body_font)

    # Left photo placeholder (couple area)
    draw.rounded_rectangle([50, 200, 480, 900], radius=20, fill=(245, 240, 250), outline=LIGHT_PURPLE, width=2)
    draw.text((140, 520), "Photo\ndu couple", fill=PURPLE, font=name_font, align="center")

    # QR zone marker (bottom left)
    draw.rectangle([70, H - 320, 250, H - 140], outline=LIGHT_PURPLE, width=2)
    draw.text((95, H - 300), "QR Code", fill=(150, 150, 150), font=small_font)

    # Placement zone marker (bottom center)
    draw.rectangle([280, H - 130, W - 60, H - 70], outline=LIGHT_PURPLE, width=1)

    return img


def create_icon_from_template(template: Image.Image, size: int = 1024) -> Image.Image:
    cropped = template.copy()
    cropped = cropped.resize((size, size), Image.Resampling.LANCZOS)
    return cropped


def main():
    template = create_invitation_template()
    template.save(ASSETS / "template-invitation.png", quality=95)

    icon = create_icon_from_template(template)
    icon.save(ASSETS / "icon.png")
    icon.save(ASSETS / "adaptive-icon.png")
    icon.save(ASSETS / "android-icon-foreground.png")

    # Solid background for adaptive icon
    bg = Image.new("RGB", (1024, 1024), WHITE)
    bg.save(ASSETS / "android-icon-background.png")

    favicon = icon.resize((48, 48), Image.Resampling.LANCZOS)
    favicon.save(ASSETS / "favicon.png")

    splash = template.resize((1284, 2778), Image.Resampling.LANCZOS)
    splash.save(ASSETS / "splash-icon.png")

    print(f"Assets generated in {ASSETS}")


if __name__ == "__main__":
    main()
