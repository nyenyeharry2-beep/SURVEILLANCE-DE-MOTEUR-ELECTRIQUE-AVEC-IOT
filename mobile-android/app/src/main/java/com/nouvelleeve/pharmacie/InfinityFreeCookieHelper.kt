package com.nouvelleeve.pharmacie

import android.content.Context
import android.os.Handler
import android.os.Looper
import android.webkit.CookieManager
import android.webkit.WebView
import android.webkit.WebViewClient
import kotlin.coroutines.resume
import kotlin.coroutines.suspendCoroutine

/**
 * InfinityFree bloque les apps mobiles avec une page JavaScript (aes.js).
 * Un WebView invisible exécute ce script et récupère le cookie __test.
 */
class InfinityFreeCookieHelper(private val context: Context) {

    private val siteUrl = "https://mapharmaciepk.xo.je"
    private val pingUrl = "$siteUrl/api/ping.php"

    fun cookieHeader(): String? = CookieManager.getInstance().getCookie(siteUrl)

    fun hasValidCookie(): Boolean = cookieHeader()?.contains("__test=") == true

    suspend fun ensureCookie(): Unit = suspendCoroutine { cont ->
        if (hasValidCookie()) {
            cont.resume(Unit)
            return@suspendCoroutine
        }

        Handler(Looper.getMainLooper()).post {
            val cookieManager = CookieManager.getInstance()
            cookieManager.setAcceptCookie(true)

            val webView = WebView(context.applicationContext)
            webView.settings.apply {
                javaScriptEnabled = true
                domStorageEnabled = true
            }
            cookieManager.setAcceptThirdPartyCookies(webView, true)

            var finished = false
            fun complete() {
                if (finished) return
                finished = true
                webView.destroy()
                cont.resume(Unit)
            }

            webView.webViewClient = object : WebViewClient() {
                override fun onPageFinished(view: WebView?, url: String?) {
                    if (cookieManager.getCookie(siteUrl)?.contains("__test=") == true) {
                        complete()
                    }
                }

                override fun onReceivedError(
                    view: WebView?,
                    errorCode: Int,
                    description: String?,
                    failingUrl: String?
                ) {
                    complete()
                }
            }

            webView.loadUrl(pingUrl)
            Handler(Looper.getMainLooper()).postDelayed({ complete() }, 8000)
        }
    }

    companion object {
        fun isInfinityFreeChallenge(body: String): Boolean {
            return body.contains("aes.js") || body.contains("__test") || body.contains("slowAES")
        }
    }
}
