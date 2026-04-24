<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/headers.php';
send_security_headers();

$pdo    = db_cuddle();
$stmt   = $pdo->query('SELECT * FROM lettres ORDER BY date_lettre ASC, created_at ASC');
$lettres = $stmt->fetchAll();

$errors = [
    'champs_manquants'      => 'Tous les champs sont obligatoires.',
    'date_invalide'         => 'La date fournie est invalide.',
    'type_fichier_invalide' => 'Format de photo non accepté (jpg, png, webp, gif).',
    'fichier_trop_lourd'    => 'La photo ne doit pas dépasser 5 Mo.',
    'upload_echoue'         => 'L\'envoi de la photo a échoué, réessaie.',
    'legende_invalide'      => 'La légende doit faire entre 2 et 255 caractères.',
    'texte_invalide'        => 'Le texte doit faire entre 2 et 5000 caractères.',
    'db_error'              => 'Une erreur est survenue, réessaie.',
    'non_autorise'          => 'Action non autorisée.',
    'csrf'                  => 'Session expirée, réessaie.',
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
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <img id="lightbox-img" src="" alt=""/>
</div>
<body>

<nav class="nav" id="nav">
    <button class="btn-hamburger" id="hamburger" onclick="toggleMenu()">☰</button>
    <span class="nav-title">🐻 Courrier des Peluches</span>
    <div class="nav-right">
        <p>Connecté en tant que<b><?php echo " "; echo $_SESSION['firstname']; ?></b></p>
        <div class="nav-links">
            <a href="login/connections/logout.php">Déconnexion</a>
            <a href="login/password_reset.php">Changer mon mot de passe</a>
        </div>
        <button class="btn-nouvelle" onclick="openModal()">💌 Nouvelle lettre</button>
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
        $side = ((int)$l['user_id'] === CURRENT_USER_ID) ? 'droite' : '';
        $stamp    = $stamps[$i % count($stamps)];
        $date_fr  = (new DateTime($l['date_lettre']))->format('d/m/Y');
        $delay    = round($i * 0.12, 2);
        $color_class = ((int)$l['user_id'] === CURRENT_USER_ID) ? 'lettre-moi' : 'lettre-autre';
    ?>
    <div class="lettre <?= $side ?> <?= $color_class ?>" style="animation-delay:<?= $delay ?>s">
        <div class="bloc-photo">
            <div class="date-label"><?= $date_fr ?></div>
            <div class="photo-frame">
                <img src="uploads/<?= htmlspecialchars($l['photo_path'], ENT_QUOTES) ?>"
                     alt="Photo de <?= $auteur ?>"
                     loading="lazy"
                     onclick="openLightbox(this.src)"
                     style="cursor:zoom-in;"/>
            </div>
            <div class="legende"><?= htmlspecialchars($l['legende'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="article">
    <?php if (!empty($l['stamp'])): ?>
        <div class="timbre" style="transform: rotate(<?= rand(-8, 8) ?>deg);">
            <img src="assets/static/stamps/<?= htmlspecialchars($l['stamp'], ENT_QUOTES) ?>"
                 alt="timbre"
                 loading="lazy"/>
        </div>
    <?php endif; ?>
    <div class="article-auteur"><?= $auteur ?></div>
    <div class="article-texte"><?= htmlspecialchars($l['texte'], ENT_QUOTES, 'UTF-8') ?></div>

    <?php if ((int)$l['user_id'] === CURRENT_USER_ID): ?>
        <div style="position:absolute; bottom:10px; right:12px; display:flex; gap:6px; margin:0;">
            <button type="button" class="btn-edit"
                onclick="openEditModal(<?= $l['id'] ?>, <?= htmlspecialchars(json_encode($l['date_lettre']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($l['legende']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($l['texte']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($l['stamp']), ENT_QUOTES) ?>)">✎</button>
            <form method="POST" action="actions/delete_lettre.php"
                  onsubmit="return confirm('Supprimer cette lettre ?')"
                  style="margin:0;">
                <input type="hidden" name="id" value="<?= $l['id'] ?>"/>
                <?= csrf_field() ?>
                <button type="submit" class="btn-delete">✕</button>
            </form>
        </div>
    <?php endif; ?>
</div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<button class="fab" onclick="openModal()">💌 Nouvelle lettre</button>

<!-- MODAL -->
<div class="overlay" id="overlay" onclick="if(event.target===this) closeModal()">
    <div class="modal">
        <div id="modal-stamp-wrap" onclick="nextStamp('modal-stamp-img', 'stamp-input')"
                style="position:absolute; top:-20px; right:-12px; height:110px; cursor:pointer;" title="Changer le timbre">
            <img id="modal-stamp-img" src="" alt="timbre"
                style="width:100%; height:100%; object-fit:contain;
                display:none;"/>
        </div>

        <h2>Nouvelle lettre</h2>

        <div class="modal-inner">
        
            <form method="POST" action="actions/post_lettre.php" enctype="multipart/form-data">
                <input type="hidden" name="stamp" id="stamp-input"/>
                <?= csrf_field() ?>

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
</div>
<div class="overlay" id="overlay-edit" onclick="if(event.target===this) closeEditModal()">
    <div class="modal">
        <div id="edit-stamp-wrap" onclick="nextStamp('edit-stamp-img', 'edit-stamp-input')"
            style="position:absolute; top:-20px; right:-12px; height:110px; cursor:pointer;" title="Changer le timbre">
            <img id="edit-stamp-img" src="" alt="timbre"
                style="width:100%; height:100%; object-fit:contain;
                display:none;"/>
        </div>
        <div class="modal-inner">
            <h2>Modifier la lettre</h2>
            <form method="POST" action="actions/edit_lettre.php" enctype="multipart/form-data">
                <input type="hidden" name="id"    id="edit-id"/>
                <input type="hidden" name="stamp" id="edit-stamp-input"/>
                <?= csrf_field() ?>

                <div class="form-row">
                    <label>Photo de la peluche <span style="font-size:12px; color:#b07840;">(laisser vide pour conserver)</span></label>
                    <div class="upload-zone">
                        <img class="upload-preview" id="edit-preview" src="" alt=""/>
                        <div id="edit-uploadHint">📎 Cliquer ou déposer une nouvelle photo</div>
                        <input type="file" name="photo" accept="image/*" onchange="previewEditImg(this)"/>
                    </div>
                </div>

                <div class="form-row">
                    <label>Date de la lettre</label>
                    <input type="date" name="date_lettre" id="edit-date" required/>
                </div>

                <div class="form-row">
                    <label>Légende de la photo</label>
                    <input type="text" name="legende" id="edit-legende" required maxlength="255"/>
                </div>

                <div class="form-row">
                    <label>Texte de la lettre</label>
                    <textarea name="texte" id="edit-texte" required></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-annuler" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" class="btn-envoyer">Enregistrer ✎</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const today = new Date().toISOString().split('T')[0];
document.getElementById('dateLetter').value = today;

let stampList = [];
let stampIndex = 0;

async function openModal() {
    document.getElementById('overlay').classList.add('open');

    if (stampList.length === 0) {
        const res = await fetch('stamps.php');
        stampList = await res.json();
    }
    if (!stampList.length) return;

    stampIndex = Math.floor(Math.random() * stampList.length);
    applyStamp('modal-stamp-img', 'stamp-input');
}

function applyStamp(imgId, inputId) {
    const chosen = stampList[stampIndex];
    const tilt   = (Math.random() * 16 - 8).toFixed(1);
    const img    = document.getElementById(imgId);
    img.src = 'assets/static/stamps/' + chosen;
    img.style.transform = `rotate(${tilt}deg)`;
    img.style.display = 'block';
    document.getElementById(inputId).value = chosen;
}

function nextStamp(imgId, inputId) {
    stampIndex = (stampIndex + 1) % stampList.length;
    applyStamp(imgId, inputId);
}

function closeModal() { document.getElementById('overlay').classList.remove('open'); }

async function openEditModal(id, date, legende, texte, currentStamp) {
    document.getElementById('edit-id').value      = id;
    document.getElementById('edit-date').value    = date;
    document.getElementById('edit-legende').value = legende;
    document.getElementById('edit-texte').value   = texte;
    document.getElementById('overlay-edit').classList.add('open');

    if (stampList.length === 0) {
        const res = await fetch('stamps.php');
        stampList = await res.json();
    }
    if (!stampList.length) return;

    stampIndex = stampList.indexOf(currentStamp);
    if (stampIndex === -1) stampIndex = Math.floor(Math.random() * stampList.length);
    applyStamp('edit-stamp-img', 'edit-stamp-input');
}

function closeEditModal() {
    document.getElementById('overlay-edit').classList.remove('open');
}

function openLightbox(src) {
    const lb  = document.getElementById('lightbox');
    const img = document.getElementById('lightbox-img');
    img.src = src;
    img.style.animation = '';
    lb.style.background = 'rgba(20, 10, 0, 0)';
    lb.classList.add('open');
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            lb.style.background = 'rgba(20, 10, 0, 0.92)';
        });
    });
}
function closeLightbox() {
    const lb  = document.getElementById('lightbox');
    const img = document.getElementById('lightbox-img');
    lb.style.background = 'rgba(20, 10, 0, 0)';
    img.style.animation = 'zoom-out 0.25s ease forwards';
    setTimeout(() => {
        lb.classList.remove('open');
        img.style.animation = '';
        lb.style.background = '';
    }, 250);
}

function previewEditImg(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('edit-preview');
        img.src = e.target.result;
        img.style.display = 'block';
        document.getElementById('edit-uploadHint').style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
}

function toggleMenu() {
    document.getElementById('nav').querySelector('.nav-right').classList.toggle('open');
}

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
</html>