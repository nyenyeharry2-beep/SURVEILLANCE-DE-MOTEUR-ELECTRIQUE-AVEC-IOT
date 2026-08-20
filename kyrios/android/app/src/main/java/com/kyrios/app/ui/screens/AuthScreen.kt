package com.kyrios.app.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import com.kyrios.app.data.ApiClient
import kotlinx.coroutines.launch

@Composable
fun AuthScreen(onAuthenticated: () -> Unit) {
    var isLogin by remember { mutableStateOf(true) }
    var username by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }
    var loading by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        verticalArrangement = Arrangement.Center
    ) {
        Text(if (isLogin) "Connexion" else "Inscription")

        Spacer(Modifier.height(16.dp))

        if (!isLogin) {
            OutlinedTextField(
                value = username,
                onValueChange = { username = it },
                label = { Text("Nom d'utilisateur") },
                modifier = Modifier.fillMaxWidth()
            )
            Spacer(Modifier.height(8.dp))
            OutlinedTextField(
                value = email,
                onValueChange = { email = it },
                label = { Text("E-mail") },
                modifier = Modifier.fillMaxWidth()
            )
            Spacer(Modifier.height(8.dp))
        } else {
            OutlinedTextField(
                value = username,
                onValueChange = { username = it },
                label = { Text("Identifiant (username, e-mail ou téléphone)") },
                modifier = Modifier.fillMaxWidth()
            )
            Spacer(Modifier.height(8.dp))
        }

        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text("Mot de passe") },
            visualTransformation = PasswordVisualTransformation(),
            modifier = Modifier.fillMaxWidth()
        )

        error?.let {
            Spacer(Modifier.height(8.dp))
            Text(it, color = androidx.compose.material3.MaterialTheme.colorScheme.error)
        }

        Spacer(Modifier.height(16.dp))

        Button(
            onClick = {
                loading = true
                error = null
                scope.launch {
                    try {
                        val response = if (isLogin) {
                            ApiClient.api.login(
                                mapOf("identifier" to username, "password" to password)
                            )
                        } else {
                            ApiClient.api.register(
                                mapOf(
                                    "username" to username,
                                    "email" to email,
                                    "password" to password,
                                    "display_name" to username
                                )
                            )
                        }
                        if (response.success && response.token != null) {
                            ApiClient.setToken(response.token)
                            onAuthenticated()
                        } else {
                            error = response.error ?: "Authentication failed"
                        }
                    } catch (e: Exception) {
                        error = e.message ?: "Network error"
                    } finally {
                        loading = false
                    }
                }
            },
            enabled = !loading,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(if (isLogin) "Se connecter" else "Créer un compte")
        }

        TextButton(onClick = { isLogin = !isLogin }) {
            Text(if (isLogin) "Pas de compte ? S'inscrire" else "Déjà un compte ? Se connecter")
        }
    }
}
