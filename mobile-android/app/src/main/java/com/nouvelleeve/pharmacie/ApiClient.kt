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

    private fun request(method: String, path: String, body: JSONObject?, auth: Boolean = true): JSONObject {
        val url = URL(baseUrl.trimEnd('/') + "/" + path.trimStart('/'))
        val conn = (url.openConnection() as HttpURLConnection).apply {
            requestMethod = method
            connectTimeout = 20000
            readTimeout = 20000
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
        val text = BufferedReader(InputStreamReader(stream, Charsets.UTF_8)).use { it.readText() }
        conn.disconnect()

        val json = try {
            JSONObject(text)
        } catch (_: Exception) {
            throw ApiException(responseCode, "Réponse serveur invalide")
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
