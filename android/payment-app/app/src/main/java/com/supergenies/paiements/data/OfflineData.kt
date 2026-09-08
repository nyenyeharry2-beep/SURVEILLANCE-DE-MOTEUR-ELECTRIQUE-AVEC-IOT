package com.supergenies.paiements.data

/** Données hors-ligne si le serveur InfinityFree n'est pas encore accessible */
object OfflineData {
    val inscriptions = InscriptionsResponse(
        success = true,
        title = "Conditions d'admission & Inscriptions",
        annee_scolaire = "2026-2027",
        school = SchoolDetail(
            name = "C.S. LES SUPER GENIES",
            address = "26, Av. Bin Malisawa, Q/Golf Maisha, Lubumbashi",
            phone = "+243 815 454 401 / 858 357 777",
            email = "lessupergenies@gmail.com",
            motto = "Discipline · Compétence · Excellence",
            slogan = "L'excellence, notre engagement !"
        ),
        sections = listOf(
            SectionInfo(
                id = "primaire",
                title = "Section Primaire",
                notes = listOf(
                    "Frais connexe : 30 USD",
                    "Frais scolaires : 65 USD/mois × 8 mois",
                    "Kit complet : 35 USD · Sac scolaire : 6 USD",
                    "Transport : 20 à 30 USD/mois selon zone"
                )
            ),
            SectionInfo(
                id = "secondaire",
                title = "Section Secondaire",
                notes = listOf(
                    "Inscriptions gratuites · Frais connexe 30-50 USD",
                    "Frais mensuels : 65 à 120 USD selon niveau",
                    "Options générales et techniques disponibles"
                )
            ),
            SectionInfo(
                id = "petrochimie",
                title = "Section Pétrochimie",
                slogan = "Choisis l'avenir, choisis la Pétrochimie !",
                description = "Deviens acteur du changement, là où la science transforme le monde."
            )
        )
    )

    val trousseau = TrousseauResponse(
        success = true,
        title = "Trousseau & Équipements",
        annee_scolaire = "2026-2027",
        school = SchoolDetail(
            name = "C.S. LES SUPER GENIES",
            address = "26, Av. Bin Malisawa, Q/Golf Maisha, Lubumbashi",
            phone = "+243 815 454 401 / 858 357 777",
            email = "lessupergenies@gmail.com",
            motto = null,
            slogan = null
        ),
        equipements = listOf(
            Equipement("Pull-over cagoule", 20, "USD"),
            Equipement("Combinaison", 25, "USD"),
            Equipement("Tenue de gymnastique", 15, "USD"),
            Equipement("Sac scolaire", 10, "USD"),
            Equipement("Cravate", "6 et 10", "USD")
        ),
        uniformes = mapOf(
            "filles" to "Jupe plissée bleue et chemise blanche",
            "garcons" to "Pantalon bleu et chemise blanche"
        ),
        coiffure = mapOf(
            "filles" to "Tresses poupée",
            "garcons" to "Ras"
        ),
        transport = emptyList<Any>(),
        politiques = listOf(
            "Anglais et informatique dès la maternelle",
            "Tous les cahiers doivent être couverts",
            "Réduction familiale à partir de 5 enfants inscrits"
        )
    )
}
