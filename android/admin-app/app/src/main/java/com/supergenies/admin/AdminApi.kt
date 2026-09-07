package com.supergenies.admin

import com.google.gson.annotations.SerializedName
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.http.*

interface AdminApi {
    @POST("api/admin/login.php")
    suspend fun login(@Body body: LoginRequest): LoginResponse

    @GET("api/admin/stats.php")
    suspend fun getStats(@Header("X-Admin-Token") token: String): StatsResponse

    @Multipart
    @POST("api/admin/upload.php")
    suspend fun uploadPdf(
        @Header("X-Admin-Token") token: String,
        @Part pdf: MultipartBody.Part,
        @Part("type") type: RequestBody
    ): UploadResponse
}

data class LoginRequest(val password: String)

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
