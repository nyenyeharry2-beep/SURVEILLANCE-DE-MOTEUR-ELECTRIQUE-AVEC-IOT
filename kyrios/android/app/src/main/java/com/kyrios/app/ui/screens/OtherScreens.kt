package com.kyrios.app.ui.screens

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.kyrios.app.data.ApiClient
import kotlinx.coroutines.launch

@Composable
fun PublishScreen() {
    var content by remember { mutableStateOf("") }
    var status by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    Column(Modifier.fillMaxSize().padding(16.dp)) {
        Text("Publier")
        OutlinedTextField(
            value = content,
            onValueChange = { content = it },
            label = { Text("Quoi de neuf ?") },
            modifier = Modifier.fillMaxWidth()
        )
        status?.let { Text(it) }
        Button(
            onClick = {
                scope.launch {
                    try {
                        ApiClient.api.createPost(mapOf("content" to content))
                        content = ""
                        status = "Publication envoyée"
                    } catch (e: Exception) {
                        status = e.message
                    }
                }
            },
            modifier = Modifier.padding(top = 12.dp)
        ) {
            Text("Publier")
        }
    }
}

@Composable
fun NotificationsScreen() {
    Column(Modifier.fillMaxSize().padding(16.dp)) {
        Text("Notifications")
        Text("Disponible en V2")
    }
}

@Composable
fun ProfileScreen(onLogout: () -> Unit) {
    var user by remember { mutableStateOf<com.kyrios.app.data.User?>(null) }

    androidx.compose.runtime.LaunchedEffect(Unit) {
        try {
            user = ApiClient.api.getMe()["user"]
        } catch (_: Exception) {}
    }

    Column(Modifier.fillMaxSize().padding(16.dp)) {
        Text("Profil")
        user?.let {
            Text("@${it.username}")
            Text(it.display_name ?: "")
            it.bio?.let { bio -> Text(bio) }
            Text("Publications: ${it.posts_count ?: 0}")
            Text("Abonnés: ${it.followers_count ?: 0}")
            Text("Abonnements: ${it.following_count ?: 0}")
        }
        Button(onClick = onLogout, modifier = Modifier.padding(top = 16.dp)) {
            Text("Déconnexion")
        }
    }
}
