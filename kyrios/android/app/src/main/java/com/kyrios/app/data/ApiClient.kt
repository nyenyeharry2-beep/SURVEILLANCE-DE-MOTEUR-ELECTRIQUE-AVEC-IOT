package com.kyrios.app.data

import com.kyrios.app.BuildConfig
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query

interface KyriosApi {
    @POST("auth/register")
    suspend fun register(@Body body: Map<String, String>): AuthResponse

    @POST("auth/login")
    suspend fun login(@Body body: Map<String, String>): AuthResponse

    @GET("users/search")
    suspend fun searchUsers(@Query("q") query: String): Map<String, List<User>>

    @GET("users/me")
    suspend fun getMe(): Map<String, User>

    @PUT("users/me")
    suspend fun updateMe(@Body body: Map<String, String>): Map<String, User>

    @GET("conversations")
    suspend fun getConversations(): Map<String, List<Conversation>>

    @POST("conversations/direct")
    suspend fun createDirectConversation(@Body body: Map<String, Int>): Map<String, Int>

    @GET("conversations/{id}/messages")
    suspend fun getMessages(@Path("id") conversationId: Int): Map<String, List<Message>>

    @POST("conversations/{id}/messages")
    suspend fun sendMessage(
        @Path("id") conversationId: Int,
        @Body body: Map<String, String>
    ): Map<String, Message>

    @GET("posts")
    suspend fun getPosts(): Map<String, List<Post>>

    @POST("posts")
    suspend fun createPost(@Body body: Map<String, String>): Map<String, Post>

    @POST("posts/{id}/like")
    suspend fun likePost(@Path("id") postId: Int): Map<String, Any>

    @POST("posts/{id}/comments")
    suspend fun commentPost(
        @Path("id") postId: Int,
        @Body body: Map<String, String>
    ): Map<String, Comment>
}

object ApiClient {
    private var token: String? = null

    fun setToken(value: String?) {
        token = value
    }

    private val authInterceptor = Interceptor { chain ->
        val request = chain.request().newBuilder().apply {
            token?.let { header("Authorization", "Bearer $it") }
        }.build()
        chain.proceed(request)
    }

    private val logging = HttpLoggingInterceptor().apply {
        level = HttpLoggingInterceptor.Level.BODY
    }

    private val client = OkHttpClient.Builder()
        .addInterceptor(authInterceptor)
        .addInterceptor(logging)
        .build()

    val api: KyriosApi by lazy {
        Retrofit.Builder()
            .baseUrl(BuildConfig.API_BASE_URL)
            .client(client)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(KyriosApi::class.java)
    }
}
