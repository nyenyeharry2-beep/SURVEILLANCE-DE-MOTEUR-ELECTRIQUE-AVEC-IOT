package com.nouvelleeve.pharmacie

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import com.nouvelleeve.pharmacie.databinding.FragmentVentesBinding
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject

class VentesFragment : Fragment() {

    private var _binding: FragmentVentesBinding? = null
    private val binding get() = _binding!!
    private val medicaments = mutableListOf<JSONObject>()

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentVentesBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        loadMedicaments("")

        binding.inputSearchMed.setOnEditorActionListener { _, _, _ ->
            loadMedicaments(binding.inputSearchMed.text?.toString().orEmpty())
            true
        }

        binding.btnValiderVente.setOnClickListener { submitSale() }
    }

    private fun mainActivity(): MainActivity = requireActivity() as MainActivity

    private fun loadMedicaments(query: String) {
        lifecycleScope.launch {
            try {
                val data = withContext(Dispatchers.IO) {
                    mainActivity().api().getMedicaments(query)
                }
                medicaments.clear()
                medicaments.addAll(data.optJSONArray("medicaments")?.toList().orEmpty())
                updateSpinner()
            } catch (e: Exception) {
                Toast.makeText(requireContext(), e.message ?: "Erreur", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun updateSpinner() {
        val labels = medicaments.map {
            "${it.optString("nom")} (${it.optInt("quantite_stock")} en stock)"
        }
        binding.spinnerMedicament.adapter = ArrayAdapter(
            requireContext(),
            android.R.layout.simple_spinner_dropdown_item,
            labels.ifEmpty { listOf("Aucun médicament") }
        )
    }

    private fun submitSale() {
        if (medicaments.isEmpty()) {
            Toast.makeText(requireContext(), "Aucun médicament disponible", Toast.LENGTH_SHORT).show()
            return
        }

        val index = binding.spinnerMedicament.selectedItemPosition
        if (index < 0 || index >= medicaments.size) return

        val med = medicaments[index]
        val quantite = binding.inputQuantite.text?.toString()?.toIntOrNull() ?: 0
        if (quantite <= 0) {
            Toast.makeText(requireContext(), "Quantité invalide", Toast.LENGTH_SHORT).show()
            return
        }

        val devise = if (binding.radioUsd.isChecked) "USD" else "CDF"
        val client = binding.inputClient.text?.toString().orEmpty()

        binding.btnValiderVente.isEnabled = false

        lifecycleScope.launch {
            try {
                val result = withContext(Dispatchers.IO) {
                    mainActivity().api().createVente(med.optInt("id"), quantite, devise, client)
                }
                binding.textVenteResult.text =
                    "✓ ${getString(R.string.sale_success)}\nN° ${result.optString("numero")}\nMontant : ${result.optDouble("montant_total")} ${result.optString("devise")}"
                loadMedicaments(binding.inputSearchMed.text?.toString().orEmpty())
            } catch (e: Exception) {
                Toast.makeText(requireContext(), e.message ?: "Erreur vente", Toast.LENGTH_LONG).show()
            } finally {
                binding.btnValiderVente.isEnabled = true
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
