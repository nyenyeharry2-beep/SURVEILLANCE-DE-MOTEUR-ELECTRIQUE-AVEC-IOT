package com.nouvelleeve.pharmacie

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.core.content.FileProvider
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import com.nouvelleeve.pharmacie.databinding.FragmentRapportsBinding
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale

class RapportsFragment : Fragment() {

    private var _binding: FragmentRapportsBinding? = null
    private val binding get() = _binding!!
    private var currentReport: JSONObject? = null
    private var currentTitle: String = ""

    override fun onCreateView(
        inflater: android.view.LayoutInflater,
        container: android.view.ViewGroup?,
        savedInstanceState: Bundle?
    ): android.view.View {
        _binding = FragmentRapportsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: android.view.View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.btnRapportJour.setOnClickListener { loadJour() }
        binding.btnRapportMois.setOnClickListener { loadMois() }
        binding.btnExportPdf.setOnClickListener { exportPdf() }
        binding.swipeRefresh.setOnRefreshListener { loadJour() }

        loadJour()
    }

    private fun mainActivity(): MainActivity = requireActivity() as MainActivity

    private fun loadJour() {
        val date = SimpleDateFormat("yyyy-MM-dd", Locale.US).format(Calendar.getInstance().time)
        currentTitle = getString(R.string.end_of_day) + " — $date"
        loadReport {
            mainActivity().api().rapportJour(date)
        }
    }

    private fun loadMois() {
        val cal = Calendar.getInstance()
        val annee = cal.get(Calendar.YEAR)
        val mois = cal.get(Calendar.MONTH) + 1
        currentTitle = "Rapport du mois — $mois/$annee"
        loadReport {
            mainActivity().api().rapportMois(annee, mois)
        }
    }

    private fun loadReport(fetch: suspend () -> JSONObject) {
        binding.swipeRefresh.isRefreshing = true
        lifecycleScope.launch {
            try {
                val data = withContext(Dispatchers.IO) { fetch() }
                currentReport = data
                binding.textRapport.text = formatReport(data)
            } catch (e: Exception) {
                binding.textRapport.text = e.message ?: "Erreur"
            } finally {
                binding.swipeRefresh.isRefreshing = false
            }
        }
    }

    private fun formatReport(data: JSONObject): String {
        val sb = StringBuilder()
        val periode = data.optJSONObject("periode")
        if (periode != null) {
            sb.append("Période: ${periode.optString("debut")}")
            if (periode.optString("debut") != periode.optString("fin")) {
                sb.append(" → ${periode.optString("fin")}")
            }
            sb.append("\n\n")
        }

        data.optDouble("taux_usd_cdf").takeIf { it > 0 }?.let {
            sb.append("Taux: 1 USD = ${String.format(Locale.FRANCE, "%,.0f", it)} FC\n\n")
        }

        val totaux = data.optJSONObject("totaux")
        if (totaux != null) {
            sb.append("═══ TOTAUX VENTES ═══\n")
            sb.append("Nombre: ${totaux.optInt("nb_ventes")}\n")
            sb.append("CDF direct: ${formatNum(totaux.optDouble("cdf_brut"))} FC\n")
            sb.append("USD direct: $${formatNum(totaux.optDouble("usd_brut"))}\n")
            sb.append("TOTAL CDF: ${formatNum(totaux.optDouble("cdf_converti"))} FC\n")
            sb.append("TOTAL USD: $${formatNum(totaux.optDouble("usd_converti"))}\n\n")
        }

        val parDevise = data.optJSONArray("par_devise")
        if (parDevise != null && parDevise.length() > 0) {
            sb.append("═══ PAR DEVISE ═══\n")
            for (i in 0 until parDevise.length()) {
                val row = parDevise.getJSONObject(i)
                sb.append("${row.optString("devise")}: ${row.optInt("nb_ventes")} ventes | ")
                sb.append("${formatNum(row.optDouble("total"))}\n")
                sb.append("  → ${formatNum(row.optDouble("equivalent_cdf"))} FC / $${formatNum(row.optDouble("equivalent_usd"))}\n")
            }
            sb.append("\n")
        }

        val ventes = data.optJSONArray("ventes")
        if (ventes != null && ventes.length() > 0) {
            sb.append("═══ DÉTAIL DES VENTES ═══\n")
            for (i in 0 until ventes.length()) {
                val v = ventes.getJSONObject(i)
                val devise = v.optString("devise", "CDF")
                val montant = v.optDouble("montant_total")
                val montantStr = if (devise == "USD") "$${formatNum(montant)}" else "${formatNum(montant)} FC"
                sb.append("${v.optString("numero")}\n")
                sb.append("  ${formatDate(v.optString("date_vente"))} | $montantStr\n")
                sb.append("  ${v.optString("details", "—")}\n")
                sb.append("  Client: ${v.optString("client_nom", "—")} | Vendeur: ${v.optString("vendeur")}\n")
                sb.append("  Équiv: ${formatNum(v.optDouble("montant_cdf"))} FC / $${formatNum(v.optDouble("montant_usd"))}\n\n")
            }
        } else {
            sb.append(getString(R.string.no_sales))
        }

        val caisse = data.optJSONObject("caisse")
        if (caisse != null) {
            sb.append("\n═══ ENTRÉES / SORTIES CAISSE ═══\n")
            sb.append("Entrées: ${formatNum(caisse.optDouble("entrees_cdf"))} FC\n")
            sb.append("Sorties: ${formatNum(caisse.optDouble("sorties_cdf"))} FC\n")
            sb.append("Solde: ${formatNum(caisse.optDouble("solde_cdf"))} FC\n")
        }

        val mouvements = data.optJSONArray("mouvements_caisse")
        if (mouvements != null && mouvements.length() > 0) {
            sb.append("\n--- Motifs des mouvements ---\n")
            for (i in 0 until mouvements.length()) {
                val m = mouvements.getJSONObject(i)
                val sign = if (m.optString("type") == "entree") "+" else "-"
                val devise = m.optString("devise", "CDF")
                val montant = m.optDouble("montant")
                val montantStr = if (devise == "USD") "$${formatNum(montant)}" else "${formatNum(montant)} FC"
                sb.append("$sign $montantStr | ${m.optString("motif")}\n")
                sb.append("  ${formatDate(m.optString("date_mouvement"))} | ${m.optString("vendeur")}\n")
            }
        }

        return sb.toString()
    }

    private fun formatDate(raw: String): String {
        return try {
            val parsed = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).parse(raw.substring(0, 19))
            SimpleDateFormat("dd/MM/yyyy HH:mm", Locale.FRANCE).format(parsed!!)
        } catch (_: Exception) {
            raw
        }
    }

    private fun formatNum(value: Double): String =
        String.format(Locale.FRANCE, "%,.2f", value)

    private fun exportPdf() {
        val report = currentReport
        if (report == null) {
            Toast.makeText(requireContext(), R.string.no_data, Toast.LENGTH_SHORT).show()
            return
        }

        try {
            val uri = PdfExporter.exportRapport(requireContext(), currentTitle, report)
            val intent = Intent(Intent.ACTION_SEND).apply {
                type = "application/pdf"
                putExtra(Intent.EXTRA_STREAM, uri)
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            startActivity(Intent.createChooser(intent, getString(R.string.share_pdf)))
            Toast.makeText(requireContext(), R.string.pdf_saved, Toast.LENGTH_SHORT).show()
        } catch (e: Exception) {
            Toast.makeText(requireContext(), e.message ?: "Erreur PDF", Toast.LENGTH_SHORT).show()
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
