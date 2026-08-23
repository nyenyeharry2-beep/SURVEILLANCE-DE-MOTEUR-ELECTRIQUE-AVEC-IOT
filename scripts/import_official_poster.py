#!/usr/bin/env python3
"""
Importe l'affiche officielle Moïse & Sarah dans tous les assets du projet.
Usage: python3 scripts/import_official_poster.py chemin/vers/affiche.jpg [--blanche]
"""
from pathlib import Path
from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
HTDOCS = ROOT / "invitation-mariage-nkuba-kasongo/htdocs/assets"
ANDROID_DRAWABLE = ROOT / "android-native/app/src/main/res/drawable"
ANDROID_POSTERS = ROOT / "android-native/app/src/main/assets/posters"

W, H = 1200, 1700


def resize_poster(img: Image.Image) -> Image.Image:
    return img.convert("RGB").resize((W, H), Image.Resampling.LANCZOS)


def extract_logo(poster: Image.Image, blanche: bool) -> Image.Image:
    pw, ph = poster.size
    if blanche:
        box = (int(pw * 0.04), int(ph * 0.10), int(pw * 0.46), int(ph * 0.72))
    else:
        box = (0, int(ph * 0.07), int(pw * 0.42), int(ph * 0.95))
    return poster.crop(box)


def save_all(poster_path: Path, blanche: bool = False):
    poster = resize_poster(Image.open(poster_path))
    logo = extract_logo(poster, blanche)
    icon = logo.copy()
    icon.thumbnail((512, 512), Image.Resampling.LANCZOS)
    icon_final = Image.new("RGB", (512, 512), (253, 250, 245))
    icon_final.paste(icon, ((512 - icon.width) // 2, (512 - icon.height) // 2))

    template_name = "template_affiche_blanche.png" if blanche else "template_mariage_civil.png"
    upload_name = "poster_blanche.jpg" if blanche else "poster_civil.jpg"

    targets = [
        HTDOCS / template_name,
        HTDOCS / "couple_photo.png",
        HTDOCS / "app-icon.png",
        HTDOCS / "uploads" / upload_name,
        ANDROID_DRAWABLE / template_name,
        ANDROID_DRAWABLE / "couple_photo.png",
        ANDROID_DRAWABLE / "app_logo.png",
        ANDROID_POSTERS / ("affiche_blanche_bg.png" if blanche else "mariage_civil_bg.png"),
        ANDROID_POSTERS / "couple_photo.png",
    ]

    for t in targets:
        t.parent.mkdir(parents=True, exist_ok=True)
        if t.suffix == ".jpg":
            poster.save(t, quality=92)
        else:
            (logo if "couple" in t.name or "logo" in t.name or t.name == "app-icon.png"
             else poster).save(t, quality=95)
        print(f"  ✓ {t.relative_to(ROOT)}")

    print("\nAffiche + logo importés. Rebuild htdocs-upload.zip et APK.")


if __name__ == "__main__":
    import sys
    if len(sys.argv) < 2:
        print("Usage: python3 scripts/import_official_poster.py affiche.jpg [--blanche]")
        sys.exit(1)
    save_all(Path(sys.argv[1]), "--blanche" in sys.argv)
