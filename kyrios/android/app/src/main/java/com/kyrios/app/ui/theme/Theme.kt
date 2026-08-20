package com.kyrios.app.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val KyriosBlue = Color(0xFF246BFD)
private val KyriosDark = Color(0xFF121212)
private val KyriosSurface = Color(0xFF1E1E1E)

private val DarkColors = darkColorScheme(
    primary = KyriosBlue,
    onPrimary = Color.White,
    background = KyriosDark,
    surface = KyriosSurface,
    onBackground = Color.White,
    onSurface = Color.White,
)

private val LightColors = lightColorScheme(
    primary = KyriosBlue,
    onPrimary = Color.White,
)

@Composable
fun KyriosTheme(darkTheme: Boolean = true, content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = if (darkTheme) DarkColors else LightColors,
        content = content
    )
}
