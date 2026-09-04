<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"/>
  <title>Invitations Moïse & Sarah</title>
  <link rel="icon" href="assets/app-icon.png?v=<?= $V ?>" type="image/png"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preload" as="image" href="<?= htmlspecialchars($couplePhoto) ?>"/>
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= $V ?>"/>
  <link rel="stylesheet" href="assets/css/invitation.css?v=<?= $V ?>"/>
</head>
<body data-version="<?= $V ?>">

<section id="screen-home" class="screen active">
  <div class="app-hero">
    <div class="app-hero-inner">
      <img class="app-hero-logo" id="homeLogo" src="<?= htmlspecialchars($coupleLogo) ?>" alt="Moïse & Sarah"/>
      <div>
        <h1 class="app-title">Invitations</h1>
        <p class="app-subtitle">Moïse NKUBA & Sarah KASONGO</p>
      </div>
    </div>
  </div>

  <div class="screen-inner screen-home-body">
    <?php if (!$hasCustomCouple): ?>
    <div class="card card-highlight">
      <div class="card-icon">📷</div>
      <h3>Ajoutez la photo des mariés</h3>
      <p>Une seule photo — elle apparaît sur toutes les invitations.</p>
      <label class="btn-primary btn-block">
        Choisir une photo
        <input type="file" id="uploadCoupleHome" accept="image/*" hidden/>
      </label>
      <p id="homeUploadStatus" class="hint"></p>
    </div>
    <?php else: ?>
    <div class="status-pill">✓ Photo des mariés installée</div>
    <?php endif; ?>

    <div class="preview-card">
      <div class="poster-viewport" style="--inv-scale:0.24">
        <div class="poster-host" id="heroPreview">
          <div class="poster-loading" style="--load-accent:#6B2D82">
            <div class="poster-loading-photo">
              <img src="<?= htmlspecialchars($couplePhoto) ?>" alt="Moïse & Sarah"/>
              <div class="poster-loading-shimmer"></div>
            </div>
            <div class="poster-loading-spinner"></div>
            <span class="poster-loading-text">Chargement…</span>
          </div>
        </div>
      </div>
    </div>

    <div class="quick-actions">
      <button type="button" class="action-card action-primary" data-nav="add">
        <span class="action-icon">✨</span>
        <span class="action-label">Créer une invitation</span>
        <span class="action-desc">Nom + design + QR code</span>
      </button>
      <button type="button" class="action-card" data-nav="guests">
        <span class="action-icon">📋</span>
        <span class="action-label">Mes invités</span>
        <span class="action-desc">Liste, export, renvoi</span>
      </button>
      <button type="button" class="action-card" data-nav="config">
        <span class="action-icon">⚙️</span>
        <span class="action-label">Paramètres</span>
        <span class="action-desc">Date, lieu, message WhatsApp</span>
      </button>
    </div>

    <a class="btn-apk" href="app/invitation-mariage-nkuba-kasongo.apk">📱 Télécharger l'app Android</a>
    <p class="version-tag">v<?= $V ?></p>
  </div>
</section>

<section id="screen-config" class="screen screen-config">
  <div class="screen-inner">
    <button type="button" class="back-link" data-nav="home">← Retour</button>
    <h2 class="page-heading">Paramètres</h2>
    <p class="page-desc">Date, lieu et message d'envoi WhatsApp</p>

    <div class="field">
      <label>Photo des mariés</label>
      <input type="file" id="uploadCouple" accept="image/jpeg,image/png,image/webp"/>
      <p id="photoStatus" class="hint"></p>
    </div>

    <div class="preview-card preview-card-sm">
      <div class="poster-viewport" style="--inv-scale:0.18">
        <div class="poster-host" id="configPreview"></div>
      </div>
    </div>

    <div class="field">
      <label for="cfgDate">Date du mariage</label>
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
      <textarea id="cfgMessage" rows="5">Bonjour {NAME},

Nous avons l'honneur de vous inviter au mariage civil de Moïse NKUBA & Sarah KASONGO, le {DATE}.

🕐 {TIME}
📍 {VENUE}
🪑 Table {TABLE} — {SEATS} place(s)

Votre présence fera notre immense joie.</textarea>
    </div>

    <button type="button" class="btn-primary btn-block" id="btnSaveConfig">Enregistrer</button>
  </div>
</section>

<section id="screen-add" class="screen">
  <div class="screen-inner">
    <button type="button" class="back-link" data-nav="home">← Retour</button>
    <h2 class="page-heading">Nouvelle invitation</h2>
    <p class="page-desc">Chaque invité reçoit son nom et un QR code unique</p>

    <div class="card form-card">
      <div class="field">
        <label for="guestName">Nom de l'invité *</label>
        <input type="text" id="guestName" placeholder="Ex : Mme Sarah BANZA" required autofocus/>
        <p class="hint">Le prénom et nom — pas l'e-mail</p>
      </div>
      <div class="field">
        <label for="whatsapp">Numéro WhatsApp</label>
        <input type="tel" id="whatsapp" placeholder="243 XXX XXX XXX"/>
        <p class="hint">Pour envoyer l'invitation en un clic</p>
      </div>
      <div class="field-row">
        <div class="field seats">
          <label for="seats">Places</label>
          <input type="number" id="seats" value="2" min="1"/>
        </div>
        <div class="field">
          <label for="tableNum">Table</label>
          <input type="text" id="tableNum" placeholder="Ex : 5"/>
        </div>
      </div>
    </div>

    <p class="section-title">Choisir le modèle</p>
    <div class="style-grid" id="styleGrid"></div>

    <div class="preview-card preview-card-add">
      <p class="preview-live-label">Aperçu du modèle sélectionné</p>
      <div class="poster-viewport" style="--inv-scale:0.20">
        <div class="poster-host" id="addPreview">
          <div class="poster-loading" style="--load-accent:#6B2D82">
            <div class="poster-loading-photo">
              <img src="<?= htmlspecialchars($couplePhoto) ?>" alt="Moïse & Sarah"/>
              <div class="poster-loading-shimmer"></div>
            </div>
            <div class="poster-loading-spinner"></div>
            <span class="poster-loading-text">Chargement…</span>
          </div>
        </div>
      </div>
    </div>

    <details class="advanced-options">
      <summary>Options avancées (QR code)</summary>
      <div class="field" style="margin-top:12px">
        <label for="qrData">Données QR</label>
        <input type="text" id="qrData" placeholder="Généré automatiquement"/>
        <button type="button" class="btn-ghost btn-sm" id="btnRegenQr">↻ Nouveau QR</button>
      </div>
    </details>
  </div>
  <button type="button" class="btn-primary btn-floating" id="btnGenerate">✨ Générer l'invitation</button>
</section>

<section id="screen-preview" class="screen">
  <div class="screen-inner preview-center">
    <button type="button" class="back-link" data-nav="add">← Modifier</button>
    <h2 class="page-heading">Votre invitation</h2>
    <p class="page-desc">Pour : <strong id="previewGuestLabel">—</strong></p>
    <div class="phone-frame">
      <div class="poster-viewport poster-viewport-img" id="previewViewport">
        <div class="poster-host" id="previewPoster">
          <div class="poster-loading" style="--load-accent:#6B2D82">
            <div class="poster-loading-photo">
              <img src="<?= htmlspecialchars($couplePhoto) ?>" alt="Moïse & Sarah"/>
              <div class="poster-loading-shimmer"></div>
            </div>
            <div class="poster-loading-spinner"></div>
            <span class="poster-loading-text">Chargement…</span>
          </div>
        </div>
      </div>
    </div>
    <button type="button" class="btn-wa btn-block" id="btnSendWa">💬 Envoyer sur WhatsApp</button>
    <button type="button" class="btn-secondary btn-block" id="btnDownloadPng">⬇️ Télécharger l'image</button>
    <button type="button" class="btn-ghost" data-nav="add">+ Autre invité</button>
    <button type="button" class="btn-ghost" id="btnToGuestList">Voir tous les invités</button>
  </div>
</section>

<section id="screen-guests" class="screen">
  <div class="screen-inner">
    <button type="button" class="back-link" data-nav="home">← Retour</button>
    <h2 class="page-heading">Mes invités</h2>
    <p id="guestListStats" class="page-desc">Chargement…</p>

    <div class="field-row" style="margin-top:12px">
      <div class="field" style="flex:2">
        <input type="search" id="guestSearch" placeholder="Rechercher…"/>
      </div>
      <div class="field" style="flex:1">
        <select id="guestSort">
          <option value="name">Nom</option>
          <option value="phone">Téléphone</option>
          <option value="table">Table</option>
          <option value="recent">Récent</option>
        </select>
      </div>
    </div>

    <div class="card guest-list-card" id="guestListBody"></div>
    <a class="btn-secondary btn-block" href="api/guests.php?action=export">Exporter la liste (CSV)</a>
    <button type="button" class="btn-primary btn-block" data-nav="add">+ Nouvelle invitation</button>
  </div>
</section>

<div id="invitationRenderHost" aria-hidden="true"></div>

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
