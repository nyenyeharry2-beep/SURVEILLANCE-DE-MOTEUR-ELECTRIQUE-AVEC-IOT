package com.supergenies.paiements.data

import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {
    @GET("api/student.php")
    suspend fun getStudent(@Query("matricule") matricule: String): StudentResponse

    @GET("api/info/inscriptions.php")
    suspend fun getInscriptions(): InscriptionsResponse

    @GET("api/info/trousseau.php")
    suspend fun getTrousseau(): TrousseauResponse

    @POST("api/messages/send.php")
    suspend fun sendMessage(@Body body: MessageRequest): MessageResponse
}

data class MessageRequest(
    val nom_parent: String,
    val telephone_parent: String,
    val matricule: String,
    val nom_eleve: String? = null,
    val prenom_eleve: String? = null,
    val classe_eleve: String? = null,
    val section_eleve: String? = null,
    val motif: String,
    val message: String
)

data class MessageResponse(
    val success: Boolean,
    val message_id: Int? = null,
    val message: String? = null,
    val error: String? = null
)

data class StudentResponse(
    val success: Boolean,
    val error: String? = null,
    val school: SchoolInfo? = null,
    val student: Student? = null,
    val fees: List<Fee> = emptyList(),
    val summary: FeeSummary? = null
)

data class SchoolInfo(
    val name: String,
    val motto: String,
    val phone: String
)

data class Student(
    val matricule: String,
    val nom: String,
    val prenom: String,
    val nom_complet: String,
    val genre: String,
    val classe: String,
    val section: String?,
    val annee_scolaire: String,
    val statut_inscription: String,
    val date_inscription: String?,
    val telephone: String?
)

data class Fee(
    val id: Int,
    val label: String,
    val montant_du: Double,
    val montant_paye: Double,
    val statut: String,
    val mois: Int?,
    val solde: Double?
)

data class FeeSummary(
    val total_du: Double,
    val total_paye: Double,
    val solde: Double,
    val nb_frais: Int,
    val nb_impayes: Int,
    val en_ordre: Boolean
)

data class InscriptionsResponse(
    val success: Boolean,
    val title: String,
    val annee_scolaire: String,
    val school: SchoolDetail,
    val sections: List<SectionInfo>
)

data class SchoolDetail(
    val name: String,
    val address: String,
    val phone: String,
    val email: String,
    val motto: String?,
    val slogan: String?
)

data class SectionInfo(
    val id: String,
    val title: String,
    val slogan: String? = null,
    val description: String? = null,
    val inscriptions: Any? = null,
    val frais_scolaires: Any? = null,
    val extras: List<Map<String, Any>>? = null,
    val transport: Any? = null,
    val coiffure: Map<String, String>? = null,
    val notes: List<String>? = null,
    val contact: Map<String, String>? = null
)

data class TrousseauResponse(
    val success: Boolean,
    val title: String,
    val annee_scolaire: String,
    val school: SchoolDetail,
    val equipements: List<Equipement>,
    val uniformes: Map<String, String>,
    val coiffure: Map<String, String>,
    val transport: Any,
    val politiques: List<String>
)

data class Equipement(
    val label: String,
    val montant: Any,
    val devise: String
)
