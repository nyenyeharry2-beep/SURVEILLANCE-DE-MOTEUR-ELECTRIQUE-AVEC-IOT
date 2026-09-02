package com.nouvelleeve.pharmacie

import android.content.Context
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
    private val token: String? = null
) {
    private val cookieHelper = InfinityFreeCookieHelper(appContext)

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

    suspend fun logout(): JSONObject = execute("POST", "${ApiConfig.BASE}/logout.php", JSONObject())

    suspend fun getMedicaments(query: String = ""): JSONObject {
        val url = if (query.isBlank()) {
            "${ApiConfig.BASE}/medicaments.php"
        } else {
            "${ApiConfig.BASE}/medicaments.php?q=${encode(query)}"
        }
        return execute("GET", url, null)
    }

    suspend fun getStock(query: String = ""): JSONObject {
        val url = if (query.isBlank()) {
            "${ApiConfig.BASE}/stock.php"
        } else {
            "${ApiConfig.BASE}/stock.php?q=${encode(query)}"
        }
        return execute("GET", url, null)
    }

    suspend fun createVente(medicamentId: Int, quantite: Int, devise: String, clientNom: String): JSONObject {
        return execute(
            "POST",
            "${ApiConfig.BASE}/ventes.php",
            JSONObject().apply {
                put("medicament_id", medicamentId)
                put("quantite", quantite)
                put("devise", devise)
                put("client_nom", clientNom)
            }
        )
    }

    suspend fun rapportJour(date: String): JSONObject =
        execute("GET", "${ApiConfig.BASE}/rapports.php?type=jour&date=$date", null)

    suspend fun rapportMois(annee: Int, mois: Int): JSONObject =
        execute("GET", "${ApiConfig.BASE}/rapports.php?type=mois&annee=$annee&mois=$mois", null)

    suspend fun getAlertes(type: String = "all"): JSONObject =
        execute("GET", "${ApiConfig.BASE}/alertes.php?type=${encode(type)}", null)

    private fun encode(value: String): String =
        java.net.URLEncoder.encode(value, Charsets.UTF_8.name())

    private suspend fun execute(
        method: String,
        urlString: String,
        body: JSONObject?,
        auth: Boolean = true
    ): JSONObject {
        cookieHelper.ensureCookie()
        var result = rawRequest(method, urlString, body, auth)

        if (InfinityFreeChallenge.isChallenge(result.body)) {
            if (!InfinityFreeChallenge.solveAndStore(result.body)) {
                cookieHelper.ensureCookie()
            }
            result = rawRequest(method, urlString, body, auth)
        }

        if (InfinityFreeChallenge.isChallenge(result.body)) {
            if (InfinityFreeChallenge.solveAndStore(result.body)) {
                result = rawRequest(method, urlString, body, auth)
            }
        }

        return parseResponse(result.code, result.body)
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
            if (auth && !token.isNullOrBlank()) {
                setRequestProperty("Authorization", "Bearer $token")
            }
            doInput = true
            if (body != null) {
                doOutput = true
                OutputStreamWriter(outputStream, Charsets.UTF_8).use { it.write(body.toString()) }
            }
        }

        val responseCode = conn.responseCode
        val stream = if (responseCode in 200..299) conn.inputStream else conn.errorStream
        val text = BufferedReader(InputStreamReader(stream ?: conn.inputStream, Charsets.UTF_8)).use { it.readText() }
        conn.disconnect()
        return HttpResult(responseCode, text)
    }

    private fun parseResponse(responseCode: Int, text: String): JSONObject {
        if (InfinityFreeChallenge.isChallenge(text)) {
            throw ApiException(
                responseCode,
                "Connexion bloquée par InfinityFree. Installez l'APK v1.3.1 et réessayez."
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
