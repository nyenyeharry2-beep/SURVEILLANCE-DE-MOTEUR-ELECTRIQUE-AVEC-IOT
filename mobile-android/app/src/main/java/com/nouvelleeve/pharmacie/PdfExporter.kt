package com.nouvelleeve.pharmacie

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.Color
import android.graphics.Paint
import android.graphics.pdf.PdfDocument
import android.net.Uri
import androidx.core.content.FileProvider
import org.json.JSONObject
import java.io.File
import java.io.FileOutputStream
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

object PdfExporter {

    fun exportRapport(context: Context, title: String, report: JSONObject): Uri {
        val lines = buildReportLines(report)
        return writeTextPdf(context, "rapport_${System.currentTimeMillis()}.pdf", title, lines, logo = null)
    }

    fun exportRecu(context: Context, recu: JSONObject): Uri {
        val vente = recu.optJSONObject("vente") ?: JSONObject()
        val title = "Reçu ${vente.optString("numero")}"
        val lines = RecuActivity.formatRecuText(recu).lines()
        val logo = BitmapFactory.decodeResource(context.resources, R.drawable.logo)
        return writeTextPdf(context, "recu_${vente.optString("numero")}.pdf", title, lines, logo)
    }

    private fun writeTextPdf(
        context: Context,
        fileName: String,
        title: String,
        lines: List<String>,
        logo: Bitmap?
    ): Uri {
        val document = PdfDocument()
        val pageWidth = 595
        val pageHeight = 842
        val margin = 40f
        val lineHeight = 16f
        val paint = Paint().apply {
            color = Color.BLACK
            textSize = 10f
            isAntiAlias = true
        }
        val titlePaint = Paint(paint).apply {
            textSize = 14f
            isFakeBoldText = true
        }

        var y = margin
        var pageNumber = 1
        var pageInfo = PdfDocument.PageInfo.Builder(pageWidth, pageHeight, pageNumber).create()
        var page = document.startPage(pageInfo)
        var canvas = page.canvas

        fun newPage() {
            document.finishPage(page)
            pageNumber++
            pageInfo = PdfDocument.PageInfo.Builder(pageWidth, pageHeight, pageNumber).create()
            page = document.startPage(pageInfo)
            canvas = page.canvas
            y = margin
        }

        fun ensureSpace(extra: Float) {
            if (y + extra > pageHeight - margin) {
                newPage()
            }
        }

        logo?.let { bitmap ->
            val logoSize = 56f
            val scaled = Bitmap.createScaledBitmap(bitmap, logoSize.toInt(), logoSize.toInt(), true)
            val logoX = (pageWidth - logoSize) / 2f
            canvas.drawBitmap(scaled, logoX, y, null)
            y += logoSize + 12f
            if (scaled !== bitmap) {
                scaled.recycle()
            }
        }

        ensureSpace(lineHeight * 2)
        canvas.drawText(title, margin, y, titlePaint)
        y += lineHeight * 2

        lines.forEach { line ->
            ensureSpace(lineHeight)
            canvas.drawText(line, margin, y, paint)
            y += lineHeight
        }

        document.finishPage(page)

        val dir = File(context.cacheDir, "reports").apply { mkdirs() }
        val file = File(dir, fileName)
        FileOutputStream(file).use { document.writeTo(it) }
        document.close()

        return FileProvider.getUriForFile(
            context,
            "${context.packageName}.fileprovider",
            file
        )
    }

    private fun buildReportLines(report: JSONObject): List<String> {
        val lines = mutableListOf<String>()
        val sdf = SimpleDateFormat("dd/MM/yyyy HH:mm", Locale.FRANCE)
        lines.add("Pharmacie Nouvelle Eve")
        lines.add("Généré le ${sdf.format(Date())}")
        lines.add("")

        val periode = report.optJSONObject("periode")
        if (periode != null) {
            lines.add("Période : ${periode.optString("debut")} → ${periode.optString("fin")}")
        }

        report.optDouble("taux_usd_cdf").takeIf { it > 0 }?.let {
            lines.add("Taux : 1 USD = ${String.format(Locale.FRANCE, "%,.0f", it)} FC")
        }

        val totaux = report.optJSONObject("totaux")
        if (totaux != null) {
            lines.add("")
            lines.add("--- TOTAUX ---")
            lines.add("Nombre de ventes : ${totaux.optInt("nb_ventes")}")
            lines.add("Total CDF brut : ${formatNum(totaux.optDouble("cdf_brut"))} FC")
            lines.add("Total USD brut : $${formatNum(totaux.optDouble("usd_brut"))}")
            lines.add("Total converti CDF : ${formatNum(totaux.optDouble("cdf_converti"))} FC")
            lines.add("Total converti USD : $${formatNum(totaux.optDouble("usd_converti"))}")
        }

        val parDevise = report.optJSONArray("par_devise")
        if (parDevise != null && parDevise.length() > 0) {
            lines.add("")
            lines.add("--- PAR DEVISE ---")
            for (i in 0 until parDevise.length()) {
                val row = parDevise.getJSONObject(i)
                lines.add("${row.optString("devise")} : ${row.optInt("nb_ventes")} ventes | ${formatNum(row.optDouble("total"))}")
                lines.add("  Équiv. ${formatNum(row.optDouble("equivalent_cdf"))} FC / $${formatNum(row.optDouble("equivalent_usd"))}")
            }
        }

        val ventes = report.optJSONArray("ventes")
        if (ventes != null && ventes.length() > 0) {
            lines.add("")
            lines.add("--- DÉTAIL DES VENTES ---")
            for (i in 0 until ventes.length()) {
                val v = ventes.getJSONObject(i)
                val devise = v.optString("devise", "CDF")
                val montant = v.optDouble("montant_total")
                val montantStr = if (devise == "USD") "$${formatNum(montant)}" else "${formatNum(montant)} FC"
                lines.add("${v.optString("numero")} | ${v.optString("date_vente")} | $montantStr")
                lines.add("  ${v.optString("details", "—")}")
                lines.add("  Client: ${v.optString("client_nom", "—")} | Vendeur: ${v.optString("vendeur")}")
            }
        }

        return lines
    }

    private fun formatNum(value: Double): String =
        String.format(Locale.FRANCE, "%,.2f", value)
}
