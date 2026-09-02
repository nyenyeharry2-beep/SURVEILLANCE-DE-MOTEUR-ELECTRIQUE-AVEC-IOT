package com.nouvelleeve.pharmacie

import android.graphics.Color
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.view.inputmethod.EditorInfo
import android.widget.TextView
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.nouvelleeve.pharmacie.databinding.FragmentStockBinding
import com.nouvelleeve.pharmacie.databinding.ItemStockBinding
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject

class StockFragment : Fragment() {

    private var _binding: FragmentStockBinding? = null
    private val binding get() = _binding!!
    private val adapter = StockAdapter()

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentStockBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.recyclerStock.layoutManager = LinearLayoutManager(requireContext())
        binding.recyclerStock.adapter = adapter

        binding.swipeRefresh.setOnRefreshListener { loadStock() }

        binding.inputSearchStock.setOnEditorActionListener { _, actionId, _ ->
            if (actionId == EditorInfo.IME_ACTION_SEARCH || actionId == EditorInfo.IME_ACTION_DONE) {
                loadStock(binding.inputSearchStock.text?.toString().orEmpty())
                true
            } else {
                false
            }
        }

        loadStock()
    }

    private fun mainActivity(): MainActivity = requireActivity() as MainActivity

    private fun loadStock(query: String = binding.inputSearchStock.text?.toString().orEmpty()) {
        binding.swipeRefresh.isRefreshing = true
        lifecycleScope.launch {
            try {
                val data = withContext(Dispatchers.IO) {
                    mainActivity().api().getStock(query)
                }
                adapter.submit(data.optJSONArray("stock")?.toList().orEmpty())
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

class StockAdapter : RecyclerView.Adapter<StockAdapter.Holder>() {

    private val items = mutableListOf<JSONObject>()

    fun submit(list: List<JSONObject>) {
        items.clear()
        items.addAll(list)
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): Holder {
        val binding = ItemStockBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return Holder(binding)
    }

    override fun onBindViewHolder(holder: Holder, position: Int) {
        holder.bind(items[position])
    }

    override fun getItemCount(): Int = items.size

    class Holder(private val binding: ItemStockBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(item: JSONObject) {
            binding.textNom.text = item.optString("nom")
            val code = item.optString("code")
            val categorie = item.optString("categorie", "—")
            val prix = item.optDouble("prix_vente")
            binding.textDetails.text = "Code: $code | $categorie | Prix: ${prix.toInt()} FC"

            val stock = item.optInt("quantite_stock")
            val seuil = item.optInt("seuil_alerte")
            val exp = item.optString("date_expiration", "—")
            val statutExp = item.optString("statut_expiration", "—")

            binding.textStock.text = "Stock: $stock (seuil $seuil) | Exp: $exp"
            binding.textStock.setTextColor(
                when {
                    stock <= 0 -> Color.parseColor("#C0392B")
                    item.optBoolean("stock_faible") -> Color.parseColor("#E6A817")
                    else -> Color.parseColor("#2A7345")
                }
            )

            if (statutExp != "—" && statutExp != "OK") {
                binding.textDetails.text = binding.textDetails.text.toString() + "\n$statutExp"
            }
        }
    }
}
