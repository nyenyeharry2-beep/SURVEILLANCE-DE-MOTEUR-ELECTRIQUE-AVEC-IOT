package com.nouvelleeve.pharmacie

import android.content.Context

class SessionManager(context: Context) {
    private val prefs = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)

    var token: String?
        get() = prefs.getString(KEY_TOKEN, null)
        set(value) = prefs.edit().putString(KEY_TOKEN, value).apply()

    var serverUrl: String
        get() = prefs.getString(KEY_SERVER, DEFAULT_SERVER) ?: DEFAULT_SERVER
        set(value) = prefs.edit().putString(KEY_SERVER, normalizeUrl(value)).apply()

    var userName: String?
        get() = prefs.getString(KEY_USER_NAME, null)
        set(value) = prefs.edit().putString(KEY_USER_NAME, value).apply()

    fun isLoggedIn(): Boolean = !token.isNullOrBlank()

    fun clear() {
        prefs.edit().clear().apply()
    }

    private fun normalizeUrl(url: String): String {
        var u = url.trim()
        if (!u.endsWith("/")) u += "/"
        if (!u.contains("/api")) {
            u = if (u.endsWith("/")) u + "api/" else u + "/api/"
        }
        return u
    }

    companion object {
        private const val PREFS = "nouvelle_eve_session"
        private const val KEY_TOKEN = "token"
        private const val KEY_SERVER = "server_url"
        private const val KEY_USER_NAME = "user_name"
        const val DEFAULT_SERVER = "https://mapharmaciepk.xo.je/api/"
    }
}
