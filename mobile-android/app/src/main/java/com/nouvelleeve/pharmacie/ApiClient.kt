package com.nouvelleeve.pharmacie

import org.json.JSONArray
import org.json.JSONObject
import java.io.BufferedReader
import java.io.InputStreamReader
import java.io.OutputStreamWriter
import java.net.HttpURLConnection
import java.net.URL
import java.net.URLEncoder

class ApiException(val code: Int, message: String) : Exception(message)

class ApiClient(private val baseUrl: String, private val token: String? = null) {

    fun login(email: String, password: String): JSONObject {
        return post("auth/login", JSONObject().apply {
            put("email", email)
            put("password", password)
        }, auth = false)
    }

    fun logout(): JSONObject = post("auth/logout", JSONObject())

    fun getMedicaments(query: String = ""): JSONObject {
        val q = if (query.isBlank()) "" else "?q=" + URLEncoder.encode(query, "UTF-8")
        return get("medicaments$q")
    }

    fun getStock(query: String = ""): JSONObject {
        val q = if (query.isBlank()) "" else "?q=" + URLEncoder.encode(query, "UTF-8")
        return get("stock$q")
    }

    fun createVente(
        medicamentId: Int,
        quantite: Int,
        devise: String,
        clientNom: String
    ): JSONObject {
        return post("ventes", JSONObject().apply {
            put("medicament_id", medicamentId)
            put("quantite", quantite)
            put("devise", devise)
            put("client_nom", clientNom)
        })
    }

    fun rapportJour(date: String): JSONObject = get("rapports/jour?date=$date")

    fun rapportMois(annee: Int, mois: Int): JSONObject =
        get("rapports/mois?annee=$annee&mois=$mois")

    fun getAlertes(type: String = "all"): JSONObject = get("alertes?type=$type")

    private fun get(path: String): JSONObject = request("GET", path, null)

    private fun post(path: String, body: JSONObject, auth: Boolean = true): JSONObject =
        request("POST", path, body, auth)

    private fun buildUrl(path: String): URL {
        val trimmed = path.trimStart('/')
        val qIndex = trimmed.indexOf('?')
        val route = if (qIndex >= 0) trimmed.substring(0, qIndex) else trimmed
        val extraQuery = if (qIndex >= 0) trimmed.substring(qIndex + 1) else ""

        val base = baseUrl.trimEnd('/')
        val apiBase = when {
            base.endsWith("index.php") -> base
            base.endsWith("/api") -> "$base/index.php"
            else -> "$base/index.php"
        }

        val urlBuilder = StringBuilder(apiBase)
            .append("?route=")
            .append(URLEncoder.encode(route, "UTF-8"))

        if (extraQuery.isNotEmpty()) {
            urlBuilder.append('&').append(extraQuery)
        }

        return URL(urlBuilder.toString())
    }

    private fun request(method: String, path: String, body: JSONObject?, auth: Boolean = true): JSONObject {
        val url = buildUrl(path)
        val conn = (url.openConnection() as HttpURLConnection).apply {
            requestMethod = method
            connectTimeout = 25000
            readTimeout = 25000
            setRequestProperty("Content-Type", "application/json; charset=utf-8")
            setRequestProperty("Accept", "application/json")
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
            val message = when {
                text.isBlank() -> "Serveur injoignable. Vérifiez Internet."
                text.trimStart().startsWith("<") -> "API non installée. Mettez à jour le site (dossier api/) sur InfinityFree."
                else -> "Réponse serveur invalide"
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
