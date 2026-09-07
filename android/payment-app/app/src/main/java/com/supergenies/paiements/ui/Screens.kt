package com.supergenies.paiements.ui

import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.ShoppingBag
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardCapitalization
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.supergenies.paiements.ApiClient
import com.supergenies.paiements.R
import com.supergenies.paiements.data.*
import com.supergenies.paiements.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SuperGeniesApp() {
    var selectedTab by remember { mutableIntStateOf(0) }
    val tabs = listOf("Accueil", "Inscriptions", "Trousseau")

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Image(
                            painter = painterResource(R.drawable.logo_spag),
                            contentDescription = "Logo SPAG",
                            modifier = Modifier.size(40.dp)
                        )
                        Spacer(Modifier.width(8.dp))
                        Column {
                            Text("C.S. LES SUPER GENIES", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                            Text("Suivi des paiements", fontSize = 11.sp)
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = NavyBlue,
                    titleContentColor = Color.White
                )
            )
        },
        bottomBar = {
            NavigationBar(containerColor = Color.White) {
                tabs.forEachIndexed { index, label ->
                    val icon = when (index) {
                        0 -> Icons.Default.Search
                        1 -> Icons.Default.Info
                        else -> Icons.Default.ShoppingBag
                    }
                    NavigationBarItem(
                        selected = selectedTab == index,
                        onClick = { selectedTab = index },
                        icon = { Icon(icon, contentDescription = label) },
                        label = { Text(label, fontSize = 11.sp) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = NavyBlue,
                            selectedTextColor = NavyBlue,
                            indicatorColor = RoyalBlue.copy(alpha = 0.15f)
                        )
                    )
                }
            }
        }
    ) { padding ->
        Box(Modifier.padding(padding)) {
            when (selectedTab) {
                0 -> HomeScreen()
                1 -> InscriptionsScreen()
                2 -> TrousseauScreen()
            }
        }
    }
}

data class CarouselItem(val imageRes: Int, val title: String)

@OptIn(ExperimentalFoundationApi::class)
@Composable
fun HomeScreen() {
    val carouselItems = listOf(
        CarouselItem(R.drawable.carousel_felicitations, "Félicitations à nos finalistes"),
        CarouselItem(R.drawable.carousel_petrochimie, "Pétrochimie - Inscription"),
        CarouselItem(R.drawable.carousel_inscriptions, "Inscriptions 2026-2027")
    )

    var matricule by remember { mutableStateOf("") }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var result by remember { mutableStateOf<StudentResponse?>(null) }
    val scope = rememberCoroutineScope()
    val focusManager = LocalFocusManager.current

    fun search() {
        if (matricule.trim().isEmpty()) {
            error = "Veuillez entrer un matricule"
            return
        }
        scope.launch {
            loading = true
            error = null
            result = null
            try {
                result = ApiClient.service.getStudent(matricule.trim().uppercase())
            } catch (e: HttpException) {
                error = if (e.code() == 404) "Matricule non trouvé" else "Erreur serveur (${e.code()})"
            } catch (e: Exception) {
                error = "Connexion impossible. Vérifiez votre internet."
            } finally {
                loading = false
            }
        }
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        item {
            Text(
                "Bienvenue chers parents",
                style = MaterialTheme.typography.headlineSmall,
                fontWeight = FontWeight.Bold,
                color = NavyBlue
            )
            Text(
                "Entrez le matricule de votre enfant pour consulter ses frais et paiements.",
                style = MaterialTheme.typography.bodyMedium,
                color = Color.Gray
            )
        }

        item {
            val pagerState = rememberPagerState(pageCount = { carouselItems.size })
            Column {
                HorizontalPager(
                    state = pagerState,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(200.dp)
                        .clip(RoundedCornerShape(16.dp))
                ) { page ->
                    Box {
                        Image(
                            painter = painterResource(carouselItems[page].imageRes),
                            contentDescription = carouselItems[page].title,
                            modifier = Modifier.fillMaxSize(),
                            contentScale = ContentScale.Crop
                        )
                        Box(
                            Modifier
                                .align(Alignment.BottomCenter)
                                .fillMaxWidth()
                                .background(NavyBlue.copy(alpha = 0.7f))
                                .padding(8.dp)
                        ) {
                            Text(
                                carouselItems[page].title,
                                color = Color.White,
                                fontWeight = FontWeight.SemiBold,
                                textAlign = TextAlign.Center,
                                modifier = Modifier.fillMaxWidth()
                            )
                        }
                    }
                }
                Spacer(Modifier.height(8.dp))
                Row(
                    Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.Center
                ) {
                    repeat(carouselItems.size) { index ->
                        Box(
                            Modifier
                                .padding(4.dp)
                                .size(if (pagerState.currentPage == index) 10.dp else 8.dp)
                                .clip(CircleShape)
                                .background(if (pagerState.currentPage == index) SchoolRed else Color.LightGray)
                        )
                    }
                }
            }
        }

        item {
            OutlinedTextField(
                value = matricule,
                onValueChange = { matricule = it.uppercase() },
                label = { Text("Matricule élève") },
                placeholder = { Text("CSLSG-2026-2027-00167") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                keyboardOptions = KeyboardOptions(
                    capitalization = KeyboardCapitalization.Characters,
                    imeAction = ImeAction.Search
                ),
                keyboardActions = KeyboardActions(onSearch = {
                    focusManager.clearFocus()
                    search()
                }),
                leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) }
            )
            Spacer(Modifier.height(8.dp))
            Button(
                onClick = { search() },
                modifier = Modifier.fillMaxWidth(),
                enabled = !loading,
                colors = ButtonDefaults.buttonColors(containerColor = SchoolRed)
            ) {
                if (loading) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(20.dp),
                        color = Color.White,
                        strokeWidth = 2.dp
                    )
                } else {
                    Text("Rechercher")
                }
            }
        }

        if (error != null) {
            item {
                Card(
                    colors = CardDefaults.cardColors(containerColor = UnpaidRed.copy(alpha = 0.1f)),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(error!!, Modifier.padding(16.dp), color = UnpaidRed)
                }
            }
        }

        result?.student?.let { student ->
            item { StudentCard(student, result!!.summary) }
        }

        result?.fees?.let { fees ->
            if (fees.isNotEmpty()) {
                item {
                    Text("Frais attribués", fontWeight = FontWeight.Bold, color = NavyBlue)
                }
                items(fees) { fee -> FeeCard(fee) }
            } else if (result != null) {
                item {
                    Card(Modifier.fillMaxWidth()) {
                        Text(
                            "Aucun frais enregistré pour cet élève.",
                            Modifier.padding(16.dp),
                            color = Color.Gray
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun StudentCard(student: Student, summary: FeeSummary?) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(4.dp)
    ) {
        Column(Modifier.padding(16.dp)) {
            Text(student.nom_complet, fontWeight = FontWeight.Bold, fontSize = 18.sp, color = NavyBlue)
            Spacer(Modifier.height(4.dp))
            InfoRow("Matricule", student.matricule)
            InfoRow("Classe", student.classe)
            student.section?.let { InfoRow("Section", it) }
            InfoRow("Inscription", student.statut_inscription)
            InfoRow("Année", student.annee_scolaire)

            summary?.let { s ->
                Spacer(Modifier.height(12.dp))
                HorizontalDivider()
                Spacer(Modifier.height(12.dp))
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Column {
                        Text("Total dû", fontSize = 12.sp, color = Color.Gray)
                        Text("$${"%.2f".format(s.total_du)}", fontWeight = FontWeight.Bold)
                    }
                    Column(horizontalAlignment = Alignment.End) {
                        Text("Total payé", fontSize = 12.sp, color = Color.Gray)
                        Text("$${"%.2f".format(s.total_paye)}", fontWeight = FontWeight.Bold, color = PaidGreen)
                    }
                }
                Spacer(Modifier.height(8.dp))
                val statusColor = if (s.en_ordre) PaidGreen else UnpaidRed
                val statusText = if (s.en_ordre) "✓ En ordre de paiement" else "Solde: $${"%.2f".format(s.solde)}"
                Surface(
                    color = statusColor.copy(alpha = 0.15f),
                    shape = RoundedCornerShape(8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(
                        statusText,
                        Modifier.padding(12.dp),
                        color = statusColor,
                        fontWeight = FontWeight.SemiBold,
                        textAlign = TextAlign.Center,
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            }
        }
    }
}

@Composable
fun InfoRow(label: String, value: String) {
    Row(Modifier.padding(vertical = 2.dp)) {
        Text("$label: ", fontSize = 13.sp, color = Color.Gray)
        Text(value, fontSize = 13.sp)
    }
}

@Composable
fun FeeCard(fee: Fee) {
    val statusColor = when (fee.statut) {
        "paye" -> PaidGreen
        "partiel" -> PartialOrange
        "exempt" -> RoyalBlue
        else -> UnpaidRed
    }
    val statusLabel = when (fee.statut) {
        "paye" -> "Payé"
        "partiel" -> "Partiel"
        "exempt" -> "Exempté"
        else -> "Impayé"
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = Color.White)
    ) {
        Row(
            Modifier
                .fillMaxWidth()
                .padding(12.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(Modifier.weight(1f)) {
                Text(fee.label, fontWeight = FontWeight.Medium)
                fee.mois?.let { Text("Mois $it", fontSize = 11.sp, color = Color.Gray) }
                Text(
                    "Dû: $${"%.2f".format(fee.montant_du)} · Payé: $${"%.2f".format(fee.montant_paye)}",
                    fontSize = 12.sp,
                    color = Color.Gray
                )
            }
            Surface(color = statusColor.copy(alpha = 0.15f), shape = RoundedCornerShape(8.dp)) {
                Text(statusLabel, Modifier.padding(horizontal = 10.dp, vertical = 4.dp), color = statusColor, fontSize = 12.sp)
            }
        }
    }
}

@Composable
fun InscriptionsScreen() {
    var data by remember { mutableStateOf<InscriptionsResponse?>(null) }
    var loading by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            data = ApiClient.service.getInscriptions()
        } catch (e: Exception) {
            error = "Impossible de charger les informations"
        } finally {
            loading = false
        }
    }

    when {
        loading -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            CircularProgressIndicator(color = NavyBlue)
        }
        error != null -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Text(error!!, color = UnpaidRed)
        }
        data != null -> LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            item {
                Image(
                    painter = painterResource(R.drawable.logo_banner),
                    contentDescription = null,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(120.dp),
                    contentScale = ContentScale.Fit
                )
                Text(data!!.title, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold, color = NavyBlue)
                Text(data!!.school.address, fontSize = 13.sp, color = Color.Gray)
                Text("${data!!.school.phone} · ${data!!.school.email}", fontSize = 12.sp, color = Color.Gray)
            }
            items(data!!.sections) { section ->
                Card(Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp)) {
                        Text(section.title, fontWeight = FontWeight.Bold, color = SchoolRed)
                        section.slogan?.let { Text(it, fontStyle = androidx.compose.ui.text.font.FontStyle.Italic, fontSize = 13.sp) }
                        section.description?.let { Text(it, fontSize = 13.sp, Modifier.padding(top = 4.dp)) }
                        section.notes?.forEach { note ->
                            Text("• $note", fontSize = 12.sp, Modifier.padding(top = 4.dp))
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun TrousseauScreen() {
    var data by remember { mutableStateOf<TrousseauResponse?>(null) }
    var loading by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            data = ApiClient.service.getTrousseau()
        } catch (e: Exception) {
            error = "Impossible de charger le trousseau"
        } finally {
            loading = false
        }
    }

    when {
        loading -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            CircularProgressIndicator(color = NavyBlue)
        }
        error != null -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Text(error!!, color = UnpaidRed)
        }
        data != null -> LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            item {
                Text(data!!.title, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold, color = NavyBlue)
                Text("Année scolaire ${data!!.annee_scolaire}", color = Color.Gray)
            }
            item {
                Text("Équipements", fontWeight = FontWeight.Bold, color = SchoolRed)
            }
            items(data!!.equipements) { eq ->
                Card(Modifier.fillMaxWidth()) {
                    Row(
                        Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text(eq.label, Modifier.weight(1f))
                        Text("$${eq.montant}", fontWeight = FontWeight.Bold, color = NavyBlue)
                    }
                }
            }
            item {
                Text("Uniformes", fontWeight = FontWeight.Bold, color = SchoolRed)
                Card(Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(12.dp)) {
                        Text("Filles: ${data!!.uniformes["filles"]}")
                        Text("Garçons: ${data!!.uniformes["garcons"]}")
                    }
                }
            }
            item {
                Text("Politiques", fontWeight = FontWeight.Bold, color = SchoolRed)
            }
            items(data!!.politiques) { policy ->
                Text("• $policy", fontSize = 13.sp)
            }
        }
    }
}
