package com.nouvelleeve.pharmacie

import android.webkit.CookieManager
import java.util.regex.Pattern
import javax.crypto.Cipher
import javax.crypto.spec.IvParameterSpec
import javax.crypto.spec.SecretKeySpec

object InfinityFreeChallenge {

    private val numberPattern = Pattern.compile("""toNumbers\("([0-9a-fA-F]+)"\)""")

    fun isChallenge(body: String): Boolean {
        return body.contains("aes.js") || body.contains("slowAES") || body.contains("__test")
    }

    fun solveAndStore(body: String, siteUrl: String = "https://mapharmaciepk.xo.je"): Boolean {
        val values = extractHexValues(body)
        if (values.size < 3) return false

        return try {
            val cookieValue = decryptToHex(values[0], values[1], values[2])
            val cookieManager = CookieManager.getInstance()
            cookieManager.setAcceptCookie(true)
            cookieManager.setCookie(siteUrl, "__test=$cookieValue; path=/")
            cookieManager.flush()
            true
        } catch (_: Exception) {
            false
        }
    }

    private fun extractHexValues(html: String): List<String> {
        val matcher = numberPattern.matcher(html)
        val values = mutableListOf<String>()
        while (matcher.find() && values.size < 3) {
            values.add(matcher.group(1) ?: continue)
        }
        return values
    }

    private fun decryptToHex(keyHex: String, ivHex: String, cipherHex: String): String {
        val key = hexToBytes(keyHex)
        val iv = hexToBytes(ivHex)
        val ciphertext = hexToBytes(cipherHex)

        val cipher = Cipher.getInstance("AES/CBC/NoPadding")
        cipher.init(Cipher.DECRYPT_MODE, SecretKeySpec(key, "AES"), IvParameterSpec(iv))
        val decrypted = cipher.doFinal(ciphertext)
        return decrypted.joinToString("") { byte -> "%02x".format(byte) }
    }

    private fun hexToBytes(hex: String): ByteArray {
        val clean = hex.trim()
        return ByteArray(clean.length / 2) { i ->
            clean.substring(i * 2, i * 2 + 2).toInt(16).toByte()
        }
    }
}
