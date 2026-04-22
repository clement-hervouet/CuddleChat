<?php
require_once __DIR__ . '/includes/db.php';

$pdo    = db_cuddle();
$stmt   = $pdo->query('SELECT * FROM lettres ORDER BY date_lettre ASC, created_at ASC');
$lettres = $stmt->fetchAll();

$errors = [
    'champs_manquants'    => 'Tous les champs sont obligatoires.',
    'date_invalide'       => 'La date fournie est invalide.',
    'type_fichier_invalide' => 'Format de photo non accepté (jpg, png, webp, gif).',
    'fichier_trop_lourd'  => 'La photo ne doit pas dépasser 5 Mo.',
    'upload_echoue'       => 'L\'envoi de la photo a échoué, réessaie.',
];
$error_msg = $errors[$_GET['error'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Courrier des Peluches</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;600&family=Lato:wght@400;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="assets/static/css/style.css">
<link rel="script" href="/assets/static/script/script.js">
</head>
<body>

<nav class="nav" id="nav">
    <span class="nav-title">🐻 Courrier des Peluches</span>
    <div class="nav-right">
        <div class="nav-links">
            <a href="login/connections/logout.php">Déconnexion</a>
            <a href="login/password_reset.php">Changer mon mot de passe</a>
        </div>
        <button class="btn-nouvelle" onclick="openModal()">✉ Nouvelle lettre</button>
    </div>
</nav>

<?php if ($error_msg): ?>
<div class="error-banner"><?= $error_msg ?></div>
<?php endif; ?>

<div class="timeline">
<?php if (empty($lettres)): ?>
    <p class="empty">Aucune lettre pour l'instant… écris la première ! 🐾</p>
<?php else: ?>
    <?php
    $stamps = ['t0','t1','t2','t3','t4','t5'];
    foreach ($lettres as $i => $l):
        $auteur = htmlspecialchars($l['auteur'], ENT_QUOTES, 'UTF-8');
        $side     = ($i % 2 === 0) ? '' : 'droite';
        $stamp    = $stamps[$i % count($stamps)];
        $date_fr  = (new DateTime($l['date_lettre']))->format('d/m/Y');
        $delay    = round($i * 0.12, 2);
    ?>
    <div class="lettre <?= $side ?>" style="animation-delay:<?= $delay ?>s">
        <div class="bloc-photo">
            <div class="date-label"><?= $date_fr ?></div>
            <div class="photo-frame">
                <img src="uploads/<?= htmlspecialchars($l['photo_path'], ENT_QUOTES) ?>"
                     alt="Photo de <?= $auteur ?>"/>
            </div>
            <div class="legende"><?= htmlspecialchars($l['legende'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="article">
            <div class="timbre <?= $stamp ?>"></div>
            <div class="article-auteur"><?= $auteur ?></div>
            <div class="article-texte"><?= htmlspecialchars($l['texte'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<button class="fab" onclick="openModal()">✉ Nouvelle lettre</button>

<!-- MODAL -->
<div class="overlay" id="overlay" onclick="if(event.target===this) closeModal()">
    <div class="modal">
        <div class="modal-timbre">✉</div>
        <h2>Nouvelle lettre</h2>
        <form method="POST" action="actions/post_lettre.php" enctype="multipart/form-data">

            <div class="form-row">
                <label>Photo de la peluche</label>
                <div class="upload-zone">
                    <img class="upload-preview" id="preview" src="" alt=""/>
                    <div id="uploadHint">📎 Cliquer ou déposer une photo</div>
                    <input type="file" name="photo" accept="image/*" required onchange="previewImg(this)"/>
                </div>
            </div>

            <div class="form-row">
                <label>Date de la lettre</label>
                <input type="date" name="date_lettre" id="dateLetter" required/>
            </div>

            <div class="form-row">
                <label>Légende de la photo</label>
                <input type="text" name="legende" placeholder="Ex : Nounours au soleil du matin…" required maxlength="255"/>
            </div>

            <div class="form-row">
                <label>Texte de la lettre</label>
                <textarea name="texte" placeholder="Cher(e) …" required></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-annuler" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn-envoyer">Envoyer ✉</button>
            </div>
        </form>
    </div>
</div>

<script>
const today = new Date().toISOString().split('T')[0];
document.getElementById('dateLetter').value = today;

function openModal()  { document.getElementById('overlay').classList.add('open'); }
function closeModal() { document.getElementById('overlay').classList.remove('open'); }

function previewImg(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('preview');
        img.src = e.target.result;
        img.style.display = 'block';
        document.getElementById('uploadHint').style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
}

let lastY = 0;
const nav = document.getElementById('nav');
window.addEventListener('scroll', () => {
    const y = window.scrollY;
    nav.classList.toggle('hidden', y > lastY && y > 60);
    lastY = y;
});
</script>
</body>
</html>