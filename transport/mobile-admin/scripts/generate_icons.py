#!/usr/bin/env python3
"""Generate Android launcher icons and splash drawable from school logo."""

from pathlib import Path
from PIL import Image, ImageDraw

ROOT = Path(__file__).resolve().parents[1]
LOGO = ROOT.parent / "assets" / "images" / "logo.jpg"
RES = ROOT / "app" / "src" / "main" / "res"

MIPMAP_SIZES = {
    "mipmap-mdpi": 48,
    "mipmap-hdpi": 72,
    "mipmap-xhdpi": 96,
    "mipmap-xxhdpi": 144,
    "mipmap-xxxhdpi": 192,
}


def fit_square(image: Image.Image, size: int, background=(26, 82, 118)) -> Image.Image:
    canvas = Image.new("RGBA", (size, size), background + (255,))
    img = image.convert("RGBA")
    max_side = int(size * 0.82)
    img.thumbnail((max_side, max_side), Image.Resampling.LANCZOS)
    offset = ((size - img.width) // 2, (size - img.height) // 2)
    canvas.paste(img, offset, img)
    return canvas


def rounded_icon(image: Image.Image, size: int) -> Image.Image:
    square = fit_square(image, size)
    mask = Image.new("L", (size, size), 0)
    draw = ImageDraw.Draw(mask)
    radius = int(size * 0.18)
    draw.rounded_rectangle((0, 0, size, size), radius=radius, fill=255)
    output = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    output.paste(square, (0, 0), mask)
    return output


def main() -> None:
    if not LOGO.exists():
        raise SystemExit(f"Logo introuvable: {LOGO}")

    logo = Image.open(LOGO)

    drawable_dir = RES / "drawable"
    drawable_dir.mkdir(parents=True, exist_ok=True)
    splash = fit_square(logo, 512)
    splash.save(drawable_dir / "splash_logo.png")

    for folder, size in MIPMAP_SIZES.items():
        target = RES / folder
        target.mkdir(parents=True, exist_ok=True)
        icon = rounded_icon(logo, size)
        icon.save(target / "ic_launcher.png")
        icon.save(target / "ic_launcher_round.png")

    print("Icons generated successfully.")


if __name__ == "__main__":
    main()
