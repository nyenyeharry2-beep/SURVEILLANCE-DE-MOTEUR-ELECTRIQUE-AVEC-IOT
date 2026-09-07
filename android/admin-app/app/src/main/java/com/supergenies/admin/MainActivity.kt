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
import okhttp3.OkHttpClient
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.io.File
import java.util.concurrent.TimeUnit

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

object AdminApiClient {
    val api: AdminApi by lazy {
        val client = OkHttpClient.Builder()
            .addInterceptor(HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BASIC })
            .connectTimeout(60, TimeUnit.SECONDS)
            .readTimeout(60, TimeUnit.SECONDS)
            .writeTimeout(60, TimeUnit.SECONDS)
            .build()
        Retrofit.Builder()
            .baseUrl(BuildConfig.API_BASE_URL)
            .client(client)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(AdminApi::class.java)
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
                Text(error!!, color = Color.Red, Modifier.padding(top = 8.dp))
            }
            Spacer(Modifier.height(16.dp))
            Button(
                onClick = {
                    scope.launch {
                        loading = true
                        error = null
                        try {
                            val resp = AdminApiClient.api.login(LoginRequest(password))
                            if (resp.success && resp.token != null) {
                                token = resp.token
                            } else {
                                error = resp.error ?: "Connexion échouée"
                            }
                        } catch (e: Exception) {
                            error = "Erreur de connexion"
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
        DashboardScreen(token!!, onLogout = { token = null })
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(token: String, onLogout: () -> Unit) {
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
                stats = AdminApiClient.api.getStats(token)
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

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Admin - Super Genies") },
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
        }
    ) { padding ->
        LazyColumn(
            Modifier
                .padding(padding)
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
                                val resp = AdminApiClient.api.uploadPdf(token, part, typeBody)
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
