package com.supergenies.admin

import android.annotation.SuppressLint
import android.webkit.CookieManager
import android.webkit.JavascriptInterface
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.compose.foundation.layout.*
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Text
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView

private const val LOGIN_URL = "http://supergenies2026.site.je/connexion.php?app=1"

class AdminLoginBridge(private val onSuccess: (String) -> Unit) {
    @JavascriptInterface
    fun onLoginSuccess(token: String, expires: String) {
        CookieManager.getInstance().flush()
        WebViewCookieJar.flush()
        onSuccess(token)
    }
}

@SuppressLint("SetJavaScriptEnabled")
@Composable
fun WebViewLoginScreen(onLoggedIn: (String) -> Unit) {
    var loading by remember { mutableStateOf(true) }
    var status by remember { mutableStateOf("Chargement de la connexion sécurisée…") }
    val bridge = remember { AdminLoginBridge(onLoggedIn) }

    Column(Modifier.fillMaxSize()) {
        if (loading) {
            Row(
                Modifier
                    .fillMaxWidth()
                    .padding(12.dp),
                horizontalArrangement = Arrangement.Center,
                verticalAlignment = Alignment.CenterVertically
            ) {
                CircularProgressIndicator(Modifier.size(18.dp), strokeWidth = 2.dp, color = Color(0xFF1B3A6B))
                Spacer(Modifier.width(8.dp))
                Text(status, fontSize = 12.sp, color = Color.Gray)
            }
        }
        AndroidView(
            modifier = Modifier.fillMaxSize(),
            factory = { context ->
                WebView(context).apply {
                    settings.javaScriptEnabled = true
                    settings.domStorageEnabled = true
                    CookieManager.getInstance().setAcceptCookie(true)
                    addJavascriptInterface(bridge, "SuperGeniesApp")
                    webViewClient = object : WebViewClient() {
                        override fun onPageFinished(view: WebView?, url: String?) {
                            loading = false
                            status = "Entrez le mot de passe : SuperGenies2026!"
                            CookieManager.getInstance().flush()
                        }
                    }
                    loadUrl(LOGIN_URL)
                }
            }
        )
    }
}
