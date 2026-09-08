package com.supergenies.paiements.ui

import android.annotation.SuppressLint
import android.graphics.Bitmap
import android.net.Uri
import android.webkit.CookieManager
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.compose.BackHandler
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import com.supergenies.paiements.R
import com.supergenies.paiements.SuperGeniesJsBridge
import com.supergenies.paiements.ui.theme.NavyBlue

private const val START_URL = "http://supergenies2026.site.je/suivi.php?app=1"

/**
 * App Paiements — WebView vers suivi.php (même design que le site web).
 */
@SuppressLint("SetJavaScriptEnabled")
@Composable
fun PaymentWebViewShell() {
    var fileCallback by remember { mutableStateOf<ValueCallback<Array<Uri>>?>(null) }
    var webView by remember { mutableStateOf<WebView?>(null) }
    var isLoading by remember { mutableStateOf(true) }
    var loadError by remember { mutableStateOf(false) }

    BackHandler(enabled = webView?.canGoBack() == true) {
        webView?.goBack()
    }

    val fileLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result ->
        val cb = fileCallback
        fileCallback = null
        if (cb == null) return@rememberLauncherForActivityResult
        cb.onReceiveValue(WebChromeClient.FileChooserParams.parseResult(result.resultCode, result.data))
    }

    Box(Modifier.fillMaxSize()) {
        AndroidView(
            modifier = Modifier.fillMaxSize(),
            factory = { context ->
                WebView(context).apply {
                    settings.javaScriptEnabled = true
                    settings.domStorageEnabled = true
                    settings.allowFileAccess = true
                    settings.loadWithOverviewMode = true
                    settings.useWideViewPort = true
                    settings.textZoom = 100
                    CookieManager.getInstance().setAcceptCookie(true)
                    CookieManager.getInstance().setAcceptThirdPartyCookies(this, true)
                    addJavascriptInterface(SuperGeniesJsBridge(context), "SuperGeniesAndroid")

                    webViewClient = object : WebViewClient() {
                        override fun onPageStarted(view: WebView?, url: String?, favicon: Bitmap?) {
                            isLoading = true
                            loadError = false
                        }

                        override fun onPageFinished(view: WebView?, url: String?) {
                            isLoading = false
                            CookieManager.getInstance().flush()
                        }

                        override fun onReceivedError(
                            view: WebView?,
                            request: WebResourceRequest?,
                            error: WebResourceError?
                        ) {
                            if (request?.isForMainFrame == true) {
                                isLoading = false
                                loadError = true
                            }
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
                            val intent = params?.createIntent() ?: return false
                            fileLauncher.launch(intent)
                            return true
                        }
                    }

                    loadUrl(START_URL)
                    webView = this
                }
            },
            update = { view -> webView = view }
        )

        if (isLoading) {
            LoadingSplash()
        }

        if (loadError) {
            ErrorSplash(onRetry = {
                loadError = false
                isLoading = true
                webView?.loadUrl(START_URL)
            })
        }
    }
}

@Composable
private fun LoadingSplash() {
    Box(
        Modifier
            .fillMaxSize()
            .background(Color.White),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Image(
                painter = painterResource(R.drawable.logo_spag),
                contentDescription = "Logo Super Genies",
                modifier = Modifier.size(96.dp)
            )
            Spacer(Modifier.height(16.dp))
            CircularProgressIndicator(
                modifier = Modifier.size(32.dp),
                color = NavyBlue,
                strokeWidth = 3.dp
            )
            Spacer(Modifier.height(12.dp))
            Text("Chargement…", color = NavyBlue, fontWeight = FontWeight.SemiBold)
        }
    }
}

@Composable
private fun ErrorSplash(onRetry: () -> Unit) {
    Box(
        Modifier
            .fillMaxSize()
            .background(Color.White)
            .padding(24.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Image(
                painter = painterResource(R.drawable.logo_spag),
                contentDescription = null,
                modifier = Modifier.size(80.dp)
            )
            Spacer(Modifier.height(16.dp))
            Text(
                "Connexion impossible",
                color = NavyBlue,
                fontWeight = FontWeight.Bold,
                fontSize = 18.sp
            )
            Spacer(Modifier.height(8.dp))
            Text(
                "Vérifiez votre connexion internet et réessayez.",
                color = Color.Gray,
                fontSize = 14.sp
            )
            Spacer(Modifier.height(20.dp))
            androidx.compose.material3.Button(onClick = onRetry) {
                Text("Réessayer")
            }
        }
    }
}
