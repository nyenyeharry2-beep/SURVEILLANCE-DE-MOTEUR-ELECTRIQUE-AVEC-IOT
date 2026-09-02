package com.nouvelleeve.pharmacie

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
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

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentRapportsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
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
        currentTitle = "Rapport du jour — $date"
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
            sb.append("Période: ${periode.optString("debut")} → ${periode.optString("fin")}\n\n")
        }

        val totaux = data.optJSONObject("totaux")
        if (totaux != null) {
            sb.append("═══ TOTAUX ═══\n")
            sb.append("Ventes: ${totaux.optInt("nb_ventes")}\n")
            sb.append("CDF: ${totaux.optDouble("cdf_brut")} FC\n")
            sb.append("USD: $${totaux.optDouble("usd_brut")}\n")
            sb.append("Converti CDF: ${totaux.optDouble("cdf_converti")} FC\n")
            sb.append("Converti USD: $${totaux.optDouble("usd_converti")}\n\n")
        }

        val ventes = data.optJSONArray("ventes")
        if (ventes != null && ventes.length() > 0) {
            sb.append("═══ VENTES ═══\n")
            for (i in 0 until ventes.length()) {
                val v = ventes.getJSONObject(i)
                sb.append("${v.optString("numero")}\n")
                sb.append("  ${v.optString("date_vente")} | ${v.optDouble("montant_total")} ${v.optString("devise")}\n")
                sb.append("  ${v.optString("client_nom", "Client comptant")}\n\n")
            }
        } else {
            sb.append("Aucune vente sur cette période.")
        }

        return sb.toString()
    }

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
