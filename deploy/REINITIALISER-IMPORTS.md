# Réinitialiser les imports — repartir à zéro

Vous n'avez **pas** besoin de réinstaller l'APK Admin ou Suivi.  
Il faut seulement **effacer les données importées** sur le serveur, puis refaire les PDF **dans l'ordre**.

---

## Méthode 1 — Depuis l'APK Admin (recommandé)

1. Ouvrir **Super Genies Admin**
2. Aller dans **Imports**
3. Descendre jusqu'à **« Réinitialiser — repartir à zéro »**
4. Taper **REINITIALISER** (en majuscules)
5. Appuyer sur **Effacer et recommencer**

Message attendu : *« Données effacées (élèves + paiements). Commencez par le PDF Inscriptions. »*

---

## Méthode 2 — phpMyAdmin (InfinityFree)

1. Panneau InfinityFree → **phpMyAdmin**
2. Sélectionner votre base de données
3. Onglet **SQL** → coller et exécuter :

```sql
DELETE FROM student_fees;
DELETE FROM students;
DELETE FROM import_logs;
```

---

## Ce qui est effacé / conservé

| Effacé | Conservé |
|--------|----------|
| Liste des élèves (noms, matricules) | Connexion Admin |
| Tous les paiements importés | Communiqués |
| Historique des imports | Messages parents |
| | APK sur le téléphone |

---

## Ordre d'import après réinitialisation

Faites **un PDF à la fois**, dans cet ordre :

| Étape | Type dans Admin | Mois ? |
|-------|-----------------|--------|
| **1** | Inscriptions | Non |
| **2** | Frais connexe | Non |
| **3** | Minerval | **Oui** — Septembre, puis Octobre, etc. |
| **4** | Bus | **Oui** — un PDF par mois |
| **5** | Cravate, Pull, Kit… | Non — un type à la fois |

### Exemple minerval (8 mois)

1. Import → Minerval → Mois **Septembre** → PDF septembre  
2. Import → Minerval → Mois **Octobre** → PDF octobre  
3. … jusqu'à **Avril**

### Exemple bus

1. Import → Bus → Mois **Septembre** → PDF bus septembre  
2. Import → Bus → Mois **Octobre** → PDF bus octobre  
3. …

---

## Vérification

Après l'étape 1 (Inscriptions), les statistiques Admin doivent afficher le nombre d'élèves.

Après minerval septembre, un parent peut chercher un matricule sur **Suivi** et voir :  
`Minerval — Septembre : XX / 65 USD`

---

## Fichier à mettre à jour (si le bouton n'apparaît pas)

Uploader : `admin/portail.php` → `/htdocs/admin/portail.php`

Ou zip frais v6 + ce fichier admin mis à jour.
