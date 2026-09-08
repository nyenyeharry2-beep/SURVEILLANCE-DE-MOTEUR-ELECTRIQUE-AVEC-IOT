# Import paiements v2 — nom, types de frais, mises à jour

## Problème

Les rapports PDF de paiement affichent souvent le **n° de reçu** au lieu du matricule. L'import ne reconnaissait que les matricules CSLSG.

## Solution

1. **Rapprochement par nom** — après import des inscriptions, les paiements sont liés à l'élève via nom + prénom (et classe si disponible).
2. **Classification automatique** selon le PDF :
   - **Frais connexe** — 30 USD (ou 50 USD secondaire)
   - **Minerval** — 65 USD + mois (Septembre, Octobre…)
   - **Frais de bus** — transport
   - **Équipements** — pull, cravate, kit, sac…
3. **Nouveau vs mise à jour** — réimporter un PDF met à jour les lignes existantes (même élève + même type de frais + même mois). Idéal pour publier les retards.

## Déploiement

Extraire dans `htdocs/` sur InfinityFree :

[supergenies-patch-paiements-v2.zip](https://github.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/raw/cursor/super-genies-paiements-019d/deploy/supergenies-patch-paiements-v2.zip)

## Ordre d'import recommandé

1. **Inscriptions** — PDF liste élèves (matricules + noms)
2. **Paiements frais connexe** — PDF connexe
3. **Paiements minerval** — un PDF par mois (Septembre, Octobre…)
4. **Bus / équipements** — selon les rapports disponibles
5. **Mises à jour** — réimporter quand un parent paie en retard

## Message après import

Exemple : `Import paiements OK : 45 ligne(s) — 12 nouvelle(s), 33 mise(s) à jour, 2 non reconnue(s)`

Les lignes « non reconnues » = nom introuvable dans la base → importer d'abord les inscriptions.

## Test parent

```
http://supergenies2026.site.je/suivi.php
```

Les frais s'affichent par catégorie : Frais connexe, Minerval (avec mois), Transport, Équipement.
