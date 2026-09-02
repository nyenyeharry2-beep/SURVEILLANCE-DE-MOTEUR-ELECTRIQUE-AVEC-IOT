package com.nouvelleeve.pharmacie

import android.content.Context
import android.graphics.Canvas
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
        val lines = buildLines(title, report)
        val document = PdfDocument()
        val pageWidth = 595
        val pageHeight = 842
        val margin = 40f
        val lineHeight = 18f
        val paint = Paint().apply {
            color = Color.BLACK
            textSize = 11f
            isAntiAlias = true
        }
        val titlePaint = Paint(paint).apply {
            textSize = 16f
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

        canvas.drawText(title, margin, y, titlePaint)
        y += lineHeight * 2

        lines.forEach { line ->
            if (y > pageHeight - margin) {
                newPage()
            }
            canvas.drawText(line, margin, y, paint)
            y += lineHeight
        }

        document.finishPage(page)

        val dir = File(context.cacheDir, "reports").apply { mkdirs() }
        val fileName = "rapport_${System.currentTimeMillis()}.pdf"
        val file = File(dir, fileName)
        FileOutputStream(file).use { document.writeTo(it) }
        document.close()

        return FileProvider.getUriForFile(
            context,
            "${context.packageName}.fileprovider",
            file
        )
    }

    private fun buildLines(title: String, report: JSONObject): List<String> {
        val lines = mutableListOf<String>()
        val sdf = SimpleDateFormat("dd/MM/yyyy HH:mm", Locale.FRANCE)
        lines.add("Pharmacie Nouvelle Eve")
        lines.add("Généré le ${sdf.format(Date())}")
        lines.add("")

        val periode = report.optJSONObject("periode")
        if (periode != null) {
            lines.add("Période : ${periode.optString("debut")} → ${periode.optString("fin")}")
        }

        val totaux = report.optJSONObject("totaux")
        if (totaux != null) {
            lines.add("")
            lines.add("--- TOTAUX ---")
            lines.add("Nombre de ventes : ${totaux.optInt("nb_ventes")}")
            lines.add("Total CDF : ${formatNum(totaux.optDouble("cdf_brut"))} FC")
            lines.add("Total USD : $${formatNum(totaux.optDouble("usd_brut"))}")
            lines.add("Converti CDF : ${formatNum(totaux.optDouble("cdf_converti"))} FC")
            lines.add("Converti USD : $${formatNum(totaux.optDouble("usd_converti"))}")
        }

        val ventes = report.optJSONArray("ventes")
        if (ventes != null && ventes.length() > 0) {
            lines.add("")
            lines.add("--- DÉTAIL DES VENTES ---")
            for (i in 0 until ventes.length()) {
                val v = ventes.getJSONObject(i)
                val montant = v.optDouble("montant_total")
                val devise = v.optString("devise", "CDF")
                val montantStr = if (devise == "USD") "$${formatNum(montant)}" else "${formatNum(montant)} FC"
                lines.add("${v.optString("numero")} | ${v.optString("date_vente")} | $montantStr")
                lines.add("  Client: ${v.optString("client_nom", "—")} | Vendeur: ${v.optString("vendeur")}")
            }
        }

        return lines
    }

    private fun formatNum(value: Double): String =
        String.format(Locale.FRANCE, "%,.2f", value)
}
