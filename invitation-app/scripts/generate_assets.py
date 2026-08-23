#!/usr/bin/env python3
"""Generate Sarah wedding invitation templates and app icon assets."""

from pathlib import Path
from PIL import Image, ImageDraw, ImageFont, ImageFilter

ASSETS = Path(__file__).resolve().parent.parent / "assets"
ASSETS.mkdir(exist_ok=True)

W, H = 1080, 1520
PURPLE = (107, 45, 131)
DEEP_PURPLE = (75, 30, 95)
GOLD = (198, 156, 78)
PINK = (219, 112, 147)
WHITE = (255, 255, 255)
LIGHT_PURPLE = (230, 210, 240)
BURGUNDY = (92, 26, 26)
CREAM = (255, 253, 248)
IVORY = (250, 245, 235)


def get_font(size: int, bold: bool = False, italic: bool = False):
    if bold and italic:
        candidates = ["/usr/share/fonts/truetype/dejavu/DejaVuSerif-BoldItalic.ttf"]
    elif bold:
        candidates = [
            "/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf",
        ]
    elif italic:
        candidates = ["/usr/share/fonts/truetype/dejavu/DejaVuSerif-Italic.ttf"]
    else:
        candidates = [
            "/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSerif-Regular.ttf",
        ]
    for path in candidates:
        try:
            return ImageFont.truetype(path, size)
        except OSError:
            continue
    return ImageFont.load_default()


def draw_rose_cluster(draw: ImageDraw.ImageDraw, cx: int, cy: int, scale: float = 1.0):
    petals = [
        (PURPLE, 22), ((140, 60, 160), 18), (PINK, 15), ((180, 100, 200), 12),
        (PURPLE, 10), ((160, 80, 180), 8),
    ]
    offsets = [(0, 0), (25, -8), (-20, 10), (18, 20), (-25, -15), (8, -25)]
    for (color, r), (ox, oy) in zip(petals, offsets):
        rs = int(r * scale)
        draw.ellipse(
            [cx + int(ox * scale) - rs, cy + int(oy * scale) - rs,
             cx + int(ox * scale) + rs, cy + int(oy * scale) + rs],
            fill=color,
        )
    for lx, ly in [(12, 18), (-10, 22), (20, 5), (-18, -5)]:
        draw.ellipse(
            [cx + int(lx * scale) - 4, cy + int(ly * scale) - 7,
             cx + int(lx * scale) + 4, cy + int(ly * scale) + 3],
            fill=(60, 120, 60),
        )


def draw_flourish(draw: ImageDraw.ImageDraw, cx: int, cy: int, color=PURPLE):
    for i in range(-3, 4):
        draw.arc([cx - 60 + i * 8, cy - 20, cx + 60 + i * 8, cy + 20], 200, 340, fill=color, width=2)


def draw_couple_silhouette(draw: ImageDraw.ImageDraw, x: int, y: int, w: int, h: int):
    """Stylized couple placeholder matching Sarah poster layout."""
    # Soft fade at bottom
    for i in range(40):
        alpha = int(255 * (i / 40))
        y_line = y + h - 40 + i
        draw.line([(x, y_line), (x + w, y_line)], fill=(255, 255, 255), width=1)

    # Man seated (simplified shapes)
    draw.ellipse([x + 80, y + 280, x + 160, y + 360], fill=(240, 235, 245))
    draw.rounded_rectangle([x + 60, y + 350, x + 200, y + 620], radius=30, fill=(250, 250, 252))
    # Woman standing
    draw.ellipse([x + 220, y + 180, x + 310, y + 270], fill=(245, 240, 250))
    draw.rounded_rectangle([x + 200, y + 260, x + 340, y + 650], radius=25, fill=(255, 255, 255))


def create_sarah_template() -> Image.Image:
    """Main Sarah & Moïse Kipushi Floral invitation poster."""
    img = Image.new("RGB", (W, H), WHITE)
    draw = ImageDraw.Draw(img)

    # Subtle background roses watermark
    for wx, wy in [(600, 400), (700, 550), (650, 700)]:
        draw.ellipse([wx, wy, wx + 80, wy + 80], fill=(255, 240, 245))

    # Curved purple border on right side
    draw.arc([480, 30, W + 100, H - 30], 270, 90, fill=LIGHT_PURPLE, width=3)

    # Floral corners
    draw_rose_cluster(draw, 90, 90, 1.8)
    draw_rose_cluster(draw, W - 90, H - 90, 1.8)
    draw_rose_cluster(draw, 70, H - 150, 1.2)

    draw_flourish(draw, W // 2, 70)

    gold_font = get_font(82, bold=True)
    body_font = get_font(26)
    small_font = get_font(22)
    script_font = get_font(44, italic=True)
    name_font = get_font(40, bold=True)

    # "Invitation" gold script header
    draw.text((W - 340, 70), "Invitation", fill=GOLD, font=gold_font)
    draw.line([(W - 340, 155), (W - 80, 155)], fill=GOLD, width=2)
    draw.ellipse([W - 210, 150, W - 200, 160], fill=GOLD)

    # Guest name line
    draw.text((520, 195), "Mme, Mlle, Mr, Couple :", fill=(50, 50, 50), font=small_font)
    for dx in range(520, W - 60, 12):
        draw.ellipse([dx, 243, dx + 4, 247], fill=(180, 180, 180))

    # Body
    lines = [
        "Les familles NKUBA et BANZA vous invitent",
        "à prendre part au mariage civil de leur fils et fille :",
    ]
    y = 280
    for line in lines:
        draw.text((520, y), line, fill=(35, 35, 35), font=body_font)
        y += 36

    draw.text((520, y + 8), "Moïse NKUBA", fill=PINK, font=script_font)
    draw.text((560, y + 55), "&", fill=(200, 50, 50), font=get_font(36, bold=True))
    draw.line([(520, y + 75), (580, y + 75)], fill=GOLD, width=2)
    draw.line([(610, y + 75), (W - 80, y + 75)], fill=GOLD, width=2)
    draw.text((520, y + 85), "Sarah KASONGO", fill=PINK, font=script_font)

    y += 160
    icons_colors = [(66, 133, 244), (234, 67, 53), (52, 168, 83)]
    details = [
        "Date : Vendredi, le 11 Septembre 2026",
        "Heure : 11h00",
        "Lieu : Commune de Kipushi, Ville de KIPUSHI",
    ]
    for i, text in enumerate(details):
        cx, cy = 535, y + 8
        draw.ellipse([cx - 12, cy - 12, cx + 12, cy + 12], fill=icons_colors[i])
        draw.text((560, y), text, fill=(45, 45, 45), font=body_font)
        y += 40

    draw.text((520, H - 170), "Votre présence fera notre immense joie.", fill=(55, 55, 55), font=body_font)

    # Left couple photo area
    draw.rounded_rectangle([40, 170, 470, 920], radius=0, fill=(248, 245, 252))
    draw_couple_silhouette(draw, 40, 170, 430, 750)

    # Purple branch bottom-left
    for bx in range(5):
        draw.line([(30 + bx * 15, H - 200 + bx * 8), (120 + bx * 20, H - 130)], fill=DEEP_PURPLE, width=3)

    return img


def create_royal_bordeaux_template() -> Image.Image:
    """Royal Bordeaux style — Adrian preview design."""
    img = Image.new("RGB", (W, H), BURGUNDY)
    draw = ImageDraw.Draw(img)

    # Paper texture effect
    for i in range(0, W, 4):
        for j in range(0, H, 4):
            if (i + j) % 8 == 0:
                draw.point((i, j), fill=(88, 24, 24))

    # Pink flowers top-left and bottom-right
    draw_rose_cluster(draw, 100, 120, 1.5)
    draw_rose_cluster(draw, W - 100, H - 120, 1.5)

    # Gold geometric frame
    margin = 60
    points = [
        (margin, margin + 40), (margin + 40, margin),
        (W - margin - 40, margin), (W - margin, margin + 40),
        (W - margin, H - margin - 40), (W - margin - 40, H - margin),
        (margin + 40, H - margin), (margin, H - margin - 40),
    ]
    draw.polygon(points, outline=GOLD, width=3)

    title_font = get_font(48, bold=True)
    script_font = get_font(36, italic=True)
    body_font = get_font(22, italic=True)

    draw.text((W // 2 - 40, 130), "VOUS", fill=WHITE, font=title_font, anchor="mm")
    draw.text((W // 2, 175), "êtes invité à notre", fill=GOLD, font=script_font, anchor="mm")
    draw.text((W // 2, 230), "MARIAGE !", fill=WHITE, font=get_font(56, bold=True), anchor="mm")

    # Guest name zone (dynamic overlay)
    draw.text((W // 2, 420), "~ ~ NOM ~ ~", fill=(255, 255, 255, 180), font=get_font(42, bold=True), anchor="mm")

    draw.text((W // 2, 520), "11 SEPTEMBRE 2026", fill=GOLD, font=get_font(28, bold=True), anchor="mm")
    addr_lines = [
        "Commune de Kipushi,",
        "Ville de KIPUSHI",
    ]
    y = 580
    for line in addr_lines:
        draw.text((W // 2, y), line, fill=WHITE, font=body_font, anchor="mm")
        y += 30

    # QR zone
    qr = 180
    qx, qy = W // 2 - qr // 2, H - 280
    draw.rectangle([qx - 4, qy - 4, qx + qr + 4, qy + qr + 4], outline=GOLD, width=2)
    draw.rectangle([qx, qy, qx + qr, qy + qr], fill=WHITE)

    return img


def create_ivory_template() -> Image.Image:
    img = Image.new("RGB", (W, H), CREAM)
    draw = ImageDraw.Draw(img)

    # Gold gradient bands
    for i in range(H):
        t = i / H
        r = int(255 - t * 30)
        g = int(253 - t * 50)
        b = int(248 - t * 80)
        draw.line([(0, i), (W, i)], fill=(r, g, b))

    draw.line([(80, 80), (W - 80, 80)], fill=GOLD, width=3)
    draw.line([(80, H - 80), (W - 80, H - 80)], fill=GOLD, width=3)

    draw.text((W // 2, 200), "Wedding Invitation", fill=GOLD, font=get_font(52, italic=True), anchor="mm")
    draw.text((W // 2, 280), "Moïse NKUBA  &  Sarah KASONGO", fill=BURGUNDY, font=get_font(30, bold=True), anchor="mm")

    draw.text((W // 2, 480), "~ ~ NOM ~ ~", fill=BURGUNDY, font=get_font(38, bold=True), anchor="mm")
    draw.text((W // 2, 560), "11 Septembre 2026 • 11h00", fill=(120, 90, 60), font=get_font(24), anchor="mm")
    draw.text((W // 2, 610), "Kipushi", fill=(120, 90, 60), font=get_font(22, italic=True), anchor="mm")

    qr = 160
    qx, qy = W // 2 - qr // 2, H - 300
    draw.rectangle([qx, qy, qx + qr, qy + qr], fill=WHITE, outline=GOLD, width=2)

    return img


def create_ville_template() -> Image.Image:
    img = Image.new("RGB", (W, H), DEEP_PURPLE)
    draw = ImageDraw.Draw(img)

    # Modern overlay
    for i in range(H):
        alpha = int(40 * (i / H))
        draw.line([(0, i), (W, i)], fill=(DEEP_PURPLE[0] + alpha // 3, DEEP_PURPLE[1], DEEP_PURPLE[2] + alpha // 2))

    draw.text((W // 2, 180), "KIPUSHI", fill=GOLD, font=get_font(64, bold=True), anchor="mm")
    draw.text((W // 2, 250), "Mariage Civil", fill=WHITE, font=get_font(32), anchor="mm")
    draw.text((W // 2, 310), "Moïse & Sarah", fill=PINK, font=get_font(40, italic=True), anchor="mm")

    draw.text((W // 2, 480), "NOM DE L'INVITÉ", fill=WHITE, font=get_font(34, bold=True), anchor="mm")
    draw.text((W // 2, 560), "11.09.2026 • Kipushi", fill=LIGHT_PURPLE, font=get_font(24), anchor="mm")

    draw.rectangle([W - 220, H - 260, W - 60, H - 100], fill=WHITE)

    return img


def create_floral_corner(size: int = 200) -> Image.Image:
    img = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    draw_rose_cluster(draw, size // 3, size // 3, 1.2)
    return img


def create_clear_icon(sarah: Image.Image, size: int = 1024) -> Image.Image:
    """Crop top-right 'Invitation' + roses for a clear app icon."""
    # Crop region: top-right with invitation text and roses
    crop = sarah.crop((400, 0, W, 500))
    crop = crop.resize((size, size), Image.Resampling.LANCZOS)

    # Add white padding border for clarity on home screen
    padded = Image.new("RGB", (size, size), WHITE)
    inner = int(size * 0.88)
    resized = crop.resize((inner, inner), Image.Resampling.LANCZOS)
    offset = (size - inner) // 2
    padded.paste(resized, (offset, offset))
    return padded


def main():
    sarah = create_sarah_template()
    sarah.save(ASSETS / "template-sarah.png", quality=98)
    sarah.save(ASSETS / "template-invitation.png", quality=98)

    create_royal_bordeaux_template().save(ASSETS / "template-royal-bordeaux.png", quality=95)
    create_ivory_template().save(ASSETS / "template-ivory.png", quality=95)
    create_ville_template().save(ASSETS / "template-ville.png", quality=95)
    create_floral_corner().save(ASSETS / "floral-corner.png")

    icon = create_clear_icon(sarah)
    icon.save(ASSETS / "icon.png")
    icon.save(ASSETS / "adaptive-icon.png")
    icon.save(ASSETS / "android-icon-foreground.png")

    Image.new("RGB", (1024, 1024), WHITE).save(ASSETS / "android-icon-background.png")
    icon.resize((48, 48), Image.Resampling.LANCZOS).save(ASSETS / "favicon.png")
    sarah.resize((1284, 2778), Image.Resampling.LANCZOS).save(ASSETS / "splash-icon.png")

    print(f"Assets generated in {ASSETS}")


if __name__ == "__main__":
    main()
