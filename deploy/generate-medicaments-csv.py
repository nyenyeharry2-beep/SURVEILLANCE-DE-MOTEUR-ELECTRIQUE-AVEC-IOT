#!/usr/bin/env python3
"""Génère deploy/modele_medicaments_1000.csv pour import site Pharmacie Nouvelle Eve."""
import csv
import random
from datetime import date, timedelta
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "deploy" / "modele_medicaments_1000.csv"

categories = [
    "Antibiotiques", "Antalgiques", "Vitamines", "Antipaludéens", "Dermatologie",
    "Antihypertenseurs", "Antidiabétiques", "Antiseptiques", "Sirops", "Gastro",
    "Ophtalmologie", "Pédiatrie", "Gynécologie", "Antihistaminiques", "Cardiologie",
]

prefixes_cp = [
    "Paracétamol", "Amoxicilline", "Ibuprofène", "Metronidazole", "Ciprofloxacine",
    "Azithromycine", "Doxycycline", "Chloroquine", "Artéméther", "Vitamine C",
    "Vitamine B", "Fer", "Acide folique", "Oméprazole", "Metformine",
    "Amlodipine", "Losartan", "Céfixime", "Tramadol", "Diclofénac",
    "Prednisolone", "Cotrimoxazole", "Fluconazole", "Albendazole", "Zinc",
]

doses_cp = ["250mg", "500mg", "100mg", "400mg", "875mg", "1000mg", "50mg", "200mg", "10mg", "5mg"]
forms_flacon = [
    ("Sirop toux", "Sirops"), ("Sirop paracétamol", "Sirops"), ("Solution buvable", "Pédiatrie"),
    ("Suspension amoxicilline", "Antibiotiques"), ("Sirop vitamine", "Vitamines"),
    ("Collyre", "Ophtalmologie"), ("Gouttes auriculaires", "Antiseptiques"),
    ("Sirop antipaludéen", "Antipaludéens"), ("Lotion cutanée", "Dermatologie"),
    ("Solution antiseptique", "Antiseptiques"),
]

headers = [
    "code", "nom", "categorie", "type_unite", "prix_achat", "prix_comprime",
    "prix_plaquette", "prix_flacon", "comprimes_par_plaquette", "quantite_stock",
    "stock_a_ajouter", "seuil_alerte", "date_expiration", "description",
]


def main(count: int = 1000, seed: int = 42) -> None:
    random.seed(seed)
    rows = []
    for i in range(1, count + 1):
        is_flacon = i % 7 == 0
        if is_flacon:
            base_name, cat = forms_flacon[i % len(forms_flacon)]
            vol = random.choice(["60ml", "100ml", "120ml", "200ml", "15ml"])
            nom = f"{base_name} {vol}" if i <= len(forms_flacon) else f"{base_name} {vol} #{i}"
            type_unite = "flacon"
            prix_achat = random.randint(800, 4500)
            prix_flacon = random.randint(1500, 8500)
            prix_comprime = prix_plaquette = 0
            cpp = 0
            stock = random.randint(5, 80)
            desc = "Vente par flacon — import catalogue"
        else:
            drug = random.choice(prefixes_cp)
            dose = random.choice(doses_cp)
            nom = f"{drug} {dose}" if i <= 25 else f"{drug} {dose} (ref {i})"
            cat = categories[i % len(categories)]
            type_unite = "comprime_plaquette"
            cpp = random.choice([8, 10, 12, 15, 20, 24, 30])
            prix_comprime = random.randint(100, 3500)
            prix_achat = max(50, int(prix_comprime * random.uniform(0.45, 0.75)))
            prix_plaquette = prix_comprime * cpp
            if random.random() < 0.3:
                prix_plaquette = int(prix_plaquette * random.uniform(0.92, 1.0))
            prix_flacon = 0
            stock = random.randint(20, 800)
            desc = "Vente par comprimé ou plaquette — import catalogue"

        exp = date.today() + timedelta(days=random.randint(180, 900))
        rows.append([
            f"MED-{i:04d}", nom, cat, type_unite, prix_achat, prix_comprime, prix_plaquette,
            prix_flacon, cpp, stock, 0, random.randint(5, 30), exp.isoformat(), desc,
        ])

    with OUT.open("w", encoding="utf-8-sig", newline="") as f:
        csv.writer(f, delimiter=";").writerows([headers, *rows])
    print(f"OK: {OUT} ({len(rows)} médicaments)")


if __name__ == "__main__":
    main()
