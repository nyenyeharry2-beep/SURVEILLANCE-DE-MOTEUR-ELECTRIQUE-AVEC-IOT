package com.kyrios.app.ui.screens

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.kyrios.app.data.ApiClient
import com.kyrios.app.data.Post
import kotlinx.coroutines.launch

@Composable
fun DiscoverScreen() {
    var posts by remember { mutableStateOf<List<Post>>(emptyList()) }
    var error by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    LaunchedEffect(Unit) {
        try {
            posts = ApiClient.api.getPosts()["posts"].orEmpty()
        } catch (e: Exception) {
            error = e.message
        }
    }

    Column(Modifier.fillMaxSize().padding(16.dp)) {
        Text("Découvrir")
        error?.let { Text(it) }
        LazyColumn {
            items(posts) { post ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 6.dp)
                ) {
                    Column(Modifier.padding(12.dp)) {
                        Text("@${post.username ?: post.display_name ?: "user"}")
                        Text(post.content)
                        Text("❤ ${post.likes_count ?: 0} · 💬 ${post.comments_count ?: 0}")
                        Button(onClick = {
                            scope.launch {
                                try {
                                    ApiClient.api.likePost(post.id)
                                    posts = ApiClient.api.getPosts()["posts"].orEmpty()
                                } catch (_: Exception) {}
                            }
                        }) {
                            Text("J'aime")
                        }
                    }
                }
            }
        }
    }
}
