package com.supergenies.paiements.ui

import android.annotation.SuppressLint
import android.webkit.CookieManager
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.compose.foundation.layout.size
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView

private const val WARMUP_URL = "http://supergenies2026.site.je/"

/** Charge le site une fois pour obtenir le cookie anti-bot InfinityFree. */
@SuppressLint("SetJavaScriptEnabled")
@Composable
fun InfinityFreeCookieWarmup(onReady: () -> Unit) {
    var done by remember { mutableStateOf(false) }

    if (!done) {
        AndroidView(
            modifier = Modifier.size(1.dp),
            factory = { context ->
                WebView(context).apply {
                    settings.javaScriptEnabled = true
                    settings.domStorageEnabled = true
                    CookieManager.getInstance().setAcceptCookie(true)
                    webViewClient = object : WebViewClient() {
                        override fun onPageFinished(view: WebView?, url: String?) {
                            if (!done) {
                                done = true
                                CookieManager.getInstance().flush()
                                onReady()
                            }
                        }
                    }
                    loadUrl(WARMUP_URL)
                }
            }
        )
    }
}
