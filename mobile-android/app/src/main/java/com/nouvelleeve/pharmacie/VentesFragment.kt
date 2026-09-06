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
import com.google.android.material.tabs.TabLayout
import com.nouvelleeve.pharmacie.databinding.FragmentVentesBinding
import com.nouvelleeve.pharmacie.databinding.ItemCartLineBinding
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

data class CartItem(
    val medId: Int,
    val nom: String,
    val code: String,
    val stock: Int,
    val prixCdf: Double,
    var qty: Int,
    val unite: String,
    val uniteLabel: String
)

class VentesFragment : Fragment() {

    private var _binding: FragmentVentesBinding? = null
    private val binding get() = _binding!!
    private val medicaments = mutableListOf<JSONObject>()
    private val cart = mutableListOf<CartItem>()
    private val medAdapter = MedicamentSearchAdapter { med -> promptUniteAndAdd(med) }
    private lateinit var cartAdapter: CartAdapter
    private var searchJob: Job? = null
    private val historiqueAdapter = HistoriqueAdapter(
        onRecu = { venteId -> openRecu(venteId) },
        onCancel = { venteId -> cancelVente(venteId) }
    )
    private var lastVenteId: Int? = null
    private var journeeOuverte = false
    private var tauxJour = 2850.0
    private var businessDate = ""

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
        cartAdapter = CartAdapter(
            onQtyChange = { updateCartTotal() },
            onRemove = { item ->
                cart.remove(item)
                cartAdapter.submit(cart)
                updateCartTotal()
            }
        )
        binding.recyclerCart.layoutManager = LinearLayoutManager(requireContext())
        binding.recyclerCart.adapter = cartAdapter

        binding.recyclerHistorique.layoutManager = LinearLayoutManager(requireContext())
        binding.recyclerHistorique.adapter = historiqueAdapter
        binding.swipeHistorique.setOnRefreshListener { loadHistorique() }

        binding.inputSearchMed.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {}
            override fun afterTextChanged(s: Editable?) { scheduleSearch(s?.toString().orEmpty()) }
        })
        binding.inputSearchMed.setOnEditorActionListener { _, actionId, _ ->
            if (actionId == EditorInfo.IME_ACTION_SEARCH || actionId == EditorInfo.IME_ACTION_DONE) {
                searchJob?.cancel()
                loadMedicaments(binding.inputSearchMed.text?.toString().orEmpty())
                true
            } else false
        }

        binding.btnValiderVente.setOnClickListener { submitSale() }
        binding.btnVoirRecu.setOnClickListener { lastVenteId?.let { openRecu(it) } }
        binding.groupDevise.setOnCheckedChangeListener { _, _ -> updateCartTotal() }

        showPanel(true)
        loadJournee()
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

    private fun loadJournee() {
        lifecycleScope.launch {
            try {
                val data = withContext(Dispatchers.IO) { mainActivity().api().getJournee() }
                journeeOuverte = data.optBoolean("peut_vendre", false)
                tauxJour = data.optDouble("taux_usd_cdf", 2850.0)
                businessDate = data.optString("date_metier", data.optString("date", ""))
                binding.textJourneeStatus.visibility = View.VISIBLE
                binding.textJourneeStatus.text = data.optString("message", "")
                binding.textJourneeStatus.setBackgroundColor(
                    if (journeeOuverte) Color.parseColor("#E8F5EE") else Color.parseColor("#FFF3CD")
                )
                binding.btnValiderVente.isEnabled = journeeOuverte && cart.isNotEmpty()
            } catch (e: Exception) {
                binding.textJourneeStatus.visibility = View.VISIBLE
                binding.textJourneeStatus.text = e.message ?: "Erreur journée"
            }
        }
    }

    private fun loadMedicaments(query: String) {
        lifecycleScope.launch {
            try {
                val data = withContext(Dispatchers.IO) { mainActivity().api().getMedicaments(query.trim()) }
                medicaments.clear()
                medicaments.addAll(data.optJSONArray("medicaments")?.toList().orEmpty())
                medAdapter.submit(medicaments)
                val q = query.trim()
                binding.textSearchHint.text = when {
                    medicaments.isEmpty() && q.isNotEmpty() -> getString(R.string.no_med_for_search, q)
                    q.isEmpty() -> getString(R.string.search_med_hint)
                    else -> "${medicaments.size} résultat(s) pour « $q »"
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), e.message ?: "Erreur", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun addToCart(med: JSONObject, unite: String? = null) {
        val id = med.optInt("id")
        val unites = med.optJSONArray("unites_vente")?.let { arr ->
            (0 until arr.length()).map { arr.getString(it) }
        } ?: listOf("comprime")

        val chosenUnite = unite ?: unites.first()
        if (chosenUnite !in unites) return

        val stockMax = med.optJSONObject("stock_max") ?: JSONObject()
        val stock = when (chosenUnite) {
            "plaquette" -> stockMax.optInt("plaquette", med.optInt("quantite_stock"))
            "flacon" -> stockMax.optInt("flacon", med.optInt("quantite_stock"))
            else -> stockMax.optInt("comprime", med.optInt("quantite_stock"))
        }

        val prixCdf = when (chosenUnite) {
            "plaquette" -> med.optDouble("prix_plaquette")
            "flacon" -> med.optDouble("prix_flacon")
            else -> med.optDouble("prix_comprime", med.optDouble("prix_vente"))
        }

        val uniteLabel = when (chosenUnite) {
            "plaquette" -> "Plaquette"
            "flacon" -> "Flacon"
            else -> "Comprimé"
        }

        val existing = cart.find { it.medId == id && it.unite == chosenUnite }
        if (existing != null) {
            existing.qty = (existing.qty + 1).coerceAtMost(existing.stock)
        } else {
            cart.add(CartItem(
                medId = id,
                nom = med.optString("nom"),
                code = med.optString("code"),
                stock = stock,
                prixCdf = prixCdf,
                qty = 1,
                unite = chosenUnite,
                uniteLabel = uniteLabel
            ))
        }
        cartAdapter.submit(cart)
        updateCartTotal()
        Toast.makeText(requireContext(), R.string.added_to_cart, Toast.LENGTH_SHORT).show()
    }

    private fun promptUniteAndAdd(med: JSONObject) {
        val unites = med.optJSONArray("unites_vente")?.let { arr ->
            (0 until arr.length()).map { arr.getString(it) }
        } ?: listOf("comprime")

        if (unites.size == 1) {
            addToCart(med, unites.first())
            return
        }

        val labels = unites.map { u ->
            when (u) {
                "plaquette" -> "Plaquette (${med.optDouble("prix_plaquette").toInt()} FC)"
                "flacon" -> "Flacon (${med.optDouble("prix_flacon").toInt()} FC)"
                else -> "Comprimé (${med.optDouble("prix_comprime", med.optDouble("prix_vente")).toInt()} FC)"
            }
        }.toTypedArray()

        androidx.appcompat.app.AlertDialog.Builder(requireContext())
            .setTitle(med.optString("nom"))
            .setItems(labels) { _, which -> addToCart(med, unites[which]) }
            .show()
    }

    private fun updateCartTotal() {
        val devise = if (binding.radioUsd.isChecked) "USD" else "CDF"
        var total = 0.0
        cart.forEach { item ->
            val prix = if (devise == "USD") item.prixCdf / tauxJour else item.prixCdf
            total += prix * item.qty
        }
        binding.textCartTotal.text = if (devise == "USD") {
            "Total : $${String.format(Locale.FRANCE, "%,.2f", total)}"
        } else {
            "Total : ${String.format(Locale.FRANCE, "%,.0f", total)} FC"
        }
        binding.btnValiderVente.isEnabled = journeeOuverte && cart.isNotEmpty()
    }

    private fun loadHistorique() {
        binding.swipeHistorique.isRefreshing = true
        lifecycleScope.launch {
            try {
                val date = businessDate.ifBlank {
                    SimpleDateFormat("yyyy-MM-dd", Locale.US).format(java.util.Date())
                }
                val data = withContext(Dispatchers.IO) {
                    mainActivity().api().getHistoriqueVentes(date = date, limit = 50)
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
        if (!journeeOuverte) {
            Toast.makeText(requireContext(), R.string.day_not_open, Toast.LENGTH_LONG).show()
            return
        }
        if (cart.isEmpty()) {
            Toast.makeText(requireContext(), R.string.cart_empty, Toast.LENGTH_SHORT).show()
            return
        }

        val devise = if (binding.radioUsd.isChecked) "USD" else "CDF"
        val client = binding.inputClient.text?.toString().orEmpty()
        val notes = binding.inputNotes.text?.toString().orEmpty()
        val lignes = cart.map { item ->
            val prix = if (devise == "USD") item.prixCdf / tauxJour else item.prixCdf
            VenteLigneInput(item.medId, item.qty, prix, item.unite)
        }

        binding.btnValiderVente.isEnabled = false
        binding.btnVoirRecu.visibility = View.GONE
        binding.textVenteResult.text = getString(R.string.connecting)

        lifecycleScope.launch {
            try {
                val result = withContext(Dispatchers.IO) {
                    mainActivity().api().createVente(lignes, devise, client, notes)
                }
                val venteId = result.optInt("id")
                if (venteId <= 0) {
                    throw Exception("Réponse invalide du serveur")
                }
                lastVenteId = venteId
                cart.clear()
                cartAdapter.submit(cart)
                updateCartTotal()
                loadMedicaments(binding.inputSearchMed.text?.toString().orEmpty())
                Toast.makeText(requireContext(), R.string.sale_success, Toast.LENGTH_SHORT).show()
                openRecu(venteId)
            } catch (e: Exception) {
                binding.textVenteResult.text = e.message ?: "Erreur vente"
                Toast.makeText(requireContext(), e.message ?: "Erreur vente", Toast.LENGTH_LONG).show()
            } finally {
                binding.btnValiderVente.isEnabled = journeeOuverte && cart.isNotEmpty()
            }
        }
    }

    private fun cancelVente(venteId: Int) {
        androidx.appcompat.app.AlertDialog.Builder(requireContext())
            .setMessage(R.string.cancel_confirm)
            .setPositiveButton(R.string.cancel_sale) { _, _ ->
                lifecycleScope.launch {
                    try {
                        withContext(Dispatchers.IO) {
                            mainActivity().api().cancelVente(venteId)
                        }
                        Toast.makeText(requireContext(), R.string.sale_cancelled, Toast.LENGTH_SHORT).show()
                        loadHistorique()
                        loadMedicaments(binding.inputSearchMed.text?.toString().orEmpty())
                    } catch (e: Exception) {
                        Toast.makeText(requireContext(), e.message ?: "Erreur", Toast.LENGTH_LONG).show()
                    }
                }
            }
            .setNegativeButton(android.R.string.cancel, null)
            .show()
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

class CartAdapter(
    private val onQtyChange: () -> Unit,
    private val onRemove: (CartItem) -> Unit
) : RecyclerView.Adapter<CartAdapter.Holder>() {

    private val items = mutableListOf<CartItem>()

    fun submit(list: List<CartItem>) {
        items.clear()
        items.addAll(list)
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): Holder {
        val binding = ItemCartLineBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return Holder(binding)
    }

    override fun onBindViewHolder(holder: Holder, position: Int) = holder.bind(items[position])

    override fun getItemCount(): Int = items.size

    inner class Holder(private val binding: ItemCartLineBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(item: CartItem) {
            binding.textCartNom.text = item.nom
            binding.textCartDetails.text = "${item.code} | ${item.uniteLabel} | ${item.prixCdf.toInt()} FC | max ${item.stock}"
            binding.inputCartQty.setText(item.qty.toString())
            binding.inputCartQty.setOnFocusChangeListener { _, hasFocus ->
                if (!hasFocus) {
                    val q = binding.inputCartQty.text?.toString()?.toIntOrNull() ?: 1
                    item.qty = q.coerceIn(1, item.stock)
                    binding.inputCartQty.setText(item.qty.toString())
                    onQtyChange()
                }
            }
            binding.btnRemoveCart.setOnClickListener { onRemove(item) }
        }
    }
}

class MedicamentSearchAdapter(
    private val onSelect: (JSONObject) -> Unit
) : RecyclerView.Adapter<MedicamentSearchAdapter.Holder>() {

    private val items = mutableListOf<JSONObject>()

    fun submit(list: List<JSONObject>) {
        items.clear()
        items.addAll(list)
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): Holder {
        val binding = ItemMedicamentSearchBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return Holder(binding)
    }

    override fun onBindViewHolder(holder: Holder, position: Int) = holder.bind(items[position])

    override fun getItemCount(): Int = items.size

    inner class Holder(private val binding: ItemMedicamentSearchBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(item: JSONObject) {
            binding.textNom.text = item.optString("nom")
            val stockLabel = item.optString("stock_label", "${item.optInt("quantite_stock")} en stock")
            val type = item.optString("type_unite", "comprime_plaquette")
            val prixInfo = if (type == "flacon") {
                "${item.optDouble("prix_flacon").toInt()} FC/fl"
            } else {
                "${item.optDouble("prix_comprime", item.optDouble("prix_vente")).toInt()} FC/cp · ${item.optDouble("prix_plaquette").toInt()} FC/plt"
            }
            binding.textDetails.text = "Code: ${item.optString("code")} | $stockLabel | $prixInfo"
            binding.cardMedicament.setCardBackgroundColor(Color.WHITE)
            binding.cardMedicament.setOnClickListener { onSelect(item) }
        }
    }
}

class HistoriqueAdapter(
    private val onRecu: (Int) -> Unit,
    private val onCancel: (Int) -> Unit
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

    override fun onBindViewHolder(holder: Holder, position: Int) = holder.bind(items[position])

    override fun getItemCount(): Int = items.size

    inner class Holder(private val binding: ItemVenteHistoriqueBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(item: JSONObject) {
            val annulee = item.optBoolean("annulee", false)
            binding.textNumero.text = if (annulee) {
                "${item.optString("numero")} — ${binding.root.context.getString(R.string.cancelled_label)}"
            } else {
                item.optString("numero")
            }
            binding.textDate.text = formatDate(item.optString("date_vente"))
            binding.textDetails.text = buildString {
                append(item.optString("details", "—"))
                append("\nClient: ${item.optString("client_nom", "—")}")
                if (annulee) {
                    append("\nAnnulée par: ${item.optString("annulee_par", "—")}")
                    append(" — ${item.optString("motif_annulation", "")}")
                }
            }
            val devise = item.optString("devise", "CDF")
            val montant = item.optDouble("montant_total")
            binding.textMontant.text = if (devise == "USD") {
                "$${String.format(Locale.FRANCE, "%,.2f", montant)}"
            } else {
                "${String.format(Locale.FRANCE, "%,.0f", montant)} FC"
            }
            binding.btnRecu.isEnabled = !annulee
            binding.btnRecu.setOnClickListener { if (!annulee) onRecu(item.optInt("id")) }
            binding.btnAnnuler.visibility = if (annulee) View.GONE else View.VISIBLE
            binding.btnAnnuler.setOnClickListener { onCancel(item.optInt("id")) }
            binding.root.alpha = if (annulee) 0.55f else 1f
        }

        private fun formatDate(raw: String): String = try {
            val parsed = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).parse(raw.substring(0, 19))
            if (parsed != null) dateFormat.format(parsed) else raw
        } catch (_: Exception) { raw }
    }
}
