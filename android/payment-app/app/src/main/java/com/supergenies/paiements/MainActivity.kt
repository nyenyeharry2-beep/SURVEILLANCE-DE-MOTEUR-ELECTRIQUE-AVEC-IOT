package com.supergenies.paiements

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.supergenies.paiements.ui.SuperGeniesApp
import com.supergenies.paiements.ui.theme.SuperGeniesTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            SuperGeniesTheme {
                SuperGeniesApp()
            }
        }
    }
}
