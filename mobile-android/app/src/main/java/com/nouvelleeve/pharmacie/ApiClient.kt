package com.nouvelleeve.pharmacie

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

class ApiClient(private val token: String? = null) {

    fun ping(): JSONObject = get("${ApiConfig.BASE}/ping.php")

    fun login(email: String, password: String): JSONObject {
        return post(
            "${ApiConfig.BASE}/login.php",
            JSONObject().apply {
                put("email", email)
                put("password", password)
            },
            auth = false
        )
    }

    fun logout(): JSONObject = post("${ApiConfig.BASE}/logout.php", JSONObject())

    fun getMedicaments(query: String = ""): JSONObject {
        val url = if (query.isBlank()) {
            "${ApiConfig.BASE}/medicaments.php"
        } else {
            "${ApiConfig.BASE}/medicaments.php?q=${encode(query)}"
        }
        return get(url)
    }

    fun getStock(query: String = ""): JSONObject {
        val url = if (query.isBlank()) {
            "${ApiConfig.BASE}/stock.php"
        } else {
            "${ApiConfig.BASE}/stock.php?q=${encode(query)}"
        }
        return get(url)
    }

    fun createVente(medicamentId: Int, quantite: Int, devise: String, clientNom: String): JSONObject {
        return post(
            "${ApiConfig.BASE}/ventes.php",
            JSONObject().apply {
                put("medicament_id", medicamentId)
                put("quantite", quantite)
                put("devise", devise)
                put("client_nom", clientNom)
            }
        )
    }

    fun rapportJour(date: String): JSONObject =
        get("${ApiConfig.BASE}/rapports.php?type=jour&date=$date")

    fun rapportMois(annee: Int, mois: Int): JSONObject =
        get("${ApiConfig.BASE}/rapports.php?type=mois&annee=$annee&mois=$mois")

    fun getAlertes(type: String = "all"): JSONObject =
        get("${ApiConfig.BASE}/alertes.php?type=${encode(type)}")

    private fun encode(value: String): String =
        java.net.URLEncoder.encode(value, Charsets.UTF_8.name())

    private fun get(url: String): JSONObject = request("GET", url, null)

    private fun post(url: String, body: JSONObject, auth: Boolean = true): JSONObject =
        request("POST", url, body, auth)

    private fun request(method: String, urlString: String, body: JSONObject?, auth: Boolean = true): JSONObject {
        val conn = (URL(urlString).openConnection() as HttpURLConnection).apply {
            requestMethod = method
            connectTimeout = 30000
            readTimeout = 30000
            setRequestProperty("Content-Type", "application/json; charset=utf-8")
            setRequestProperty("Accept", "application/json")
            setRequestProperty("User-Agent", "NouvelleEve-Android/1.2.2")
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

        val json = try {
            JSONObject(text)
        } catch (_: Exception) {
            val preview = text.replace("\n", " ").take(120)
            val message = when {
                text.isBlank() -> "Serveur injoignable. Vérifiez Internet."
                text.trimStart().startsWith("<") -> "Le serveur a renvoyé une page web au lieu de l'API. Vérifiez que le dossier api/ est bien dans htdocs."
                else -> "Réponse invalide ($responseCode): $preview"
            }
            throw ApiException(responseCode, message)
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
