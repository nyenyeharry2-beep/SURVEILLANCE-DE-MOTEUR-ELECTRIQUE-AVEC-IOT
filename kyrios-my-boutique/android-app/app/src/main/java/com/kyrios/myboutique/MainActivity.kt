package com.kyrios.myboutique

import android.annotation.SuppressLint
import android.os.Bundle
import android.view.View
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebResourceResponse
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.OnBackPressedCallback
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.WindowCompat
import java.net.HttpURLConnection
import java.net.URL
import kotlin.concurrent.thread

class MainActivity : AppCompatActivity() {

    private lateinit var webView: WebView
    private var isOfflineMode = true
    private val serverUrl by lazy { getString(R.string.webview_url) }
    private val offlineUrl by lazy { getString(R.string.offline_url) }

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        WindowCompat.setDecorFitsSystemWindows(window, false)
        @Suppress("DEPRECATION")
        window.decorView.systemUiVisibility = (
            View.SYSTEM_UI_FLAG_LAYOUT_STABLE
                or View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN
            )

        webView = WebView(this).apply {
            settings.javaScriptEnabled = true
            settings.domStorageEnabled = true
            settings.databaseEnabled = true
            settings.loadWithOverviewMode = true
            settings.useWideViewPort = true
            settings.setSupportZoom(true)
            settings.builtInZoomControls = true
            settings.displayZoomControls = false
            settings.mediaPlaybackRequiresUserGesture = false
            settings.allowFileAccess = true
            settings.allowContentAccess = true

            webViewClient = object : WebViewClient() {
                override fun onReceivedHttpError(
                    view: WebView?,
                    request: WebResourceRequest?,
                    errorResponse: WebResourceResponse?
                ) {
                    if (request?.isForMainFrame == true && !isOfflineMode) {
                        switchToOffline()
                    }
                }

                override fun onReceivedError(
                    view: WebView?,
                    request: WebResourceRequest?,
                    error: WebResourceError?
                ) {
                    if (request?.isForMainFrame == true && !isOfflineMode) {
                        switchToOffline()
                    }
                }

                @Deprecated("Deprecated in Java")
                override fun onReceivedError(
                    view: WebView?,
                    errorCode: Int,
                    description: String?,
                    failingUrl: String?
                ) {
                    if (!isOfflineMode) {
                        switchToOffline()
                    }
                }

                override fun shouldOverrideUrlLoading(
                    view: WebView?,
                    request: WebResourceRequest?
                ): Boolean {
                    val url = request?.url?.toString() ?: return false
                    if (url.startsWith("https://") || url.startsWith("file://")) {
                        if (url.startsWith("https://") && url.contains("kyriosboutique")) {
                            isOfflineMode = false
                        }
                        return false
                    }
                    return true
                }
            }
            webChromeClient = WebChromeClient()
        }

        setContentView(webView)

        if (savedInstanceState != null) {
            webView.restoreState(savedInstanceState)
        } else {
            // Toujours démarrer en mode local (évite "Page web non disponible")
            loadOffline()
            // Vérifier le serveur en arrière-plan
            checkServerInBackground()
        }

        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                webView.evaluateJavascript("typeof handleBack === 'function'") { result ->
                    if (result == "true") {
                        webView.evaluateJavascript("handleBack()") { handled ->
                            if (handled != "true" && webView.canGoBack()) {
                                webView.goBack()
                            } else if (handled != "true") {
                                isEnabled = false
                                onBackPressedDispatcher.onBackPressed()
                            }
                        }
                    } else if (webView.canGoBack()) {
                        webView.goBack()
                    } else {
                        isEnabled = false
                        onBackPressedDispatcher.onBackPressed()
                    }
                }
            }
        })
    }

    private fun loadOffline() {
        isOfflineMode = true
        webView.loadUrl(offlineUrl)
    }

    private fun switchToOffline() {
        if (!isOfflineMode) {
            isOfflineMode = true
            webView.loadUrl(offlineUrl)
        }
    }

    private fun checkServerInBackground() {
        thread {
            if (isServerReachable(serverUrl)) {
                runOnUiThread {
                    isOfflineMode = false
                    webView.loadUrl(serverUrl)
                }
            }
        }
    }

    private fun isServerReachable(urlString: String): Boolean {
        return try {
            val url = URL(urlString)
            val conn = url.openConnection() as HttpURLConnection
            conn.connectTimeout = 6000
            conn.readTimeout = 6000
            conn.requestMethod = "GET"
            conn.instanceFollowRedirects = true
            val code = conn.responseCode
            conn.disconnect()
            code in 200..399
        } catch (_: Exception) {
            false
        }
    }

    override fun onSaveInstanceState(outState: Bundle) {
        super.onSaveInstanceState(outState)
        webView.saveState(outState)
    }
}
