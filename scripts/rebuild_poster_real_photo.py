#!/usr/bin/env python3
"""Reconstruit l'affiche violette avec la vraie photo Moïse & Sarah."""
from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(ROOT / "scripts"))

from build_official_assets import (  # noqa: E402
    HTDOCS,
    ANDROID_DRAW,
    ANDROID_POSTERS,
    W,
    H,
    create_blanche_poster,
    create_icon,
    create_official_poster,
    load_font,
)
from PIL import Image

COUPLE = ROOT / "invitation-mariage-nkuba-kasongo/htdocs/assets/couple_photo.jpg"


def load_couple() -> Image.Image:
    if not COUPLE.exists():
        raise SystemExit(f"Photo introuvable: {COUPLE}")
    return Image.open(COUPLE).convert("RGB")


def save_all() -> None:
    couple = load_couple()
    civil = create_official_poster(couple)
    blanche = create_blanche_poster(couple)
    icon = create_icon(couple)

    targets = [
        (HTDOCS / "couple_photo.png", couple),
        (HTDOCS / "template_mariage_civil.png", civil),
        (HTDOCS / "template_affiche_blanche.png", blanche),
        (HTDOCS / "app-icon.png", icon),
        (HTDOCS / "uploads" / "poster_civil.jpg", civil),
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


if __name__ == "__main__":
    print("Reconstruction affiche avec photo réelle…")
    save_all()
    print("Terminé.")
