package com.supergenies.admin

import android.annotation.SuppressLint
import android.net.Uri
import android.webkit.CookieManager
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.viewinterop.AndroidView

private const val START_URL = "http://supergenies2026.site.je/connexion.php?app=1"

/**
 * Application Admin 100 % WebView — même technologie que le navigateur qui fonctionne.
 * Imports PDF, messages, stats : tout passe par le serveur web (pas d'API OkHttp).
 */
@SuppressLint("SetJavaScriptEnabled")
@Composable
fun AdminWebViewShell() {
    var fileCallback by remember { mutableStateOf<ValueCallback<Array<Uri>>?>(null) }
    var webView by remember { mutableStateOf<WebView?>(null) }

    val fileLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result ->
        val cb = fileCallback
        fileCallback = null
        if (cb == null) return@rememberLauncherForActivityResult
        val uris = WebChromeClient.FileChooserParams.parseResult(result.resultCode, result.data)
        cb.onReceiveValue(uris)
    }

    AndroidView(
        modifier = Modifier.fillMaxSize(),
        factory = { context ->
            WebView(context).apply {
                settings.javaScriptEnabled = true
                settings.domStorageEnabled = true
                settings.allowFileAccess = true
                settings.allowContentAccess = true
                CookieManager.getInstance().setAcceptCookie(true)

                webViewClient = object : WebViewClient() {
                    override fun onPageFinished(view: WebView?, url: String?) {
                        CookieManager.getInstance().flush()
                    }
                }

                webChromeClient = object : WebChromeClient() {
                    override fun onShowFileChooser(
                        webView: WebView?,
                        callback: ValueCallback<Array<Uri>>?,
                        params: FileChooserParams?
                    ): Boolean {
                        fileCallback?.onReceiveValue(null)
                        fileCallback = callback
                        val intent = params?.createIntent()
                        if (intent != null) {
                            fileLauncher.launch(intent)
                            return true
                        }
                        return false
                    }
                }

                loadUrl(START_URL)
                webView = this
            }
        },
        update = { view ->
            webView = view
        }
    )
}
