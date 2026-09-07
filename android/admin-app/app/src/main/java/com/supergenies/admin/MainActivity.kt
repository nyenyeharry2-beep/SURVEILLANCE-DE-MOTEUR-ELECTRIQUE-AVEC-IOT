package com.supergenies.admin

import android.content.Context
import android.net.Uri
import android.os.Bundle
import android.provider.OpenableColumns
import androidx.activity.ComponentActivity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.compose.setContent
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CloudUpload
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.Logout
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            MaterialTheme(colorScheme = lightColorScheme(primary = Color(0xFF1B3A6B))) {
                AdminApp()
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminApp() {
    var token by remember { mutableStateOf<String?>(null) }
    var password by remember { mutableStateOf("") }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    if (token == null) {
        Column(
            Modifier
                .fillMaxSize()
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Image(painterResource(R.drawable.logo_spag), null, Modifier.size(80.dp))
            Spacer(Modifier.height(16.dp))
            Text("Administration", fontSize = 22.sp, fontWeight = FontWeight.Bold)
            Text("C.S. LES SUPER GENIES", color = Color.Gray)
            Spacer(Modifier.height(24.dp))
            OutlinedTextField(
                value = password,
                onValueChange = { password = it },
                label = { Text("Mot de passe") },
                visualTransformation = PasswordVisualTransformation(),
                modifier = Modifier.fillMaxWidth(),
                singleLine = true
            )
            if (error != null) {
                Text(error!!, color = Color.Red, modifier = Modifier.padding(top = 8.dp))
            }
            Spacer(Modifier.height(16.dp))
            Button(
                onClick = {
                    scope.launch {
                        loading = true
                        error = null
                        try {
                            val resp = AdminApiClient.call { it.login(LoginRequest(password)) }
                            if (resp.success && resp.token != null) {
                                token = resp.token
                            } else {
                                error = resp.error ?: "Connexion échouée"
                            }
                        } catch (e: Exception) {
                            error = if (e.message?.contains("mot de passe", true) == true)
                                "Mot de passe incorrect"
                            else ApiConfig.ADMIN_ERROR_NETWORK
                        } finally {
                            loading = false
                        }
                    }
                },
                enabled = !loading && password.isNotEmpty(),
                modifier = Modifier.fillMaxWidth(),
                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFC62828))
            ) {
                if (loading) CircularProgressIndicator(Modifier.size(20.dp), color = Color.White, strokeWidth = 2.dp)
                else Text("Se connecter")
            }
            Text(
                "Mot de passe par défaut: SuperGenies2026!",
                fontSize = 11.sp,
                color = Color.Gray,
                modifier = Modifier.padding(top = 16.dp)
            )
        }
    } else {
        AdminMainScreen(token!!, onLogout = { token = null })
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminMainScreen(token: String, onLogout: () -> Unit) {
    var selectedTab by remember { mutableIntStateOf(0) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (selectedTab == 0) "Admin - Super Genies" else "Messagerie Facturation") },
                actions = {
                    IconButton(onClick = onLogout) {
                        Icon(Icons.Default.Logout, "Déconnexion")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = Color(0xFF1B3A6B),
                    titleContentColor = Color.White,
                    actionIconContentColor = Color.White
                )
            )
        },
        bottomBar = {
            NavigationBar {
                NavigationBarItem(
                    selected = selectedTab == 0,
                    onClick = { selectedTab = 0 },
                    icon = { Icon(Icons.Default.CloudUpload, null) },
                    label = { Text("Imports") }
                )
                NavigationBarItem(
                    selected = selectedTab == 1,
                    onClick = { selectedTab = 1 },
                    icon = { Icon(Icons.Default.Email, null) },
                    label = { Text("Messages") }
                )
            }
        }
    ) { padding ->
        Box(Modifier.padding(padding)) {
            when (selectedTab) {
                0 -> DashboardScreen(token)
                1 -> MessagesScreen(token)
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(token: String) {
    var stats by remember { mutableStateOf<StatsResponse?>(null) }
    var importType by remember { mutableStateOf("auto") }
    var selectedUri by remember { mutableStateOf<Uri?>(null) }
    var selectedName by remember { mutableStateOf<String?>(null) }
    var uploading by remember { mutableStateOf(false) }
    var uploadResult by remember { mutableStateOf<String?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    val context = LocalContext.current
    val scope = rememberCoroutineScope()

    fun refreshStats() {
        scope.launch {
            try {
                stats = AdminApiClient.call { it.getStats(token) }
            } catch (_: Exception) {}
        }
    }

    LaunchedEffect(token) { refreshStats() }

    val pdfLauncher = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri ->
        selectedUri = uri
        selectedName = uri?.let { getFileName(context, it) }
        uploadResult = null
        error = null
    }

    LazyColumn(
        Modifier
            .fillMaxSize()
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
            stats?.stats?.let { s ->
                item {
                    Text("Statistiques", fontWeight = FontWeight.Bold, fontSize = 18.sp)
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        StatCard("Élèves", s.total_students.toString(), Modifier.weight(1f))
                        StatCard("Frais", s.total_fees.toString(), Modifier.weight(1f))
                        StatCard("Impayés", s.fees_impayes.toString(), Modifier.weight(1f))
                    }
                }
            }

            item {
                Text("Publier une fiche PDF", fontWeight = FontWeight.Bold, fontSize = 18.sp)
                Text(
                    "Importez une fiche d'inscriptions ou de paiements. Les matricules seront classés automatiquement par classe et section.",
                    fontSize = 13.sp,
                    color = Color.Gray
                )
                Spacer(Modifier.height(8.dp))

                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    listOf("auto" to "Auto", "inscriptions" to "Inscriptions", "paiements" to "Paiements").forEach { (value, label) ->
                        FilterChip(
                            selected = importType == value,
                            onClick = { importType = value },
                            label = { Text(label) }
                        )
                    }
                }

                Spacer(Modifier.height(8.dp))

                OutlinedButton(
                    onClick = { pdfLauncher.launch("application/pdf") },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Icon(Icons.Default.CloudUpload, null)
                    Spacer(Modifier.width(8.dp))
                    Text(selectedName ?: "Choisir un fichier PDF")
                }

                Spacer(Modifier.height(8.dp))

                Button(
                    onClick = {
                        val uri = selectedUri ?: return@Button
                        scope.launch {
                            uploading = true
                            error = null
                            uploadResult = null
                            try {
                                val file = uriToTempFile(context, uri)
                                val requestFile = file.asRequestBody("application/pdf".toMediaTypeOrNull())
                                val part = MultipartBody.Part.createFormData("pdf", file.name, requestFile)
                                val typeBody = importType.toRequestBody("text/plain".toMediaTypeOrNull())
                                val resp = AdminApiClient.call { it.uploadPdf(token, part, typeBody) }
                                if (resp.success) {
                                    uploadResult = resp.message ?: "Import réussi"
                                    refreshStats()
                                } else {
                                    error = resp.error ?: "Import échoué"
                                }
                            } catch (e: Exception) {
                                error = "Erreur: ${e.message}"
                            } finally {
                                uploading = false
                            }
                        }
                    },
                    enabled = selectedUri != null && !uploading,
                    modifier = Modifier.fillMaxWidth(),
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFC62828))
                ) {
                    if (uploading) CircularProgressIndicator(Modifier.size(20.dp), color = Color.White, strokeWidth = 2.dp)
                    else Text("Publier et importer")
                }

                uploadResult?.let {
                    Card(colors = CardDefaults.cardColors(containerColor = Color(0xFFE8F5E9)), modifier = Modifier.fillMaxWidth().padding(top = 8.dp)) {
                        Text(it, Modifier.padding(12.dp), color = Color(0xFF2E7D32))
                    }
                }
                error?.let {
                    Card(colors = CardDefaults.cardColors(containerColor = Color(0xFFFFEBEE)), modifier = Modifier.fillMaxWidth().padding(top = 8.dp)) {
                        Text(it, Modifier.padding(12.dp), color = Color.Red)
                    }
                }
            }

            stats?.par_classe?.let { classes ->
                if (classes.isNotEmpty()) {
                    item { Text("Par classe", fontWeight = FontWeight.Bold, fontSize = 16.sp) }
                    items(classes) { c ->
                        Card(Modifier.fillMaxWidth()) {
                            Row(
                                Modifier
                                    .fillMaxWidth()
                                    .padding(12.dp),
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Column {
                                    Text(c.classe, fontWeight = FontWeight.Medium)
                                    c.section?.let { Text(it, fontSize = 12.sp, color = Color.Gray) }
                                }
                                Text("${c.total} élèves", fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }
            }

            stats?.derniers_imports?.let { imports ->
                if (imports.isNotEmpty()) {
                    item { Text("Derniers imports", fontWeight = FontWeight.Bold, fontSize = 16.sp) }
                    items(imports) { imp ->
                        Card(Modifier.fillMaxWidth()) {
                            Column(Modifier.padding(12.dp)) {
                                Text("${imp.typeImport.uppercase()} - ${imp.fichier}", fontWeight = FontWeight.Medium, fontSize = 13.sp)
                                imp.classeDetectee?.let { Text("Classe: $it", fontSize = 12.sp) }
                                Text("${imp.lignesTraitees} lignes · ${imp.importedAt}", fontSize = 11.sp, color = Color.Gray)
                            }
                        }
                    }
                }
            }
    }
}

val MOTIF_LABELS = mapOf(
    "paiement_non_enregistre" to "Paiement non enregistré",
    "montant_incorrect" to "Montant incorrect",
    "double_paiement" to "Double paiement",
    "probleme_inscription" to "Problème d'inscription",
    "autre" to "Autre"
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MessagesScreen(token: String) {
    var messagesData by remember { mutableStateOf<MessagesResponse?>(null) }
    var filter by remember { mutableStateOf("all") }
    var loading by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    fun loadMessages() {
        scope.launch {
            loading = true
            error = null
            try {
                messagesData = AdminApiClient.call {
                    it.getMessages(token, if (filter == "all") null else filter)
                }
            } catch (e: Exception) {
                error = "Impossible de charger les messages"
            } finally {
                loading = false
            }
        }
    }

    LaunchedEffect(token, filter) { loadMessages() }

    Column(Modifier.fillMaxSize()) {
        Row(
            Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 8.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            listOf("all" to "Tous", "nouveau" to "Nouveaux", "en_cours" to "En cours", "traite" to "Traités").forEach { (value, label) ->
                FilterChip(
                    selected = filter == value,
                    onClick = { filter = value },
                    label = {
                        val count = when (value) {
                            "nouveau" -> messagesData?.counts?.nouveau
                            "en_cours" -> messagesData?.counts?.en_cours
                            "traite" -> messagesData?.counts?.traite
                            else -> messagesData?.counts?.total
                        }
                        Text(if (value == "all") label else "$label (${count ?: 0})")
                    }
                )
            }
        }

        when {
            loading -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = Color(0xFF1B3A6B))
            }
            error != null -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Text(error!!, color = Color.Red)
            }
            messagesData?.messages.isNullOrEmpty() -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Text("Aucun message", color = Color.Gray)
            }
            else -> LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = 16.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp),
                contentPadding = PaddingValues(bottom = 16.dp)
            ) {
                items(messagesData!!.messages!!) { msg ->
                    MessageCard(msg, token) { loadMessages() }
                }
            }
        }
    }
}

@Composable
fun MessageCard(msg: ParentMessage, token: String, onUpdated: () -> Unit) {
    val scope = rememberCoroutineScope()
    var updating by remember { mutableStateOf(false) }

    val statutColor = when (msg.statut) {
        "nouveau" -> Color(0xFFC62828)
        "en_cours" -> Color(0xFFF57C00)
        else -> Color(0xFF2E7D32)
    }
    val statutLabel = when (msg.statut) {
        "nouveau" -> "Nouveau"
        "en_cours" -> "En cours"
        else -> "Traité"
    }

    Card(Modifier.fillMaxWidth(), elevation = CardDefaults.cardElevation(2.dp)) {
        Column(Modifier.padding(14.dp)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text(msg.nomParent, fontWeight = FontWeight.Bold, fontSize = 16.sp)
                Surface(color = statutColor.copy(alpha = 0.15f), shape = RoundedCornerShape(8.dp)) {
                    Text(statutLabel, Modifier.padding(horizontal = 8.dp, vertical = 4.dp), color = statutColor, fontSize = 11.sp)
                }
            }
            Text("📞 ${msg.telephoneParent}", fontSize = 13.sp, color = Color.Gray)
            Spacer(Modifier.height(6.dp))
            Text("Élève: ${msg.nomEleve ?: ""} ${msg.prenomEleve ?: ""}".trim(), fontSize = 13.sp)
            Text("Matricule: ${msg.matricule}", fontSize = 12.sp, color = Color.Gray)
            msg.classeEleve?.let { Text("Classe: $it", fontSize = 12.sp, fontWeight = FontWeight.Medium, color = Color(0xFF1B3A6B)) }
            msg.sectionEleve?.let { Text("Section: $it", fontSize = 12.sp) }
            Spacer(Modifier.height(6.dp))
            Surface(color = Color(0xFFF5F5F5), shape = RoundedCornerShape(8.dp), modifier = Modifier.fillMaxWidth()) {
                Column(Modifier.padding(10.dp)) {
                    Text("Motif: ${MOTIF_LABELS[msg.motif] ?: msg.motif}", fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                    Text(msg.message, fontSize = 13.sp, modifier = Modifier.padding(top = 4.dp))
                }
            }
            Text("Reçu le ${msg.createdAt}", fontSize = 10.sp, color = Color.Gray, modifier = Modifier.padding(top = 6.dp))

            if (msg.statut != "traite") {
                Spacer(Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    if (msg.statut == "nouveau") {
                        OutlinedButton(
                            onClick = {
                                scope.launch {
                                    updating = true
                                    try {
                                        AdminApiClient.call {
                                            it.updateMessageStatus(token, UpdateMessageRequest(id = msg.id, statut = "en_cours"))
                                        }
                                        onUpdated()
                                    } finally { updating = false }
                                }
                            },
                            enabled = !updating,
                            modifier = Modifier.weight(1f)
                        ) { Text("Prendre en charge", fontSize = 11.sp) }
                    }
                    Button(
                        onClick = {
                            scope.launch {
                                updating = true
                                try {
                                    AdminApiClient.call {
                                        it.updateMessageStatus(token, UpdateMessageRequest(id = msg.id, statut = "traite"))
                                    }
                                    onUpdated()
                                } finally { updating = false }
                            }
                        },
                        enabled = !updating,
                        modifier = Modifier.weight(1f),
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF2E7D32))
                    ) { Text("Marquer traité", fontSize = 11.sp) }
                }
            }
        }
    }
}

@Composable
fun StatCard(label: String, value: String, modifier: Modifier = Modifier) {
    Card(modifier, shape = RoundedCornerShape(12.dp)) {
        Column(Modifier.padding(12.dp), horizontalAlignment = Alignment.CenterHorizontally) {
            Text(value, fontWeight = FontWeight.Bold, fontSize = 20.sp, color = Color(0xFF1B3A6B))
            Text(label, fontSize = 12.sp, color = Color.Gray)
        }
    }
}

fun getFileName(context: Context, uri: Uri): String {
    context.contentResolver.query(uri, null, null, null, null)?.use { cursor ->
        if (cursor.moveToFirst()) {
            val idx = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
            if (idx >= 0) return cursor.getString(idx)
        }
    }
    return "document.pdf"
}

fun uriToTempFile(context: Context, uri: Uri): File {
    val name = getFileName(context, uri)
    val temp = File(context.cacheDir, name)
    context.contentResolver.openInputStream(uri)?.use { input ->
        temp.outputStream().use { output -> input.copyTo(output) }
    }
    return temp
}
