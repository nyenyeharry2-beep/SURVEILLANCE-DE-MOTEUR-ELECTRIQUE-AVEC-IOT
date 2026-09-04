package com.nouvelleeve.pharmacie

import android.content.Context
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject
import java.io.BufferedReader
import java.io.InputStreamReader
import java.io.OutputStreamWriter
import java.net.HttpURLConnection
import java.net.URL

class ApiException(val code: Int, message: String) : Exception(message)

object ApiConfig {
    const val BASE = "https://mapharmaciepk.xo.je/api"
}

class ApiClient(
    private val appContext: Context,
    private val token: String? = null,
    private val sessionId: String? = null
) {
    private val cookieHelper = InfinityFreeCookieHelper(appContext)

    private fun authToken(): String? = token?.trim()?.takeIf { it.isNotBlank() }
    private fun authSessionId(): String? = sessionId?.trim()?.takeIf { it.isNotBlank() }

    private fun withAuthQuery(url: String): String {
        val sb = StringBuilder(url)
        fun appendParam(name: String, value: String) {
            sb.append(if (sb.contains('?')) '&' else '?')
            sb.append(name).append('=').append(encode(value))
        }
        authToken()?.let { appendParam("token", it) }
        authSessionId()?.let { appendParam("sid", it) }
        return sb.toString()
    }

    suspend fun ping(): JSONObject = execute("GET", "${ApiConfig.BASE}/ping.php", null)

    suspend fun login(email: String, password: String): JSONObject {
        return execute(
            "POST",
            "${ApiConfig.BASE}/login.php",
            JSONObject().apply {
                put("email", email)
                put("password", password)
            },
            auth = false
        )
    }

    suspend fun logout(): JSONObject {
        val body = JSONObject().apply {
            authToken()?.let { put("token", it) }
            authSessionId()?.let { put("sid", it) }
        }
        return execute("POST", withAuthQuery("${ApiConfig.BASE}/logout.php"), body)
    }

    suspend fun getMedicaments(query: String = ""): JSONObject {
        val url = if (query.isBlank()) {
            "${ApiConfig.BASE}/medicaments.php"
        } else {
            "${ApiConfig.BASE}/medicaments.php?q=${encode(query)}"
        }
        return execute("GET", withAuthQuery(url), null)
    }

    suspend fun getStock(query: String = ""): JSONObject {
        val url = if (query.isBlank()) {
            "${ApiConfig.BASE}/stock.php"
        } else {
            "${ApiConfig.BASE}/stock.php?q=${encode(query)}"
        }
        return execute("GET", withAuthQuery(url), null)
    }

    suspend fun createVente(
        lignes: List<Triple<Int, Int, Double>>,
        devise: String,
        clientNom: String,
        notes: String = ""
    ): JSONObject {
        val arr = org.json.JSONArray()
        lignes.forEach { (medId, qty, prix) ->
            arr.put(JSONObject().apply {
                put("medicament_id", medId)
                put("quantite", qty)
                if (prix > 0) put("prix_unitaire", prix)
            })
        }
        return execute(
            "POST",
            withAuthQuery("${ApiConfig.BASE}/ventes.php"),
            JSONObject().apply {
                put("lignes", arr)
                put("devise", devise)
                put("client_nom", clientNom)
                if (notes.isNotBlank()) put("notes", notes)
            }
        )
    }

    suspend fun getJournee(date: String? = null): JSONObject {
        val url = buildString {
            append("${ApiConfig.BASE}/journee.php")
            if (!date.isNullOrBlank()) append("?date=${encode(date)}")
        }
        return execute("GET", withAuthQuery(url), null)
    }

    suspend fun getHistoriqueVentes(date: String? = null, limit: Int = 50): JSONObject {
        val params = buildString {
            append("${ApiConfig.BASE}/ventes.php?liste=1&limit=$limit")
            if (!date.isNullOrBlank()) append("&date=${encode(date)}")
        }
        return execute("GET", withAuthQuery(params), null)
    }

    suspend fun getRecu(venteId: Int): JSONObject =
        execute("GET", withAuthQuery("${ApiConfig.BASE}/recu.php?id=$venteId"), null)

    suspend fun rapportJour(date: String): JSONObject =
        execute("GET", withAuthQuery("${ApiConfig.BASE}/rapports.php?type=jour&date=$date"), null)

    suspend fun rapportMois(annee: Int, mois: Int): JSONObject =
        execute("GET", withAuthQuery("${ApiConfig.BASE}/rapports.php?type=mois&annee=$annee&mois=$mois"), null)

    suspend fun getAlertes(type: String = "all"): JSONObject =
        execute("GET", withAuthQuery("${ApiConfig.BASE}/alertes.php?type=${encode(type)}"), null)

    suspend fun getCaisse(date: String? = null): JSONObject {
        val url = buildString {
            append("${ApiConfig.BASE}/caisse.php")
            if (!date.isNullOrBlank()) append("?date=${encode(date)}")
        }
        return execute("GET", withAuthQuery(url), null)
    }

    suspend fun createMouvementCaisse(
        type: String,
        montant: Double,
        devise: String,
        motif: String
    ): JSONObject {
        return execute(
            "POST",
            withAuthQuery("${ApiConfig.BASE}/caisse.php"),
            JSONObject().apply {
                put("type", type)
                put("montant", montant)
                put("devise", devise)
                put("motif", motif)
            }
        )
    }

    private fun encode(value: String): String =
        java.net.URLEncoder.encode(value, Charsets.UTF_8.name())

    private suspend fun execute(
        method: String,
        urlString: String,
        body: JSONObject?,
        auth: Boolean = true
    ): JSONObject = withContext(Dispatchers.IO) {
        withContext(Dispatchers.Main) {
            cookieHelper.ensureCookie()
        }

        val requestBody = when {
            body != null && (authToken() != null || authSessionId() != null) ->
                JSONObject(body.toString()).apply {
                    authToken()?.let { put("token", it) }
                    authSessionId()?.let { put("sid", it) }
                }
            else -> body
        }

        var result = rawRequest(method, urlString, requestBody, auth)

        if (InfinityFreeChallenge.isChallenge(result.body)) {
            if (!InfinityFreeChallenge.solveAndStore(result.body)) {
                withContext(Dispatchers.Main) {
                    cookieHelper.ensureCookie()
                }
            }
            result = rawRequest(method, urlString, requestBody, auth)
        }

        if (InfinityFreeChallenge.isChallenge(result.body)) {
            if (InfinityFreeChallenge.solveAndStore(result.body)) {
                result = rawRequest(method, urlString, requestBody, auth)
            }
        }

        parseResponse(result.code, result.body)
    }

    private data class HttpResult(val code: Int, val body: String)

    private fun rawRequest(method: String, urlString: String, body: JSONObject?, auth: Boolean): HttpResult {
        val conn = (URL(urlString).openConnection() as HttpURLConnection).apply {
            requestMethod = method
            connectTimeout = 30000
            readTimeout = 30000
            setRequestProperty("Content-Type", "application/json; charset=utf-8")
            setRequestProperty("Accept", "application/json")
            setRequestProperty("User-Agent", "Mozilla/5.0 (Linux; Android) AppleWebKit/537.36 Chrome/120.0.0.0 Mobile")
            cookieHelper.cookieHeader()?.let { setRequestProperty("Cookie", it) }
            authToken()?.let { t ->
                setRequestProperty("Authorization", "Bearer $t")
                setRequestProperty("X-Auth-Token", t)
            }
            authSessionId()?.let { sid ->
                setRequestProperty("X-Session-Id", sid)
            }
            doInput = true
            if (body != null && method != "GET") {
                doOutput = true
                OutputStreamWriter(outputStream, Charsets.UTF_8).use { it.write(body.toString()) }
            }
        }

        val responseCode = conn.responseCode
        val stream = if (responseCode in 200..299) conn.inputStream else conn.errorStream
        val text = BufferedReader(InputStreamReader(stream ?: conn.inputStream, Charsets.UTF_8)).use { it.readText() }
        captureSessionCookie(conn)
        conn.disconnect()
        return HttpResult(responseCode, text)
    }

    private fun captureSessionCookie(conn: HttpURLConnection) {
        val setCookies = conn.headerFields?.entries
            ?.filter { it.key.equals("Set-Cookie", ignoreCase = true) }
            ?.flatMap { it.value }
            ?: return

        for (header in setCookies) {
            val match = Regex("PHPSESSID=([^;]+)", RegexOption.IGNORE_CASE).find(header) ?: continue
            val sid = match.groupValues[1]
            if (sid.isNotBlank()) {
                SessionManager(appContext).sessionId = sid
            }
        }
    }

    private fun parseResponse(responseCode: Int, text: String): JSONObject {
        if (InfinityFreeChallenge.isChallenge(text)) {
            throw ApiException(
                responseCode,
                "Connexion bloquée par InfinityFree. Réessayez dans 5 secondes."
            )
        }

        val json = try {
            JSONObject(text)
        } catch (_: Exception) {
            throw ApiException(responseCode, "Réponse serveur invalide ($responseCode)")
        }

        if (!json.optBoolean("success", false)) {
            throw ApiException(responseCode, json.optString("message", "Erreur API"))
        }

        return json.optJSONObject("data") ?: JSONObject()
    }
}

fun JSONArray.toList(): List<JSONObject> {
    val list = mutableListOf<JSONObject>()
    for (i in 0 until length()) {
        list.add(getJSONObject(i))
    }
    return list
}
