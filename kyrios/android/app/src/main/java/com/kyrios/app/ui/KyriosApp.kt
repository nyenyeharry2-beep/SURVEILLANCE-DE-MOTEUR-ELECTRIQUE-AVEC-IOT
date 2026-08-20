package com.kyrios.app.ui

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Chat
import androidx.compose.material.icons.filled.Explore
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import com.kyrios.app.data.ApiClient
import com.kyrios.app.ui.screens.AuthScreen
import com.kyrios.app.ui.screens.DiscoverScreen
import com.kyrios.app.ui.screens.MessagesScreen
import com.kyrios.app.ui.screens.NotificationsScreen
import com.kyrios.app.ui.screens.ProfileScreen
import com.kyrios.app.ui.screens.PublishScreen
import com.kyrios.app.ui.screens.SplashScreen

@Composable
fun KyriosApp() {
    var showSplash by remember { mutableStateOf(true) }
    var isAuthenticated by remember { mutableStateOf(false) }
    var selectedTab by remember { mutableIntStateOf(0) }

    if (showSplash) {
        SplashScreen(onFinished = { showSplash = false })
        return
    }

    if (!isAuthenticated) {
        AuthScreen(onAuthenticated = {
            isAuthenticated = true
        })
        return
    }

    val tabs = listOf(
        "Messages" to Icons.Default.Chat,
        "Découvrir" to Icons.Default.Explore,
        "Publier" to Icons.Default.Add,
        "Notifications" to Icons.Default.Notifications,
        "Profil" to Icons.Default.Person,
    )

    Scaffold(
        bottomBar = {
            NavigationBar {
                tabs.forEachIndexed { index, (label, icon) ->
                    NavigationBarItem(
                        selected = selectedTab == index,
                        onClick = { selectedTab = index },
                        icon = { Icon(icon, contentDescription = label) },
                        label = { Text(label) }
                    )
                }
            }
        }
    ) { padding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
        ) {
            when (selectedTab) {
                0 -> MessagesScreen()
                1 -> DiscoverScreen()
                2 -> PublishScreen()
                3 -> NotificationsScreen()
                4 -> ProfileScreen(onLogout = {
                    ApiClient.setToken(null)
                    isAuthenticated = false
                })
            }
        }
    }
}
