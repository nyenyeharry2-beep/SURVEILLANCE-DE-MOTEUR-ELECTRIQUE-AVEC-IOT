package com.supergenies.admin

import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiConfig {
    val BASE_URLS: List<String> = listOf(
        "http://supergenies2026.site.je/",
        "https://supergenies2026.site.je/",
    )

    const val CONNECTION_HELP =
        "Serveur inaccessible. Uploadez le backend dans htdocs sur InfinityFree " +
        "et importez schema.sql dans phpMyAdmin."
}

object AdminApiClient {
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
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(AdminApi::class.java)
    }

    val api: AdminApi get() = apis[ApiConfig.BASE_URLS.first()]!!

    suspend fun <T> call(block: suspend (AdminApi) -> T): T {
        var lastError: Exception? = null
        for ((_, api) in apis) {
            try {
                return block(api)
            } catch (e: Exception) {
                lastError = e
            }
        }
        throw lastError ?: Exception(ApiConfig.CONNECTION_HELP)
    }
}
