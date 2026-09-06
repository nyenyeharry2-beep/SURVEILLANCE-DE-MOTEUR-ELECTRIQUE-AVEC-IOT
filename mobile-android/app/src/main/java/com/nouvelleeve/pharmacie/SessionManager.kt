package com.nouvelleeve.pharmacie

import android.content.Context

class SessionManager(context: Context) {
    private val prefs = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)

    init {
        if (prefs.getInt(KEY_AUTH_VERSION, 0) < AUTH_VERSION) {
            prefs.edit()
                .clear()
                .putInt(KEY_AUTH_VERSION, AUTH_VERSION)
                .commit()
        } else {
            prefs.edit()
                .remove(LEGACY_SERVER_KEY)
                .remove("server_url")
                .apply()
        }
    }

    var token: String?
        get() = prefs.getString(KEY_TOKEN, null)
        set(value) {
            prefs.edit().putString(KEY_TOKEN, value).commit()
        }

    var sessionId: String?
        get() = prefs.getString(KEY_SESSION_ID, null)
        set(value) {
            prefs.edit().putString(KEY_SESSION_ID, value).commit()
        }

    var userName: String?
        get() = prefs.getString(KEY_USER_NAME, null)
        set(value) {
            prefs.edit().putString(KEY_USER_NAME, value).commit()
        }

    fun isLoggedIn(): Boolean =
        !token.isNullOrBlank() || !sessionId.isNullOrBlank()

    fun clear() {
        prefs.edit()
            .remove(KEY_TOKEN)
            .remove(KEY_SESSION_ID)
            .remove(KEY_USER_NAME)
            .commit()
    }

    companion object {
        private const val PREFS = "nouvelle_eve_session"
        private const val KEY_TOKEN = "token"
        private const val KEY_SESSION_ID = "session_id"
        private const val KEY_USER_NAME = "user_name"
        private const val KEY_AUTH_VERSION = "auth_version"
        private const val LEGACY_SERVER_KEY = "server_url"
        private const val AUTH_VERSION = 2
    }
}
