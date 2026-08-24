#!/usr/bin/env python3
"""
Installe la photo des mariés (Moïse & Sarah) dans tout le projet.
Usage:
  python3 scripts/install_couple_photo.py chemin/vers/couple_photo.jpg
  python3 scripts/install_couple_photo.py   # lit scripts/input/couple_photo.jpg
"""
from __future__ import annotations

import sys
from pathlib import Path
from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
INPUT_DEFAULT = ROOT / "scripts/input/couple_photo.jpg"

TARGETS_JPG = [
    ROOT / "invitation-mariage-nkuba-kasongo/htdocs/assets/couple_photo.jpg",
    ROOT / "invitation-mariage-nkuba-kasongo/htdocs/assets/uploads/couple_photo.jpg",
    ROOT / "invitation-mariage-nkuba-kasongo/htdocs/assets/uploads/couple_logo.jpg",
    ROOT / "php-backend/assets/couple_photo.jpg",
]

TARGETS_PNG = [
    ROOT / "invitation-mariage-nkuba-kasongo/htdocs/assets/couple_photo.png",
    ROOT / "android-native/app/src/main/res/drawable/couple_photo.png",
    ROOT / "android-native/app/src/main/assets/posters/couple_photo.png",
    ROOT / "php-backend/assets/couple_photo.png",
]

ICON_TARGETS = [
    ROOT / "invitation-mariage-nkuba-kasongo/htdocs/assets/app-icon.png",
    ROOT / "android-native/app/src/main/res/drawable/app_logo.png",
]


def load_source(path: Path) -> Image.Image:
    img = Image.open(path)
    if img.mode not in ("RGB", "L"):
        img = img.convert("RGB")
    return img


def make_logo(img: Image.Image, size: int = 512) -> Image.Image:
    w, h = img.size
    side = int(min(w, h) * 0.55)
    cx, cy = w // 2, int(h * 0.38)
    left = max(0, cx - side // 2)
    top = max(0, cy - side // 2)
    crop = img.crop((left, top, min(w, left + side), min(h, top + side)))
    out = Image.new("RGB", (size, size), (255, 255, 255))
    crop = crop.resize((size, size), Image.Resampling.LANCZOS)
    out.paste(crop, (0, 0))
    return out


def install(src: Path) -> None:
    if not src.exists():
        print(f"✗ Fichier introuvable: {src}")
        print("  Placez votre photo dans scripts/input/couple_photo.jpg")
        print("  ou: python3 scripts/install_couple_photo.py /chemin/photo.jpg")
        sys.exit(1)

    img = load_source(src)
    logo = make_logo(img)
    print(f"Source: {src} ({img.size[0]}×{img.size[1]})")

    for t in TARGETS_JPG:
        t.parent.mkdir(parents=True, exist_ok=True)
        img.save(t, quality=92)
        print(f"  ✓ {t.relative_to(ROOT)}")

    for t in TARGETS_PNG:
        t.parent.mkdir(parents=True, exist_ok=True)
        img.save(t, quality=95)
        print(f"  ✓ {t.relative_to(ROOT)}")

    for t in ICON_TARGETS:
        t.parent.mkdir(parents=True, exist_ok=True)
        logo.save(t, quality=95)
        print(f"  ✓ {t.relative_to(ROOT)}")

    print("\nPhoto installée. Rebuild htdocs-upload.zip puis redéployez.")


if __name__ == "__main__":
    source = Path(sys.argv[1]) if len(sys.argv) > 1 else INPUT_DEFAULT
    install(source)
