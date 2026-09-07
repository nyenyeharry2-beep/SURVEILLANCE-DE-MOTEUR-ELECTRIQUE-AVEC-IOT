package com.supergenies.admin

import com.google.gson.Gson
import com.google.gson.JsonSyntaxException
import okhttp3.Interceptor
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
    const val BASE_URL = "http://supergenies2026.site.je/"

    const val ADMIN_ERROR_NETWORK =
        "Impossible de joindre le serveur. Vérifiez votre connexion internet " +
        "ou contactez le support informatique de l'école."
}

object AdminApiClient {
    private val gson = Gson()

    /** InfinityFree supprime parfois X-Admin-Token — on duplique en ?token= */
    private val tokenQueryInterceptor = Interceptor { chain ->
        var request = chain.request()
        val token = request.header("X-Admin-Token")
        if (!token.isNullOrBlank() && request.url.queryParameter("token") == null) {
            val url = request.url.newBuilder().addQueryParameter("token", token).build()
            request = request.newBuilder().url(url).build()
        }
        chain.proceed(request)
    }

    private val browserHeaders = Interceptor { chain ->
        chain.proceed(
            chain.request().newBuilder()
                .header(
                    "User-Agent",
                    "Mozilla/5.0 (Linux; Android 13; Mobile) AppleWebKit/537.36 " +
                        "(KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36"
                )
                .header("Accept", "application/json, text/plain, */*")
                .header("Referer", "http://supergenies2026.site.je/connexion.php")
                .build()
        )
    }

    private val httpClient = OkHttpClient.Builder()
        .cookieJar(WebViewCookieJar)
        .addInterceptor(tokenQueryInterceptor)
        .addInterceptor(browserHeaders)
        .addInterceptor(HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BASIC })
        .connectTimeout(60, TimeUnit.SECONDS)
        .readTimeout(60, TimeUnit.SECONDS)
        .writeTimeout(60, TimeUnit.SECONDS)
        .retryOnConnectionFailure(true)
        .build()

    private val api: AdminApi = Retrofit.Builder()
        .baseUrl(ApiConfig.BASE_URL)
        .client(httpClient)
        .addConverterFactory(GsonConverterFactory.create(gson))
        .build()
        .create(AdminApi::class.java)

    fun parseServerError(e: HttpException): String? {
        return try {
            val body = e.response()?.errorBody()?.string() ?: return null
            if (body.trimStart().startsWith("<")) {
                return "Réponse HTML au lieu de JSON — rechargez l'app après connexion web"
            }
            gson.fromJson(body, LoginResponse::class.java)?.error
        } catch (_: Exception) {
            null
        }
    }

    fun userFriendlyMessage(e: Throwable): String {
        return when (e) {
            is HttpException -> parseServerError(e) ?: when (e.code()) {
                401 -> "Session expirée — reconnectez-vous"
                403 -> "Mot de passe incorrect"
                404 -> "Fichiers admin manquants sur le serveur."
                400 -> "Requête refusée (400). Reconnectez-vous via l'écran de connexion."
                500, 502, 503 -> "Serveur temporairement indisponible (erreur ${e.code()})."
                else -> "Erreur serveur (${e.code()})."
            }
            is UnknownHostException -> "Nom de domaine introuvable. Vérifiez internet."
            is SocketTimeoutException -> "Délai dépassé — réessayez."
            is SSLException -> "Problème SSL."
            is JsonSyntaxException -> "Import impossible : le serveur a renvoyé une page web au lieu de JSON. Fermez et rouvrez l'app."
            is IOException -> e.message?.takeIf { it.isNotBlank() } ?: ApiConfig.ADMIN_ERROR_NETWORK
            else -> {
                val msg = e.message ?: ""
                if (msg.contains("JsonReader") || msg.contains("malformed JSON")) {
                    "Le serveur n'a pas renvoyé de JSON valide. Reconnectez-vous."
                } else {
                    msg.takeIf { it.isNotBlank() } ?: ApiConfig.ADMIN_ERROR_NETWORK
                }
            }
        }
    }

    /** Précharge les cookies InfinityFree avant chaque appel API */
    suspend fun <T> call(block: suspend (AdminApi) -> T): T {
        WebViewCookieJar.flush()
        try {
            api.checkServer()
        } catch (_: Exception) {
            // ping optionnel
        }
        return block(api)
    }
}
