package com.nouvelleeve.pharmacie

import android.content.Context

class SessionManager(context: Context) {
    private val prefs = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)

    init {
        prefs.edit().remove(LEGACY_SERVER_KEY).apply()
    }

    var token: String?
        get() = prefs.getString(KEY_TOKEN, null)
        set(value) = prefs.edit().putString(KEY_TOKEN, value).apply()

    val serverUrl: String
        get() = API_URL

    var userName: String?
        get() = prefs.getString(KEY_USER_NAME, null)
        set(value) = prefs.edit().putString(KEY_USER_NAME, value).apply()

    fun isLoggedIn(): Boolean = !token.isNullOrBlank()

    fun clear() {
        prefs.edit().remove(KEY_TOKEN).remove(KEY_USER_NAME).apply()
    }

    companion object {
        private const val PREFS = "nouvelle_eve_session"
        private const val KEY_TOKEN = "token"
        private const val KEY_USER_NAME = "user_name"
        private const val LEGACY_SERVER_KEY = "server_url"
        const val API_URL = "https://mapharmaciepk.xo.je/api/"
    }
}
