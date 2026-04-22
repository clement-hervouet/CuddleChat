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
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    background: #fdf6ec;
    font-family: 'Lato', sans-serif;
    color: #4a3010;
    min-height: 100vh;
}

/* NAV */
.nav {
    position: sticky; top: 0; z-index: 100;
    background: #f5e6c8;
    border-bottom: 1.5px solid #e2c98a;
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 28px;
    font-family: 'Caveat', cursive; font-size: 18px; color: #7a4a1e;
    transition: transform 0.35s ease, opacity 0.35s ease;
}
.nav.hidden { transform: translateY(-100%); opacity: 0; pointer-events: none; }
.nav-title  { font-size: 24px; font-weight: 600; letter-spacing: 1px; }
.nav-right  { display: flex; align-items: center; gap: 20px; }
.nav-links  { display: flex; gap: 18px; }
.nav-links a { color: #a0622b; text-decoration: none; font-size: 16px; }
.nav-links a:hover { text-decoration: underline; }

.btn-nouvelle {
    background: #c9915a; color: #fff; border: none;
    font-family: 'Caveat', cursive; font-size: 17px;
    padding: 7px 18px; border-radius: 4px; cursor: pointer;
}
.btn-nouvelle:hover { background: #b07840; }

/* ERROR */
.error-banner {
    max-width: 860px; margin: 16px auto 0;
    background: #fbeaea; border: 1.5px solid #e2a0a0;
    border-radius: 4px; padding: 10px 18px;
    font-family: 'Caveat', cursive; font-size: 17px; color: #8b2020;
}

/* TIMELINE */
.timeline {
    max-width: 860px; margin: 0 auto;
    padding: 40px 20px 120px;
    display: flex; flex-direction: column; gap: 52px;
}

.empty {
    text-align: center; font-family: 'Caveat', cursive;
    font-size: 22px; color: #b07840; margin-top: 60px;
}

.lettre {
    display: flex; align-items: flex-start; gap: 24px;
    opacity: 0; transform: translateY(20px);
    animation: apparition 0.5s ease forwards;
}
.lettre.droite { flex-direction: row-reverse; }

@keyframes apparition {
    to { opacity: 1; transform: translateY(0); }
}

.bloc-photo {
    display: flex; flex-direction: column; align-items: center; gap: 7px;
    flex-shrink: 0;
}
.photo-frame {
    width: 130px; height: 130px;
    background: #fff;
    border: 2px solid #d4a96a;
    padding: 6px;
    box-shadow: 2px 2px 0 #c9915a;
}
.photo-frame img { width: 100%; height: 100%; object-fit: cover; display: block; }
.legende   { font-family: 'Caveat', cursive; font-size: 13px; color: #8c5a2a; text-align: center; max-width: 130px; line-height: 1.3; }
.date-label { font-family: 'Caveat', cursive; font-size: 13px; color: #b07840; }

.article {
    flex: 1;
    background: #fffdf5;
    border: 1.5px solid #e0c990;
    padding: 22px 24px 18px;
    position: relative;
    box-shadow: 3px 3px 0 #d6b86a;
    font-family: 'Caveat', cursive; font-size: 18px;
    color: #4a3010; line-height: 1.6;
    min-height: 140px;
}
.article-auteur {
    font-size: 12px; color: #b07840;
    font-family: 'Lato', sans-serif; font-weight: 700;
    letter-spacing: 0.5px; text-transform: uppercase;
    margin-bottom: 8px;
}
.article-texte { white-space: pre-line; }

.timbre {
    position: absolute; top: -14px; right: -10px;
    width: 46px; height: 56px;
    background: #fdf6ec;
    border: 2px solid #c9915a;
    outline: 4px dotted #c9915a; outline-offset: -7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    transform: rotate(3deg);
    box-shadow: 1px 1px 0 #b07840;
}

/* tilt par position */
.lettre:nth-child(odd)  .article { transform: rotate(-1deg); }
.lettre:nth-child(even) .article { transform: rotate(0.7deg); }
.lettre:nth-child(3n)   .article { transform: rotate(-0.4deg); }

/* FAB bas de page */
.fab {
    position: fixed; bottom: 28px; right: 28px; z-index: 90;
    background: #c9915a; color: #fff; border: none;
    font-family: 'Caveat', cursive; font-size: 18px;
    padding: 12px 22px; border-radius: 30px; cursor: pointer;
    box-shadow: 2px 3px 0 #b07840;
}
.fab:hover { background: #b07840; }

/* OVERLAY */
.overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(90,55,20,0.45);
    z-index: 200; align-items: center; justify-content: center;
}
.overlay.open { display: flex; }

.modal {
    background: #fffdf5;
    border: 2px solid #d4a96a;
    box-shadow: 5px 5px 0 #c9915a;
    width: 520px; max-width: 95vw; max-height: 90vh;
    overflow-y: auto;
    padding: 28px 30px 22px;
    transform: rotate(-0.5deg);
    position: relative;
    font-family: 'Caveat', cursive;
    animation: pop 0.22s ease;
}
@keyframes pop {
    from { opacity: 0; transform: rotate(-0.5deg) scale(0.94); }
    to   { opacity: 1; transform: rotate(-0.5deg) scale(1); }
}

.modal-timbre {
    position: absolute; top: -14px; right: -10px;
    width: 46px; height: 56px;
    background: #fdf6ec; border: 2px solid #c9915a;
    outline: 4px dotted #c9915a; outline-offset: -7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; transform: rotate(4deg);
    box-shadow: 1px 1px 0 #b07840;
}
.modal h2 { font-size: 22px; color: #7a4a1e; margin-bottom: 20px; font-weight: 600; }
.form-row { margin-bottom: 16px; }
.form-row label { display: block; font-size: 15px; color: #8c5a2a; margin-bottom: 5px; }

.upload-zone {
    border: 2px dashed #d4a96a; background: #fdf6ec;
    border-radius: 4px; padding: 16px; text-align: center;
    cursor: pointer; color: #b07840; font-size: 15px;
    position: relative; transition: background 0.2s;
}
.upload-zone:hover { background: #f5e6c8; }
.upload-zone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.upload-preview { display: none; width: 80px; height: 80px; object-fit: cover; border: 2px solid #d4a96a; padding: 3px; margin: 0 auto 6px; }

.modal input[type=date],
.modal input[type=text],
.modal textarea {
    width: 100%; font-family: 'Caveat', cursive; font-size: 16px;
    border: 1.5px solid #d4a96a; background: #fdf6ec;
    padding: 7px 12px; border-radius: 4px; color: #4a3010; outline: none;
}
.modal input[type=date]:focus,
.modal input[type=text]:focus,
.modal textarea:focus { border-color: #c9915a; }
.modal textarea { resize: vertical; min-height: 100px; line-height: 1.5; }

.modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; }
.btn-annuler {
    background: none; border: 1.5px solid #d4a96a; color: #a0622b;
    font-family: 'Caveat', cursive; font-size: 16px;
    padding: 7px 18px; border-radius: 4px; cursor: pointer;
}
.btn-annuler:hover { background: #f5e6c8; }
.btn-envoyer {
    background: #c9915a; color: #fff; border: none;
    font-family: 'Caveat', cursive; font-size: 16px;
    padding: 7px 22px; border-radius: 4px; cursor: pointer;
}
.btn-envoyer:hover { background: #b07840; }

/* timbres variés par index */
.t0::after { content:'🌸'; } .t1::after { content:'🌼'; }
.t2::after { content:'☕'; } .t3::after { content:'🍂'; }
.t4::after { content:'⭐'; } .t5::after { content:'🌿'; }
.timbre { font-size: 0; }
.timbre::after { font-size: 22px; }
</style>
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