package com.supergenies.paiements

import com.supergenies.paiements.data.ApiService
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiConfig {
    /** HTTP en premier : InfinityFree free tier + certificat SSL parfois instable */
    val BASE_URLS: List<String> = listOf(
        "http://supergenies2026.site.je/",
        "https://supergenies2026.site.je/",
    )

    const val CONNECTION_HELP =
        "Serveur inaccessible. Vérifiez que les fichiers backend sont bien uploadés " +
        "dans htdocs sur InfinityFree et que la base MySQL est créée."
}

object ApiClient {
    private val logging = HttpLoggingInterceptor().apply {
        level = HttpLoggingInterceptor.Level.BASIC
    }

    private val httpClient: OkHttpClient = OkHttpClient.Builder()
        .addInterceptor(logging)
        .connectTimeout(45, TimeUnit.SECONDS)
        .readTimeout(45, TimeUnit.SECONDS)
        .retryOnConnectionFailure(true)
        .build()

    private val services = ApiConfig.BASE_URLS.associateWith { baseUrl ->
        Retrofit.Builder()
            .baseUrl(baseUrl)
            .client(httpClient)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(ApiService::class.java)
    }

    val service: ApiService get() = services[ApiConfig.BASE_URLS.first()]!!

    suspend fun <T> call(block: suspend (ApiService) -> T): T {
        var lastError: Exception? = null
        for ((baseUrl, api) in services) {
            try {
                return block(api)
            } catch (e: Exception) {
                lastError = e
            }
        }
        throw lastError ?: Exception(ApiConfig.CONNECTION_HELP)
    }
}
