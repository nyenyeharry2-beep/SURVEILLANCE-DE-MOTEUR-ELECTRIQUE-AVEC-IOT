package com.supergenies.admin

import com.google.gson.annotations.SerializedName
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.http.*

interface AdminApi {
    @FormUrlEncoded
    @POST("api/admin/login.php")
    suspend fun login(@Field("password") password: String): LoginResponse

    /** GET login.php = test léger (pas besoin de ping.php sur le serveur) */
    @GET("api/admin/login.php")
    suspend fun checkServer(): PingResponse

    @GET("api/admin/stats.php")
    suspend fun getStats(@Header("X-Admin-Token") token: String): StatsResponse

    @Multipart
    @POST("api/admin/upload.php")
    suspend fun uploadPdf(
        @Header("X-Admin-Token") token: String,
        @Part pdf: MultipartBody.Part,
        @Part("type") type: RequestBody
    ): UploadResponse

    @GET("api/admin/messages.php")
    suspend fun getMessages(
        @Header("X-Admin-Token") token: String,
        @Query("statut") statut: String? = null
    ): MessagesResponse

    @POST("api/admin/messages.php")
    suspend fun updateMessageStatus(
        @Header("X-Admin-Token") token: String,
        @Body body: UpdateMessageRequest
    ): MessageActionResponse
}

data class LoginRequest(val password: String)

data class PingResponse(val success: Boolean, val message: String? = null)

data class LoginResponse(
    val success: Boolean,
    val token: String? = null,
    val expires_at: String? = null,
    val error: String? = null,
    val message: String? = null
)

data class StatsResponse(
    val success: Boolean,
    val stats: Stats? = null,
    val par_classe: List<ClassStat>? = null,
    val derniers_imports: List<ImportLog>? = null,
    val error: String? = null
)

data class Stats(val total_students: Int, val total_fees: Int, val fees_impayes: Int)

data class ClassStat(
    val classe: String,
    val section: String?,
    val total: Int
)

data class ImportLog(
    val id: Int,
    @SerializedName("type_import") val typeImport: String,
    val fichier: String,
    @SerializedName("classe_detectee") val classeDetectee: String?,
    @SerializedName("section_detectee") val sectionDetectee: String?,
    @SerializedName("lignes_traitees") val lignesTraitees: Int,
    @SerializedName("lignes_erreur") val lignesErreur: Int,
    @SerializedName("imported_at") val importedAt: String
)

data class UploadResponse(
    val success: Boolean,
    val type: String? = null,
    val filename: String? = null,
    @SerializedName("lignes_detectees") val lignesDetectees: Int? = null,
    val result: UploadResult? = null,
    val message: String? = null,
    val error: String? = null
)

data class UploadResult(
    val processed: Int,
    val errors: Int,
    @SerializedName("classe_detectee") val classeDetectee: String? = null,
    @SerializedName("section_detectee") val sectionDetectee: String? = null,
    val classes: Map<String, Int>? = null,
    @SerializedName("par_classe") val parClasse: Map<String, Int>? = null
)

data class MessagesResponse(
    val success: Boolean,
    val messages: List<ParentMessage>? = null,
    val counts: MessageCounts? = null,
    val error: String? = null
)

data class MessageCounts(
    val nouveau: Int,
    val en_cours: Int,
    val traite: Int,
    val total: Int
)

data class ParentMessage(
    val id: Int,
    @SerializedName("nom_parent") val nomParent: String,
    @SerializedName("telephone_parent") val telephoneParent: String,
    val matricule: String,
    @SerializedName("nom_eleve") val nomEleve: String?,
    @SerializedName("prenom_eleve") val prenomEleve: String?,
    @SerializedName("classe_eleve") val classeEleve: String?,
    @SerializedName("section_eleve") val sectionEleve: String?,
    val motif: String,
    val message: String,
    val statut: String,
    @SerializedName("note_admin") val noteAdmin: String?,
    @SerializedName("created_at") val createdAt: String
)

data class UpdateMessageRequest(
    val action: String = "update_status",
    val id: Int,
    val statut: String,
    @SerializedName("note_admin") val noteAdmin: String? = null
)

data class MessageActionResponse(
    val success: Boolean,
    val message: String? = null,
    val error: String? = null
)
