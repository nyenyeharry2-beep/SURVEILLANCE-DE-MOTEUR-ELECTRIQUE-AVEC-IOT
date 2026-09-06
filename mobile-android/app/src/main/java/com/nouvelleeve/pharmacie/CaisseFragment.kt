package com.nouvelleeve.pharmacie

import android.graphics.Color
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.nouvelleeve.pharmacie.databinding.FragmentCaisseBinding
import com.nouvelleeve.pharmacie.databinding.ItemCaisseMouvementBinding
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.text.SimpleDateFormat
import java.util.Locale

class CaisseFragment : Fragment() {

    private var _binding: FragmentCaisseBinding? = null
    private val binding get() = _binding!!
    private val adapter = CaisseMouvementAdapter()

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentCaisseBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.recyclerMouvements.layoutManager = LinearLayoutManager(requireContext())
        binding.recyclerMouvements.adapter = adapter
        binding.swipeRefresh.setOnRefreshListener { loadData() }
        binding.btnEnregistrer.setOnClickListener { submitMovement() }

        loadData()
    }

    private fun mainActivity(): MainActivity = requireActivity() as MainActivity

    private fun loadData() {
        binding.swipeRefresh.isRefreshing = true
        lifecycleScope.launch {
            try {
                val data = withContext(Dispatchers.IO) {
                    mainActivity().api().getCaisse()
                }
                val resume = data.optJSONObject("resume")
                binding.textResumeCaisse.text = formatResume(resume)
                adapter.submit(data.optJSONArray("mouvements")?.toList().orEmpty())
            } catch (e: Exception) {
                Toast.makeText(requireContext(), e.message ?: "Erreur", Toast.LENGTH_SHORT).show()
            } finally {
                binding.swipeRefresh.isRefreshing = false
            }
        }
    }

    private fun formatResume(resume: JSONObject?): String {
        if (resume == null) return getString(R.string.loading)
        return buildString {
            append("Entrées : ${formatFc(resume.optDouble("entrees_cdf"))} FC\n")
            append("Sorties : ${formatFc(resume.optDouble("sorties_cdf"))} FC\n")
            append("Solde caisse : ${formatFc(resume.optDouble("solde_cdf"))} FC")
        }
    }

    private fun formatFc(value: Double): String =
        String.format(Locale.FRANCE, "%,.0f", value)

    private fun submitMovement() {
        val montant = binding.inputMontant.text?.toString()?.replace(',', '.')?.toDoubleOrNull() ?: 0.0
        val motif = binding.inputMotif.text?.toString()?.trim().orEmpty()
        val type = if (binding.radioSortie.isChecked) "sortie" else "entree"
        val devise = if (binding.radioUsd.isChecked) "USD" else "CDF"

        if (montant <= 0) {
            Toast.makeText(requireContext(), R.string.amount_required, Toast.LENGTH_SHORT).show()
            return
        }
        if (motif.isBlank()) {
            Toast.makeText(requireContext(), R.string.motif_required, Toast.LENGTH_SHORT).show()
            return
        }

        binding.btnEnregistrer.isEnabled = false
        lifecycleScope.launch {
            try {
                withContext(Dispatchers.IO) {
                    mainActivity().api().createMouvementCaisse(type, montant, devise, motif)
                }
                binding.inputMontant.text?.clear()
                binding.inputMotif.text?.clear()
                Toast.makeText(requireContext(), R.string.movement_saved, Toast.LENGTH_SHORT).show()
                loadData()
            } catch (e: Exception) {
                Toast.makeText(requireContext(), e.message ?: "Erreur", Toast.LENGTH_LONG).show()
            } finally {
                binding.btnEnregistrer.isEnabled = true
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}

class CaisseMouvementAdapter : RecyclerView.Adapter<CaisseMouvementAdapter.Holder>() {

    private val items = mutableListOf<JSONObject>()

    fun submit(list: List<JSONObject>) {
        items.clear()
        items.addAll(list)
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): Holder {
        val binding = ItemCaisseMouvementBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return Holder(binding)
    }

    override fun onBindViewHolder(holder: Holder, position: Int) {
        holder.bind(items[position])
    }

    override fun getItemCount(): Int = items.size

    class Holder(private val binding: ItemCaisseMouvementBinding) : RecyclerView.ViewHolder(binding.root) {
        private val timeFormat = SimpleDateFormat("HH:mm", Locale.FRANCE)

        fun bind(item: JSONObject) {
            val isEntree = item.optString("type") == "entree"
            binding.textType.text = if (isEntree) "ENTRÉE +" else "SORTIE -"
            binding.textType.setTextColor(
                Color.parseColor(if (isEntree) "#2A7345" else "#C0392B")
            )

            val devise = item.optString("devise", "CDF")
            val montant = item.optDouble("montant")
            binding.textMontant.text = if (devise == "USD") {
                "$${String.format(Locale.FRANCE, "%,.2f", montant)}"
            } else {
                "${String.format(Locale.FRANCE, "%,.0f", montant)} FC"
            }
            binding.textMontant.setTextColor(binding.textType.currentTextColor)

            binding.textMotif.text = item.optString("motif")
            binding.textMeta.text = buildString {
                append(formatTime(item.optString("date_mouvement")))
                append(" | ")
                append(item.optString("vendeur"))
            }
        }

        private fun formatTime(raw: String): String {
            return try {
                val parsed = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).parse(raw.substring(0, 19))
                if (parsed != null) timeFormat.format(parsed) else raw
            } catch (_: Exception) {
                raw
            }
        }
    }
}
