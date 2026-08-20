package com.kyrios.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.kyrios.app.ui.KyriosApp
import com.kyrios.app.ui.theme.KyriosTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            KyriosTheme {
                KyriosApp()
            }
        }
    }
}
