package com.nouvelleeve.pharmacie

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.nouvelleeve.pharmacie.databinding.ActivityRecuBinding
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.text.SimpleDateFormat
import java.util.Locale

class RecuActivity : AppCompatActivity() {

    private lateinit var binding: ActivityRecuBinding
    private var recuData: JSONObject? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityRecuBinding.inflate(layoutInflater)
        setContentView(binding.root)

        supportActionBar?.setDisplayHomeAsUpEnabled(true)
        title = getString(R.string.receipt_title)

        val venteId = intent.getIntExtra(EXTRA_VENTE_ID, 0)
        if (venteId <= 0) {
            finish()
            return
        }

        binding.btnPartagerRecu.setOnClickListener { sharePdf() }
        binding.btnImprimerRecu.setOnClickListener { sharePdf() }

        lifecycleScope.launch {
            try {
                val data = withContext(Dispatchers.IO) {
                    ApiClient(applicationContext, SessionManager(this@RecuActivity).token).getRecu(venteId)
                }
                recuData = data
                binding.textRecu.text = formatRecuText(data)
            } catch (e: Exception) {
                binding.textRecu.text = e.message ?: "Erreur"
                Toast.makeText(this@RecuActivity, e.message ?: "Erreur", Toast.LENGTH_LONG).show()
            }
        }
    }

    private fun sharePdf() {
        val data = recuData ?: return
        try {
            val uri = PdfExporter.exportRecu(this, data)
            val intent = Intent(Intent.ACTION_SEND).apply {
                type = "application/pdf"
                putExtra(Intent.EXTRA_STREAM, uri)
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            startActivity(Intent.createChooser(intent, getString(R.string.share_receipt)))
        } catch (e: Exception) {
            Toast.makeText(this, e.message ?: "Erreur PDF", Toast.LENGTH_SHORT).show()
        }
    }

    override fun onSupportNavigateUp(): Boolean {
        finish()
        return true
    }

    companion object {
        const val EXTRA_VENTE_ID = "vente_id"

        fun formatRecuText(data: JSONObject): String {
            val pharma = data.optJSONObject("pharmacie") ?: JSONObject()
            val vente = data.optJSONObject("vente") ?: JSONObject()
            val lignes = data.optJSONArray("lignes")
            val sb = StringBuilder()

            sb.appendLine("Pharmacie ${pharma.optString("nom")}")
            pharma.optString("tagline").takeIf { it.isNotBlank() }?.let { sb.appendLine(it) }
            pharma.optString("adresse").takeIf { it.isNotBlank() }?.let { sb.appendLine(it) }
            pharma.optString("telephone").takeIf { it.isNotBlank() }?.let { sb.appendLine(it) }
            sb.appendLine()
            sb.appendLine("RECU DE VENTE")
            sb.appendLine("CAISSE")
            sb.appendLine("----------------------------------------")
            sb.appendLine("REFERENCE : ${vente.optString("numero")}")
            sb.appendLine("DATE : ${formatRecuDate(vente.optString("date_vente"))}")
            sb.appendLine("VENDEUR : ${vente.optString("vendeur")}")
            sb.appendLine("CLIENT : ${vente.optString("client_nom", "Client comptant")}")
            sb.appendLine("----------------------------------------")

            val devise = vente.optString("devise", "CDF")
            val montant = vente.optDouble("montant_total")
            val montantStr = if (devise == "USD") {
                "$${String.format(Locale.FRANCE, "%,.2f", montant)}"
            } else {
                "${String.format(Locale.FRANCE, "%,.0f", montant)} FC"
            }

            sb.appendLine()
            sb.appendLine("Montant reçu : $montantStr")
            vente.optString("montant_lettres").takeIf { it.isNotBlank() }?.let {
                sb.appendLine("($it)")
            }
            sb.appendLine()

            if (lignes != null) {
                for (i in 0 until lignes.length()) {
                    val l = lignes.getJSONObject(i)
                    sb.appendLine("- ${l.optString("nom")} x${l.optInt("quantite")}")
                }
            }

            sb.appendLine("----------------------------------------")
            sb.appendLine("MONTANT : $montantStr")
            vente.optString("equivalent").takeIf { it.isNotBlank() }?.let {
                sb.appendLine("Équivalent : $it")
            }
            vente.optString("notes").takeIf { it.isNotBlank() }?.let {
                sb.appendLine("Note : $it")
            }
            sb.appendLine("----------------------------------------")
            sb.appendLine("En cas de réclamation, présentez ce reçu.")
            sb.appendLine("Merci pour votre confiance !")

            return sb.toString()
        }

        private fun formatRecuDate(raw: String): String {
            return try {
                val parsed = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).parse(raw.substring(0, 19))
                SimpleDateFormat("dd/MM/yyyy HH:mm", Locale.FRANCE).format(parsed!!)
            } catch (_: Exception) {
                raw
            }
        }
    }
}
