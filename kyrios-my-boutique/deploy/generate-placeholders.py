#!/usr/bin/env python3
"""Generate product placeholder JPGs for Kyrios My Boutique."""

from PIL import Image, ImageDraw, ImageFont
import os

OUTPUT = os.path.join(os.path.dirname(__file__), '..', 'public', 'uploads', 'products')

PRODUCTS = {
    # Mode enfant
    'lp-robe-creme.jpg': ('Little Princess', 'Robe Satin Crème', '#f5e6d3', '#c9a87c'),
    'lp-robe-bleu.jpg': ('Little Princess', 'Robe Bleu Ciel', '#dbeafe', '#60a5fa'),
    'lp-robe-rose.jpg': ('Little Princess', 'Robe Rose Poudré', '#fce7f3', '#f472b6'),
    'lp-robe-bleu-plisse.jpg': ('Little Princess', 'Robe Bleu Plissé', '#e0f2fe', '#38bdf8'),
    'lp-robe-rouge.jpg': ('Little Princess', 'Robe Velours Rouge', '#fee2e2', '#ef4444'),
    'lp-robe-noir.jpg': ('Little Princess', 'Robe Velours Noir', '#f3f4f6', '#374151'),
    # Mode femme
    'robe-magenta.jpg': ('Mode Femme', 'Robe Magenta Peplum', '#fdf2f8', '#db2777'),
    'robe-bleu-argent.jpg': ('Mode Femme', 'Robe Bleu Argent', '#eff6ff', '#3b82f6'),
    'robe-marine.jpg': ('Mode Femme', 'Robe Bleu Marine', '#eef2ff', '#1e3a8a'),
    'ensemble-marine.jpg': ('Mode Femme', 'Ensemble Marine', '#f0f9ff', '#0369a1'),
    'ensemble-olive.jpg': ('Mode Femme', 'Ensemble Olive', '#f7fee7', '#65a30d'),
    'ensemble-creme.jpg': ('Mode Femme', 'Ensemble Crème Brodé', '#fffbeb', '#d97706'),
    'robe-floral-menthe.jpg': ('Mode Femme', 'Robe Florale Menthe', '#ecfdf5', '#059669'),
    'robe-floral-lavande.jpg': ('Mode Femme', 'Robe Florale Lavande', '#f5f3ff', '#7c3aed'),
    'ensemble-noir.jpg': ('Mode Femme', 'Ensemble Noir Paon', '#fafafa', '#171717'),
    'ensemble-bleu-satin.jpg': ('Mode Femme', 'Ensemble Bleu Satin', '#eff6ff', '#2563eb'),
    # Mode homme
    'chemise-marron.jpg': ('Mode Homme', 'Chemise Marron Pois', '#fef3c7', '#92400e'),
    'chemise-bleu-fleur.jpg': ('Mode Homme', 'Chemise Bleu Floral', '#dbeafe', '#1d4ed8'),
    'chemise-tribal.jpg': ('Mode Homme', 'Chemise Tribal', '#fef3c7', '#78350f'),
    'chemise-etoile.jpg': ('Mode Homme', 'Chemise Étoiles', '#f3f4f6', '#4b5563'),
    'chemise-kaki.jpg': ('Mode Homme', 'Chemise Kaki', '#ecfccb', '#4d7c0f'),
    # Chaussures
    'sneakers-or-noir.jpg': ('Chaussures', 'Sneakers Or Noir', '#fafafa', '#171717'),
    'sneakers-or-rose.jpg': ('Chaussures', 'Sneakers Or Rose', '#fdf2f8', '#be185d'),
    'sneakers-or-marron.jpg': ('Chaussures', 'Sneakers Or Marron', '#fff7ed', '#9a3412'),
}


def make_placeholder(filename, badge, title, bg_light, accent):
    size = 800
    img = Image.new('RGB', (size, size), bg_light)
    draw = ImageDraw.Draw(img)

    for y in range(size):
        r = int(int(bg_light[1:3], 16) * (1 - y / size * 0.15))
        g = int(int(bg_light[3:5], 16) * (1 - y / size * 0.15))
        b = int(int(bg_light[5:7], 16) * (1 - y / size * 0.15))
        draw.line([(0, y), (size, y)], fill=(r, g, b))

    card = 60
    draw.rounded_rectangle([card, card, size - card, size - card], radius=24, fill='white')

    try:
        font_badge = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', 22)
        font_title = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', 32)
        font_small = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', 18)
    except OSError:
        font_badge = font_title = font_small = ImageFont.load_default()

    badge_w = draw.textlength(badge, font=font_badge) + 32
    bx = (size - badge_w) / 2
    draw.rounded_rectangle([bx, 100, bx + badge_w, 140], radius=16, fill=accent)
    draw.text((size / 2, 120), badge, fill='white', font=font_badge, anchor='mm')

    icon_y = size // 2 - 20
    draw.rounded_rectangle([size // 2 - 40, icon_y - 60, size // 2 + 40, icon_y + 60], radius=8, outline='#9ca3af', width=3)

    draw.text((size / 2, icon_y + 100), title, fill='#1f2937', font=font_title, anchor='mm')
    draw.text((size / 2, size - 90), 'Kyrios My Boutique', fill='white', font=font_small, anchor='mm')

    path = os.path.join(OUTPUT, filename)
    img.save(path, 'JPEG', quality=88)
    print(f'  ✓ {filename}')


if __name__ == '__main__':
    os.makedirs(OUTPUT, exist_ok=True)
    print('Generating placeholders...')
    for fn, (badge, title, bg, accent) in PRODUCTS.items():
        make_placeholder(fn, badge, title, bg, accent)
    print(f'Done — {len(PRODUCTS)} images in {OUTPUT}')
