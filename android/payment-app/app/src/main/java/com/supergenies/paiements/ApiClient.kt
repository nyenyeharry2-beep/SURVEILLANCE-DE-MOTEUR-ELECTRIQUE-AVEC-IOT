package com.supergenies.paiements

import com.supergenies.paiements.data.ApiService
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiConfig {
    const val BASE_URL = "http://supergenies2026.site.je/"

    /** Message affiché aux parents — aucun détail technique */
    const val PARENT_ERROR_NETWORK =
        "Service momentanément indisponible. Vérifiez votre connexion internet " +
        "et réessayez. Si le problème persiste, contactez le secrétariat au +243 815 454 401."

    const val PARENT_ERROR_MESSAGE =
        "Impossible d'envoyer votre message pour le moment. " +
        "Veuillez réessayer ou appeler le secrétariat au +243 815 454 401."
}

object ApiClient {
    private val logging = HttpLoggingInterceptor().apply {
        level = HttpLoggingInterceptor.Level.BASIC
    }

    private val browserHeaders = Interceptor { chain ->
        chain.proceed(
            chain.request().newBuilder()
                .header(
                    "User-Agent",
                    "Mozilla/5.0 (Linux; Android 13; Mobile) AppleWebKit/537.36 " +
                        "(KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36"
                )
                .build()
        )
    }

    private val httpClient: OkHttpClient = OkHttpClient.Builder()
        .cookieJar(WebViewCookieJar)
        .addInterceptor(browserHeaders)
        .addInterceptor(logging)
        .connectTimeout(45, TimeUnit.SECONDS)
        .readTimeout(45, TimeUnit.SECONDS)
        .retryOnConnectionFailure(true)
        .build()

    private val api: ApiService = Retrofit.Builder()
        .baseUrl(ApiConfig.BASE_URL)
        .client(httpClient)
        .addConverterFactory(GsonConverterFactory.create())
        .build()
        .create(ApiService::class.java)

    suspend fun <T> call(block: suspend (ApiService) -> T): T {
        WebViewCookieJar.flush()
        return block(api)
    }
}
