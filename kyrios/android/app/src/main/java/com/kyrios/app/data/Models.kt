package com.kyrios.app.data

data class User(
    val id: Int,
    val username: String,
    val display_name: String?,
    val bio: String?,
    val profile_photo: String?,
    val posts_count: Int? = null,
    val followers_count: Int? = null,
    val following_count: Int? = null,
)

data class AuthResponse(
    val success: Boolean,
    val user: User?,
    val token: String?,
    val error: String? = null,
)

data class Conversation(
    val id: Int,
    val is_group: Int,
    val title: String?,
    val last_message: String?,
    val last_message_at: String?,
    val unread_count: Int?,
)

data class Message(
    val id: Int,
    val sender_id: Int,
    val sender_username: String?,
    val message: String?,
    val message_type: String,
    val media_url: String?,
    val is_read: Int,
    val created_at: String,
)

data class Post(
    val id: Int,
    val user_id: Int,
    val username: String?,
    val display_name: String?,
    val profile_photo: String?,
    val content: String,
    val media_url: String?,
    val visibility: String,
    val created_at: String,
    val likes_count: Int?,
    val comments_count: Int?,
)

data class Comment(
    val id: Int,
    val content: String,
    val created_at: String,
    val username: String?,
    val display_name: String?,
)
