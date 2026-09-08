package com.supergenies.paiements

import android.webkit.CookieManager
import okhttp3.Cookie
import okhttp3.CookieJar
import okhttp3.HttpUrl

object WebViewCookieJar : CookieJar {
    private val cookieManager: CookieManager = CookieManager.getInstance().apply {
        setAcceptCookie(true)
    }

    override fun loadForRequest(url: HttpUrl): List<Cookie> {
        val raw = cookieManager.getCookie(url.toString()) ?: return emptyList()
        return raw.split(';').mapNotNull { part ->
            Cookie.parse(url, part.trim())
        }
    }

    override fun saveFromResponse(url: HttpUrl, cookies: List<Cookie>) {
        cookies.forEach { cookie ->
            cookieManager.setCookie(url.toString(), "${cookie.name}=${cookie.value}")
        }
        cookieManager.flush()
    }

    fun flush() {
        cookieManager.flush()
    }
}
