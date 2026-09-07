package com.supergenies.admin

import com.google.gson.Gson
import com.google.gson.JsonSyntaxException
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.HttpException
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.io.IOException
import java.net.SocketTimeoutException
import java.net.UnknownHostException
import java.util.concurrent.TimeUnit
import javax.net.ssl.SSLException

object ApiConfig {
    val BASE_URLS: List<String> = listOf(
        "http://supergenies2026.site.je/",
        "https://supergenies2026.site.je/",
    )

    const val ADMIN_ERROR_NETWORK =
        "Impossible de joindre le serveur. Vérifiez votre connexion internet " +
        "ou contactez le support informatique de l'école."
}

object AdminApiClient {
    private val gson = Gson()
    private val httpClient = OkHttpClient.Builder()
        .addInterceptor(HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BASIC })
        .connectTimeout(60, TimeUnit.SECONDS)
        .readTimeout(60, TimeUnit.SECONDS)
        .writeTimeout(60, TimeUnit.SECONDS)
        .retryOnConnectionFailure(true)
        .build()

    private val apis = ApiConfig.BASE_URLS.associateWith { baseUrl ->
        Retrofit.Builder()
            .baseUrl(baseUrl)
            .client(httpClient)
            .addConverterFactory(GsonConverterFactory.create(gson))
            .build()
            .create(AdminApi::class.java)
    }

    val api: AdminApi get() = apis[ApiConfig.BASE_URLS.first()]!!

    fun parseServerError(e: HttpException): String? {
        return try {
            val body = e.response()?.errorBody()?.string() ?: return null
            gson.fromJson(body, LoginResponse::class.java)?.error
        } catch (_: Exception) {
            null
        }
    }

    fun userFriendlyMessage(e: Throwable): String {
        return when (e) {
            is HttpException -> parseServerError(e) ?: when (e.code()) {
                403 -> "Mot de passe incorrect"
                404 -> "Fichiers admin manquants sur le serveur. Uploadez le patch admin dans htdocs."
                405 -> "Serveur à mettre à jour (login.php)"
                500, 502, 503 -> "Serveur temporairement indisponible (erreur ${e.code()}). Réessayez dans quelques minutes."
                else -> "Erreur serveur (${e.code()}). Vérifiez que les fichiers admin sont bien uploadés."
            }
            is UnknownHostException -> "Nom de domaine introuvable. Vérifiez internet ou l'URL du serveur."
            is SocketTimeoutException -> "Délai dépassé. Le serveur met trop de temps à répondre — réessayez."
            is SSLException -> "Problème de certificat SSL. L'app essaie aussi en HTTP automatiquement."
            is JsonSyntaxException -> "Réponse serveur invalide. Vérifiez que login.php et bootstrap.php sont uploadés."
            is IOException -> ApiConfig.ADMIN_ERROR_NETWORK
            else -> e.message?.takeIf { it.isNotBlank() } ?: ApiConfig.ADMIN_ERROR_NETWORK
        }
    }

    suspend fun <T> call(block: suspend (AdminApi) -> T): T {
        var lastError: Exception? = null
        for ((_, api) in apis) {
            try {
                return block(api)
            } catch (e: HttpException) {
                lastError = e
                // Erreurs métier (auth, validation) : ne pas basculer sur l'autre URL
                if (e.code() in 400..499 && e.code() != 404) throw e
            } catch (e: Exception) {
                lastError = e
            }
        }
        throw lastError ?: Exception(ApiConfig.ADMIN_ERROR_NETWORK)
    }
}
