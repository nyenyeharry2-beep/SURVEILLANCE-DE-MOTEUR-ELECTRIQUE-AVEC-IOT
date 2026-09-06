package com.nouvelleeve.pharmacie

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import android.widget.TextView
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.nouvelleeve.pharmacie.databinding.FragmentAlertesBinding
import com.nouvelleeve.pharmacie.databinding.ItemAlerteBinding
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject

class AlertesFragment : Fragment() {

    private var _binding: FragmentAlertesBinding? = null
    private val binding get() = _binding!!
    private val adapter = AlertesAdapter()
    private val types = listOf(
        "all" to "Toutes les alertes",
        "stock" to "Stock faible",
        "ecouler" to "À écouler (expiration)",
        "expiration" to "Expirés"
    )

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentAlertesBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.recyclerAlertes.layoutManager = LinearLayoutManager(requireContext())
        binding.recyclerAlertes.adapter = adapter

        binding.spinnerTypeAlerte.adapter = ArrayAdapter(
            requireContext(),
            android.R.layout.simple_spinner_dropdown_item,
            types.map { it.second }
        )

        binding.spinnerTypeAlerte.setSelection(0)
        binding.swipeRefresh.setOnRefreshListener { loadAlertes() }
        binding.spinnerTypeAlerte.onItemSelectedListener = object : android.widget.AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: android.widget.AdapterView<*>?, view: View?, position: Int, id: Long) {
                loadAlertes()
            }
            override fun onNothingSelected(parent: android.widget.AdapterView<*>?) {}
        }

        loadAlertes()
    }

    private fun mainActivity(): MainActivity = requireActivity() as MainActivity

    private fun loadAlertes() {
        val type = types[binding.spinnerTypeAlerte.selectedItemPosition].first
        binding.swipeRefresh.isRefreshing = true

        lifecycleScope.launch {
            try {
                val data = withContext(Dispatchers.IO) {
                    mainActivity().api().getAlertes(type)
                }
                adapter.submit(data.optJSONArray("alertes")?.toList().orEmpty())
            } catch (e: Exception) {
                Toast.makeText(requireContext(), e.message ?: "Erreur", Toast.LENGTH_SHORT).show()
            } finally {
                binding.swipeRefresh.isRefreshing = false
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}

class AlertesAdapter : RecyclerView.Adapter<AlertesAdapter.Holder>() {

    private val items = mutableListOf<JSONObject>()

    fun submit(list: List<JSONObject>) {
        items.clear()
        items.addAll(list)
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): Holder {
        val binding = ItemAlerteBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return Holder(binding)
    }

    override fun onBindViewHolder(holder: Holder, position: Int) {
        holder.bind(items[position])
    }

    override fun getItemCount(): Int = items.size

    class Holder(private val binding: ItemAlerteBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(item: JSONObject) {
            binding.textNom.text = item.optString("nom")
            val stock = item.optInt("quantite_stock")
            val seuil = item.optInt("seuil_alerte")
            val exp = item.optString("date_expiration", "—")
            val statut = item.optString("statut_label", "—")
            binding.textDetails.text =
                "Code: ${item.optString("code")} | Stock: $stock (seuil $seuil)\nExpiration: $exp | $statut"
        }
    }
}
