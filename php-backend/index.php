<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitations Moïse & Sarah — Dashboard PHP</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #F5F0EB;
            color: #1A1A1A;
            min-height: 100vh;
        }
        header {
            background: linear-gradient(135deg, #6B2D82, #B8956B);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        header h1 { font-size: 1.8rem; margin-bottom: 0.3rem; }
        header p { opacity: 0.9; color: #D4B896; font-weight: 600; }
        .container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #E0D5C8;
        }
        .card h2 { color: #B8956B; margin-bottom: 1rem; font-size: 1.1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #E0D5C8; }
        th { color: #777; font-size: 0.85rem; text-transform: uppercase; }
        .badge { padding: 0.2rem 0.6rem; border-radius: 10px; font-size: 0.8rem; }
        .sent { background: #D4EDDA; }
        .pending { background: #FFF3CD; }
        .btn {
            display: inline-block;
            background: #4A7BFF;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 14px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 0.5rem;
        }
        .btn-gold { background: #D4B896; color: #1A1A1A; }
        .apk-link {
            background: #25D366;
            display: block;
            text-align: center;
            margin: 1rem 0;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>Générateur d'Invitations</h1>
        <p>Mariage de Moïse & Sarah</p>
    </header>
    <div class="container">
        <div class="card">
            <h2>📱 Application Android (Java natif)</h2>
            <p style="margin-bottom:1rem;color:#777;">Design Adrian — écrans dark/crème/or, affiche Sarah, styles Royal Bordeaux, Ivory, Kipushi.</p>
            <a class="btn apk-link" href="../releases/invitations-moise-sarah-v2.0.1.apk">Télécharger l'APK v2.0.1 (signé)</a>
        </div>

        <div class="card">
            <h2>📋 Liste des invités (PHP)</h2>
            <div id="guestTable">Chargement…</div>
            <a class="btn btn-gold" href="api/guests.php?action=export">Exporter CSV</a>
        </div>
    </div>
    <script>
        fetch('api/guests.php?action=list')
            .then(r => r.json())
            .then(data => {
                const guests = data.guests || [];
                if (!guests.length) {
                    document.getElementById('guestTable').innerHTML = '<p style="color:#777">Aucun invité synchronisé. Utilisez l\'app Android.</p>';
                    return;
                }
                let html = '<table><tr><th>Nom</th><th>WhatsApp</th><th>Table</th><th>Statut</th></tr>';
                guests.forEach(g => {
                    html += `<tr>
                        <td><strong>${g.fullName||''}</strong></td>
                        <td>${g.whatsapp||''}</td>
                        <td>${g.tableZone||'—'}</td>
                        <td><span class="badge ${g.sent?'sent':'pending'}">${g.sent?'Envoyé':'Non envoyé'}</span></td>
                    </tr>`;
                });
                html += '</table>';
                document.getElementById('guestTable').innerHTML = html;
            });
    </script>
</body>
</html>
