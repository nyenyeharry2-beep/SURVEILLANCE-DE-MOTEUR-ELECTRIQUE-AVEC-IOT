package com.kyrios.app.ui.screens

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.kyrios.app.data.ApiClient
import com.kyrios.app.data.Conversation
import com.kyrios.app.data.User

@Composable
fun MessagesScreen() {
    var conversations by remember { mutableStateOf<List<Conversation>>(emptyList()) }
    var searchQuery by remember { mutableStateOf("") }
    var searchResults by remember { mutableStateOf<List<User>>(emptyList()) }
    var selectedConversation by remember { mutableStateOf<Conversation?>(null) }
    var messages by remember { mutableStateOf<List<com.kyrios.app.data.Message>>(emptyList()) }
    var newMessage by remember { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            conversations = ApiClient.api.getConversations()["conversations"].orEmpty()
        } catch (e: Exception) {
            error = e.message
        }
    }

    if (selectedConversation != null) {
        ConversationScreen(
            conversation = selectedConversation!!,
            messages = messages,
            newMessage = newMessage,
            onMessageChange = { newMessage = it },
            onBack = { selectedConversation = null },
            onSend = {
                // Send handled via LaunchedEffect in ConversationScreen
            },
            onLoad = {
                try {
                    messages = ApiClient.api.getMessages(selectedConversation!!.id)["messages"].orEmpty()
                } catch (e: Exception) {
                    error = e.message
                }
            },
            onSendMessage = {
                try {
                    ApiClient.api.sendMessage(
                        selectedConversation!!.id,
                        mapOf("message" to newMessage)
                    )
                    newMessage = ""
                    messages = ApiClient.api.getMessages(selectedConversation!!.id)["messages"].orEmpty()
                } catch (e: Exception) {
                    error = e.message
                }
            }
        )
        return
    }

    Column(Modifier.fillMaxSize().padding(16.dp)) {
        Text("Messagerie", modifier = Modifier.padding(bottom = 12.dp))
        OutlinedTextField(
            value = searchQuery,
            onValueChange = {
                searchQuery = it
            },
            label = { Text("Rechercher un utilisateur") },
            modifier = Modifier.fillMaxWidth()
        )

        if (searchQuery.length >= 2) {
            LaunchedEffect(searchQuery) {
                try {
                    searchResults = ApiClient.api.searchUsers(searchQuery)["users"].orEmpty()
                } catch (_: Exception) {
                    searchResults = emptyList()
                }
            }
            searchResults.forEach { user ->
                Text(
                    text = "@${user.username} — ${user.display_name ?: ""}",
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable {
                            // Start conversation
                        }
                        .padding(vertical = 8.dp)
                )
            }
        }

        error?.let { Text(it) }

        LazyColumn {
            items(conversations) { conv ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 4.dp)
                        .clickable {
                            selectedConversation = conv
                        }
                ) {
                    Column(Modifier.padding(12.dp)) {
                        Text(conv.title ?: "Conversation #${conv.id}")
                        conv.last_message?.let { Text(it) }
                    }
                }
            }
        }
    }
}

@Composable
private fun ConversationScreen(
    conversation: Conversation,
    messages: List<com.kyrios.app.data.Message>,
    newMessage: String,
    onMessageChange: (String) -> Unit,
    onBack: () -> Unit,
    onSend: () -> Unit,
    onLoad: () -> Unit,
    onSendMessage: () -> Unit,
) {
    LaunchedEffect(conversation.id) { onLoad() }

    Column(Modifier.fillMaxSize().padding(16.dp)) {
        Text("← Retour", modifier = Modifier.clickable { onBack() }.padding(bottom = 8.dp))
        Text(conversation.title ?: "Conversation #${conversation.id}")
        LazyColumn(modifier = Modifier.weight(1f)) {
            items(messages) { msg ->
                Text("${msg.sender_username}: ${msg.message ?: ""}")
            }
        }
        OutlinedTextField(
            value = newMessage,
            onValueChange = onMessageChange,
            label = { Text("Message") },
            modifier = Modifier.fillMaxWidth()
        )
        Text("Envoyer", modifier = Modifier.clickable { onSendMessage() }.padding(top = 8.dp))
    }
}
