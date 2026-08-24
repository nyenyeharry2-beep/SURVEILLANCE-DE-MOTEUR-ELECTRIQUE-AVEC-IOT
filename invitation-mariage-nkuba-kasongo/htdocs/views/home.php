<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"/>
  <title>invitation-mariage-nkuba-kasongo</title>
  <link rel="icon" href="assets/app-icon.png?v=<?= $V ?>" type="image/png"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= $V ?>"/>
  <link rel="stylesheet" href="assets/css/invitation.css?v=<?= $V ?>"/>
</head>
<body data-version="<?= $V ?>">

<section id="screen-home" class="screen active">
  <div class="screen-inner">
    <div class="header-row">
      <div class="logo-frame">
        <img id="homeLogo" src="<?= htmlspecialchars($coupleLogo) ?>" alt="Logo Moïse & Sarah"/>
      </div>
      <div>
        <h1>Générateur d'Invitations</h1>
        <p class="subtitle">Mariage de Moïse & Sarah</p>
      </div>
    </div>

    <?php if (!$hasCustomCouple): ?>
    <div class="card upload-alert">
      <h3>📷 Importez la photo des mariés</h3>
      <p>Le design de l'affiche est en <strong>HTML/CSS</strong> — seule la photo de Moïse & Sarah est insérée dynamiquement sur chaque invitation.</p>
      <label class="btn-upload-home">
        📷 Choisir la photo des mariés
        <input type="file" id="uploadCoupleHome" accept="image/*" hidden/>
      </label>
      <p id="homeUploadStatus" class="hint" style="margin-top:8px"></p>
    </div>
    <?php else: ?>
    <p class="badge-ok">✓ Photo des mariés active — design HTML/CSS</p>
    <?php endif; ?>

    <div class="hero-poster hero-html">
      <div class="poster-viewport" style="--inv-scale:0.22">
        <div class="poster-scaler" id="heroPreview"></div>
      </div>
    </div>
    <p class="caption">Affiche HTML — Moïse NKUBA & Sarah KASONGO</p>
    <p class="caption-gold">Design officiel violet • photo couple dynamique</p>

    <div class="card card-menu" data-nav="add">
      <div class="card-menu-row">
        <div class="icon-box gold">👤</div>
        <div class="card-menu-text">
          <h3>Ajouter un invité</h3>
          <p>Nom → invitation avec QR automatique</p>
        </div>
        <span class="card-menu-arrow">›</span>
      </div>
    </div>

    <a class="card card-menu" href="generator.html" style="text-decoration:none;color:inherit;display:block">
      <div class="card-menu-row">
        <div class="icon-box blue">🎨</div>
        <div class="card-menu-text">
          <h3>Générateur HTML/CSS/JS</h3>
          <p>Aperçu live + QR code qrcode.js</p>
        </div>
        <span class="card-menu-arrow">›</span>
      </div>
    </a>

    <div class="card card-menu" data-nav="guests">
      <div class="card-menu-row">
        <div class="icon-box purple">📋</div>
        <div class="card-menu-text">
          <h3>Liste des invités</h3>
          <p>Nom, téléphone, table — sync app</p>
        </div>
        <span class="card-menu-arrow">›</span>
      </div>
    </div>

    <div class="card card-menu" data-nav="config">
      <div class="card-menu-row">
        <div class="icon-box blue">⚙</div>
        <div class="card-menu-text">
          <h3>Configurer l'événement</h3>
          <p>Date, lieu, affiches, WhatsApp</p>
        </div>
        <span class="card-menu-arrow">›</span>
      </div>
    </div>

    <p class="version-tag">invitation-mariage-nkuba-kasongo • v<?= $V ?></p>
    <a class="apk-link" href="app/invitation-mariage-nkuba-kasongo.apk">📱 Télécharger l'application Android</a>
  </div>
</section>

<section id="screen-config" class="screen screen-config">
  <div class="screen-inner">
    <button type="button" class="back-link" data-nav="home">← Retour</button>
    <div class="header-row" style="justify-content:flex-start">
      <div class="logo-frame lg">
        <img id="configLogo" src="<?= htmlspecialchars($coupleLogo) ?>" alt="Logo"/>
      </div>
      <div>
        <h1>Configurer l'événement</h1>
        <p class="subtitle">Mariage de Moïse & Sarah</p>
      </div>
    </div>

    <p id="photoStatus" class="config-intro" style="color:#ffcc80;margin-bottom:12px">
      Les affiches sont en HTML/CSS. Uploadez uniquement la <strong>photo HD des mariés</strong>.
    </p>

    <div class="field">
      <label>📷 Photo des mariés (Moïse & Sarah) *</label>
      <input type="file" id="uploadCouple" accept="image/jpeg,image/png,image/webp"/>
    </div>

    <div class="config-poster-card hero-html">
      <div class="poster-viewport" style="--inv-scale:0.18">
        <div class="poster-scaler" id="configPreview"></div>
      </div>
    </div>

    <div class="field">
      <label for="cfgDate">Date</label>
      <input type="text" id="cfgDate" value="Vendredi, le 11 Septembre 2026"/>
    </div>
    <div class="field">
      <label for="cfgTime">Heure</label>
      <input type="text" id="cfgTime" value="11h00"/>
    </div>
    <div class="field">
      <label for="cfgVenue">Lieu</label>
      <input type="text" id="cfgVenue" value="Commune de Kipushi, Ville de KIPUSHI"/>
    </div>
    <div class="field">
      <label for="cfgMessage">Message WhatsApp</label>
      <textarea id="cfgMessage" rows="4">Bonjour {NAME}, nous avons l'honneur de vous inviter au mariage civil de Moïse NKUBA & Sarah KASONGO, le {DATE} à {VENUE}. Table {TABLE}, {SEATS} place(s).</textarea>
    </div>

    <button type="button" class="btn-blue" id="btnSaveConfig">Enregistrer</button>
  </div>
</section>

<section id="screen-add" class="screen">
  <div class="screen-inner">
    <div class="page-title">
      <button type="button" class="btn-settings" data-nav="config">⚙</button>
      <h2>Ajouter un invité</h2>
      <p class="subtitle">Le nom sera inscrit sur l'invitation</p>
    </div>

    <div class="card">
      <div class="field">
        <label for="guestName">Nom complet de l'invité *</label>
        <input type="text" id="guestName" placeholder="Ex: Mme Sarah BANZA" required autofocus/>
      </div>
      <div class="field">
        <label for="whatsapp">WhatsApp</label>
        <input type="tel" id="whatsapp" placeholder="243XXXXXXXXX"/>
      </div>
      <div class="field-row">
        <div class="field seats">
          <label for="seats">Places</label>
          <input type="number" id="seats" value="2" min="1"/>
        </div>
        <div class="field">
          <label for="tableNum">Table</label>
          <input type="text" id="tableNum" placeholder="Table 12"/>
        </div>
      </div>
      <div class="field">
        <label for="qrData">Données du QR code</label>
        <input type="text" id="qrData" placeholder="Généré automatiquement (nom + identifiant unique)"/>
        <p class="hint">Format : INVITE|nom:…|id:…|table:…|places:… — modifiable avant envoi</p>
        <button type="button" class="btn-outline btn-sm" id="btnRegenQr" style="margin-top:8px">↻ Nouvel identifiant QR</button>
      </div>
    </div>

    <div class="style-grid">
      <div class="style-thumb active" data-style="mariage-civil">
        <span class="check">✓</span>
        <div class="preview-wrap style-chip style-civil">
          <span>Mariage Civil</span>
        </div>
        <span class="style-name">Violet — roses</span>
      </div>
      <div class="style-thumb" data-style="affiche-blanche">
        <span class="check">✓</span>
        <div class="preview-wrap style-chip style-blanche">
          <span>Bénédiction</span>
        </div>
        <span class="style-name">Blanche — or</span>
      </div>
    </div>
  </div>
  <button type="button" class="btn-gold-fixed" id="btnGenerate">Générer l'invitation</button>
</section>

<section id="screen-preview" class="screen">
  <div class="screen-inner preview-center">
    <button type="button" class="back-link" data-nav="add">← Retour</button>
    <h2>Aperçu final</h2>
    <p class="subtitle">Invitation pour : <strong id="previewGuestLabel">—</strong></p>
    <div class="phone-frame">
      <div class="poster-viewport" style="--inv-scale:0.22">
        <div class="poster-scaler" id="previewPoster"></div>
      </div>
    </div>
    <p class="hint qr-preview-meta">QR : <code id="qrDataPreview">—</code></p>
    <button type="button" class="btn-wa" id="btnSendWa">Envoyer WhatsApp (message + photo)</button>
    <button type="button" class="btn-blue" id="btnDownloadPng">Télécharger PNG</button>
    <button type="button" class="btn-close" id="btnToGuestList">Voir la liste des invités</button>
    <button type="button" class="btn-close" data-nav="add">Autre invité</button>
  </div>
</section>

<section id="screen-guests" class="screen">
  <div class="screen-inner">
    <button type="button" class="back-link" data-nav="home">← Retour</button>
    <h2>Liste des invités</h2>
    <p id="guestListStats" class="subtitle">Chargement…</p>

    <div class="field-row" style="margin-top:12px">
      <div class="field" style="flex:2">
        <input type="search" id="guestSearch" placeholder="Rechercher nom, téléphone, table…"/>
      </div>
      <div class="field" style="flex:1">
        <select id="guestSort">
          <option value="name">Tri : Nom</option>
          <option value="phone">Tri : Téléphone</option>
          <option value="table">Tri : Table</option>
          <option value="recent">Tri : Récent</option>
        </select>
      </div>
    </div>

    <div class="card" id="guestListBody" style="padding:0;overflow:hidden"></div>

    <a class="btn-outline" href="api/guests.php?action=export" style="display:block;text-align:center;margin-top:12px">Exporter CSV</a>
    <button type="button" class="btn-gold" data-nav="add" style="width:100%;margin-top:12px">+ Ajouter un invité</button>
  </div>
</section>

<script>
  window.NKUBA_BRANDING = {
    couple: <?= json_encode($couplePhoto) ?>,
    hasCustomCouple: <?= $hasCustomCouple ? 'true' : 'false' ?>,
    renderMode: 'html'
  };
</script>
<script src="assets/js/qrcode.min.js?v=<?= $V ?>"></script>
<script src="assets/js/html2canvas.min.js?v=<?= $V ?>"></script>
<script src="assets/js/invitation.js?v=<?= $V ?>"></script>
</body>
</html>
