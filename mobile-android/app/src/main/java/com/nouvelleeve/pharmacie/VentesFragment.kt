package com.nouvelleeve.pharmacie

import android.content.Intent
import android.graphics.Color
import android.os.Bundle
import android.text.Editable
import android.text.TextWatcher
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.view.inputmethod.EditorInfo
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.google.android.material.card.MaterialCardView
import com.google.android.material.tabs.TabLayout
import com.nouvelleeve.pharmacie.databinding.FragmentVentesBinding
import com.nouvelleeve.pharmacie.databinding.ItemMedicamentSearchBinding
import com.nouvelleeve.pharmacie.databinding.ItemVenteHistoriqueBinding
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.text.SimpleDateFormat
import java.util.Locale

class VentesFragment : Fragment() {

    private var _binding: FragmentVentesBinding? = null
    private val binding get() = _binding!!
    private val medicaments = mutableListOf<JSONObject>()
    private val medAdapter = MedicamentSearchAdapter { med -> selectMedicament(med) }
    private var selectedMedicament: JSONObject? = null
    private var searchJob: Job? = null
    private val historiqueAdapter = HistoriqueAdapter { venteId ->
        openRecu(venteId)
    }
    private var lastVenteId: Int? = null

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentVentesBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.tabVentes.addTab(binding.tabVentes.newTab().setText(R.string.tab_new_sale))
        binding.tabVentes.addTab(binding.tabVentes.newTab().setText(R.string.tab_history))

        binding.tabVentes.addOnTabSelectedListener(object : TabLayout.OnTabSelectedListener {
            override fun onTabSelected(tab: TabLayout.Tab) {
                showPanel(tab.position == 0)
                if (tab.position == 1) loadHistorique()
            }
            override fun onTabUnselected(tab: TabLayout.Tab?) {}
            override fun onTabReselected(tab: TabLayout.Tab?) {
                if (tab?.position == 1) loadHistorique()
            }
        })

        binding.recyclerMedicaments.layoutManager = LinearLayoutManager(requireContext())
        binding.recyclerMedicaments.adapter = medAdapter

        binding.recyclerHistorique.layoutManager = LinearLayoutManager(requireContext())
        binding.recyclerHistorique.adapter = historiqueAdapter
        binding.swipeHistorique.setOnRefreshListener { loadHistorique() }

        binding.inputSearchMed.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {}
            override fun afterTextChanged(s: Editable?) {
                scheduleSearch(s?.toString().orEmpty())
            }
        })
        binding.inputSearchMed.setOnEditorActionListener { _, actionId, _ ->
            if (actionId == EditorInfo.IME_ACTION_SEARCH || actionId == EditorInfo.IME_ACTION_DONE) {
                searchJob?.cancel()
                loadMedicaments(binding.inputSearchMed.text?.toString().orEmpty())
                true
            } else {
                false
            }
        }

        binding.btnValiderVente.setOnClickListener { submitSale() }
        binding.btnVoirRecu.setOnClickListener {
            lastVenteId?.let { openRecu(it) }
        }

        showPanel(true)
        loadMedicaments("")
    }

    private fun scheduleSearch(query: String) {
        searchJob?.cancel()
        searchJob = lifecycleScope.launch {
            delay(250)
            loadMedicaments(query)
        }
    }

    private fun showPanel(nouvelleVente: Boolean) {
        binding.panelNouvelleVente.visibility = if (nouvelleVente) View.VISIBLE else View.GONE
        binding.swipeHistorique.visibility = if (nouvelleVente) View.GONE else View.VISIBLE
    }

    private fun mainActivity(): MainActivity = requireActivity() as MainActivity

    private fun loadMedicaments(query: String) {
        lifecycleScope.launch {
            try {
                val data = withContext(Dispatchers.IO) {
                    mainActivity().api().getMedicaments(query.trim())
                }
                medicaments.clear()
                medicaments.addAll(data.optJSONArray("medicaments")?.toList().orEmpty())
                medAdapter.submit(medicaments, selectedMedicament?.optInt("id"))
                val q = query.trim()
                binding.textSearchHint.text = when {
                    medicaments.isEmpty() && q.isNotEmpty() ->
                        getString(R.string.no_med_for_search, q)
                    q.isEmpty() ->
                        getString(R.string.search_med_hint)
                    else ->
                        "${medicaments.size} résultat(s) pour « $q »"
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), e.message ?: "Erreur", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun selectMedicament(med: JSONObject) {
        selectedMedicament = med
        medAdapter.submit(medicaments, med.optInt("id"))
        binding.textSelectedMed.visibility = View.VISIBLE
        binding.textSelectedMed.text = getString(
            R.string.selected_med,
            "${med.optString("nom")} (${med.optInt("quantite_stock")} en stock)"
        )
    }

    private fun loadHistorique() {
        binding.swipeHistorique.isRefreshing = true
        lifecycleScope.launch {
            try {
                val data = withContext(Dispatchers.IO) {
                    mainActivity().api().getHistoriqueVentes(limit = 50)
                }
                historiqueAdapter.submit(data.optJSONArray("ventes")?.toList().orEmpty())
            } catch (e: Exception) {
                Toast.makeText(requireContext(), e.message ?: "Erreur", Toast.LENGTH_SHORT).show()
            } finally {
                binding.swipeHistorique.isRefreshing = false
            }
        }
    }

    private fun submitSale() {
        val med = selectedMedicament
        if (med == null) {
            Toast.makeText(requireContext(), R.string.select_med_first, Toast.LENGTH_SHORT).show()
            return
        }

        val quantite = binding.inputQuantite.text?.toString()?.toIntOrNull() ?: 0
        if (quantite <= 0) {
            Toast.makeText(requireContext(), "Quantité invalide", Toast.LENGTH_SHORT).show()
            return
        }

        val devise = if (binding.radioUsd.isChecked) "USD" else "CDF"
        val client = binding.inputClient.text?.toString().orEmpty()
        val notes = binding.inputNotes.text?.toString().orEmpty()

        binding.btnValiderVente.isEnabled = false
        binding.btnVoirRecu.visibility = View.GONE

        lifecycleScope.launch {
            try {
                val result = withContext(Dispatchers.IO) {
                    mainActivity().api().createVente(
                        med.optInt("id"),
                        quantite,
                        devise,
                        client,
                        notes
                    )
                }
                lastVenteId = result.optInt("id")
                binding.textVenteResult.text =
                    "✓ ${getString(R.string.sale_success)}\nN° ${result.optString("numero")}\nMontant : ${result.optDouble("montant_total")} ${result.optString("devise")}\n\n${getString(R.string.receipt_sync_hint)}"
                binding.btnVoirRecu.visibility = View.VISIBLE
                selectedMedicament = null
                binding.textSelectedMed.visibility = View.GONE
                loadMedicaments(binding.inputSearchMed.text?.toString().orEmpty())
            } catch (e: Exception) {
                Toast.makeText(requireContext(), e.message ?: "Erreur vente", Toast.LENGTH_LONG).show()
            } finally {
                binding.btnValiderVente.isEnabled = true
            }
        }
    }

    private fun openRecu(venteId: Int) {
        startActivity(Intent(requireContext(), RecuActivity::class.java).putExtra(RecuActivity.EXTRA_VENTE_ID, venteId))
    }

    override fun onDestroyView() {
        super.onDestroyView()
        searchJob?.cancel()
        _binding = null
    }
}

class MedicamentSearchAdapter(
    private val onSelect: (JSONObject) -> Unit
) : RecyclerView.Adapter<MedicamentSearchAdapter.Holder>() {

    private val items = mutableListOf<JSONObject>()
    private var selectedId: Int? = null

    fun submit(list: List<JSONObject>, selectedId: Int? = null) {
        items.clear()
        items.addAll(list)
        this.selectedId = selectedId
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): Holder {
        val binding = ItemMedicamentSearchBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return Holder(binding)
    }

    override fun onBindViewHolder(holder: Holder, position: Int) {
        holder.bind(items[position])
    }

    override fun getItemCount(): Int = items.size

    inner class Holder(private val binding: ItemMedicamentSearchBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(item: JSONObject) {
            binding.textNom.text = item.optString("nom")
            binding.textDetails.text =
                "Code: ${item.optString("code")} | ${item.optInt("quantite_stock")} en stock | ${item.optDouble("prix_vente").toInt()} FC"

            val selected = item.optInt("id") == selectedId
            binding.cardMedicament.setCardBackgroundColor(
                if (selected) Color.parseColor("#E8F5EE") else Color.WHITE
            )

            binding.cardMedicament.setOnClickListener { onSelect(item) }
        }
    }
}

class HistoriqueAdapter(
    private val onRecu: (Int) -> Unit
) : RecyclerView.Adapter<HistoriqueAdapter.Holder>() {

    private val items = mutableListOf<JSONObject>()
    private val dateFormat = SimpleDateFormat("dd/MM/yyyy HH:mm", Locale.FRANCE)

    fun submit(list: List<JSONObject>) {
        items.clear()
        items.addAll(list)
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): Holder {
        val binding = ItemVenteHistoriqueBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return Holder(binding)
    }

    override fun onBindViewHolder(holder: Holder, position: Int) {
        holder.bind(items[position])
    }

    override fun getItemCount(): Int = items.size

    inner class Holder(private val binding: ItemVenteHistoriqueBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(item: JSONObject) {
            binding.textNumero.text = item.optString("numero")
            binding.textDate.text = formatDate(item.optString("date_vente"))
            binding.textDetails.text = buildString {
                append(item.optString("details", "—"))
                append("\nClient: ${item.optString("client_nom", "—")}")
                append(" | Vendeur: ${item.optString("vendeur", "—")}")
            }
            val devise = item.optString("devise", "CDF")
            val montant = item.optDouble("montant_total")
            binding.textMontant.text = if (devise == "USD") {
                "$${String.format(Locale.FRANCE, "%,.2f", montant)}"
            } else {
                "${String.format(Locale.FRANCE, "%,.0f", montant)} FC"
            }
            binding.btnRecu.setOnClickListener { onRecu(item.optInt("id")) }
        }

        private fun formatDate(raw: String): String {
            return try {
                val parsed = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).parse(raw.substring(0, 19))
                if (parsed != null) dateFormat.format(parsed) else raw
            } catch (_: Exception) {
                raw
            }
        }
    }
}
