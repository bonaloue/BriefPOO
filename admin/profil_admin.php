<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Utilisateur.php';

Utilisateur::exigerConnexion();
Utilisateur::exigerAdmin();

$db          = (new Database())->connect();
$utilisateur = new Utilisateur($db);

$succes_profil = '';
$erreur_profil = '';
$succes_mdp    = '';
$erreur_mdp    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'modifier_profil') {
        $res = $utilisateur->modifierProfil(
            $_SESSION['user_id'],
            trim($_POST['nom']    ?? ''),
            trim($_POST['prenom'] ?? '')
        );
        $res['succes']
            ? $succes_profil = $res['message']
            : $erreur_profil = $res['message'];
    }

    if ($action === 'changer_mdp') {
        $res = $utilisateur->modifierMotDePasse(
            $_SESSION['user_id'],
            $_POST['ancien_mdp']       ?? '',
            $_POST['nouveau_mdp']      ?? '',
            $_POST['confirmation_mdp'] ?? ''
        );
        $res['succes']
            ? $succes_mdp = $res['message']
            : $erreur_mdp = $res['message'];
    }
}

$profil = $utilisateur->trouverParId($_SESSION['user_id']);

$page_titre    = "Mon profil";
$page_courante = 'profil_admin.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page"><i class="bi bi-person-circle me-2"></i>Mon profil</h1>
        <div class="topbar-droite">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['user_prenom'], 0, 1) . substr($_SESSION['user_nom'], 0, 1)) ?>
            </div>
        </div>
    </div>

    <div class="page-content">

        <div class="profil-hero mb-4">
            <div class="profil-avatar-lg">
                <?= strtoupper(substr($profil['prenom'], 0, 1) . substr($profil['nom'], 0, 1)) ?>
            </div>
            <div>
                <h2 class="profil-nom">
                    <?= htmlspecialchars($profil['prenom'] . ' ' . $profil['nom']) ?>
                </h2>
                <div class="profil-meta">
                    <span class="badge-role-admin"><i class="bi bi-shield-fill me-1"></i>Administrateur</span>
                    <span class="text-muted ms-3">
                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($profil['email']) ?>
                    </span>
                    <span class="text-muted ms-3">
                        <i class="bi bi-calendar3 me-1"></i>Membre depuis le <?= date('d/m/Y', strtotime($profil['date_creation'])) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-4">

            <div class="col-lg-6">
                <div class="form-card h-100">
                    <div class="section-titre">
                        <i class="bi bi-person-fill me-2"></i>Informations personnelles
                    </div>

                    <?php if ($succes_profil): ?>
                        <div class="alerte-succes"> <?= htmlspecialchars($succes_profil) ?></div>
                    <?php endif; ?>
                    <?php if ($erreur_profil): ?>
                        <div class="alerte-erreur"> <?= htmlspecialchars($erreur_profil) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="modifier_profil">

                        <div class="mb-3">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control"
                                   value="<?= htmlspecialchars($profil['prenom']) ?>"
                                   placeholder="Votre prénom" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control"
                                   value="<?= htmlspecialchars($profil['nom']) ?>"
                                   placeholder="Votre nom" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Adresse email</label>
                            <input type="email" class="form-control" readonly
                                   value="<?= htmlspecialchars($profil['email']) ?>"
                                   style="background:#f8f9fa; cursor:not-allowed">
                            <small class="text-muted">L'email ne peut pas être modifié.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Rôle</label>
                            <div class="form-control" style="background:#f8f9fa; cursor:not-allowed">
                                <span class="badge-role-admin">
                                    <i class="bi bi-shield-fill me-1"></i>Administrateur
                                </span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-navy w-100">
                            <i class="bi bi-check-lg me-2"></i>Enregistrer les modifications
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="form-card h-100" id="mdp">
                    <div class="section-titre">
                        <i class="bi bi-shield-lock-fill me-2"></i>Changer mon mot de passe
                    </div>

                    <?php if ($succes_mdp): ?>
                        <div class="alerte-succes"> <?= htmlspecialchars($succes_mdp) ?></div>
                    <?php endif; ?>
                    <?php if ($erreur_mdp): ?>
                        <div class="alerte-erreur"> <?= htmlspecialchars($erreur_mdp) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="changer_mdp">

                        <div class="mb-3">
                            <label class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="ancien_mdp" id="ancien_mdp"
                                       class="form-control" placeholder="Votre mot de passe actuel" required>
                                <button type="button" class="btn btn-outline-secondary btn-oeil"
                                        onclick="toggleVisibilite('ancien_mdp', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Nouveau mot de passe <span class="text-danger">*</span>
                                <small class="text-muted fw-normal">(8 caractères min.)</small>
                            </label>
                            <div class="input-group">
                                <input type="password" name="nouveau_mdp" id="nouveau_mdp"
                                       class="form-control" placeholder="Votre nouveau mot de passe"
                                       required oninput="verifierForce(this.value)">
                                <button type="button" class="btn btn-outline-secondary btn-oeil"
                                        onclick="toggleVisibilite('nouveau_mdp', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="force-barre mt-2">
                                <div id="force-fill" class="force-fill"></div>
                            </div>
                            <small id="force-texte" class="text-muted"></small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="confirmation_mdp" id="confirmation_mdp"
                                       class="form-control" placeholder="Répétez votre nouveau mot de passe"
                                       required oninput="verifierCorrespondance()">
                                <button type="button" class="btn btn-outline-secondary btn-oeil"
                                        onclick="toggleVisibilite('confirmation_mdp', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small id="correspond-texte"></small>
                        </div>

                        <div class="regles-mdp mb-4">
                            <div class="regle" id="r-longueur">
                                <i class="bi bi-circle me-1"></i>Au moins 8 caractères
                            </div>
                            <div class="regle" id="r-different">
                                <i class="bi bi-circle me-1"></i>Différent du mot de passe par défaut
                            </div>
                        </div>

                        <button type="submit" class="btn btn-navy w-100">
                            <i class="bi bi-lock-fill me-2"></i>Changer mon mot de passe
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .profil-hero {
        display: flex;
        align-items: center;
        gap: 24px;
        background: #fff;
        border-radius: 14px;
        padding: 28px 32px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .profil-avatar-lg {
        width: 80px; height: 80px;
        background: var(--navy);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 800; font-size: 28px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(26,58,92,0.3);
    }
    .profil-nom {
        font-size: 22px; font-weight: 700;
        color: var(--navy); margin: 0 0 8px;
    }
    .profil-meta {
        font-size: 13px; display: flex;
        align-items: center; flex-wrap: wrap; gap: 4px;
    }
    .badge-role-admin {
        background: #E63946; color: #fff;
        font-size: 11px; font-weight: 700;
        padding: 3px 10px; border-radius: 20px;
    }

    .section-titre {
        font-size: 16px; font-weight: 700;
        color: var(--navy); margin-bottom: 22px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f0f0f0;
    }

    .force-barre {
        height: 5px; background: #e9ecef;
        border-radius: 10px; overflow: hidden;
    }
    .force-fill {
        height: 100%; width: 0%;
        border-radius: 10px;
        transition: width 0.3s, background 0.3s;
    }

    .regles-mdp {
        background: #f8f9fa; border-radius: 8px;
        padding: 12px 16px;
    }
    .regle {
        font-size: 12px; color: #aaa; margin-bottom: 4px;
        transition: color 0.2s;
    }
    .regle:last-child { margin-bottom: 0; }
    .regle.ok { color: #198754; }
    .regle.ok i::before { content: "\f26b"; } /* bi-check-circle */

    .btn-oeil { border-left: 0; }
</style>

<script>
function toggleVisibilite(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function verifierForce(valeur) {
    const fill   = document.getElementById('force-fill');
    const texte  = document.getElementById('force-texte');
    const rLong  = document.getElementById('r-longueur');
    const rDiff  = document.getElementById('r-different');

    if (valeur.length >= 8) {
        rLong.className = 'regle ok';
        rLong.innerHTML = '<i class="bi bi-check-circle me-1"></i>Au moins 8 caractères';
    } else {
        rLong.className = 'regle';
        rLong.innerHTML = '<i class="bi bi-circle me-1"></i>Au moins 8 caractères';
    }

    if (valeur !== 'passer123' && valeur.length > 0) {
        rDiff.className = 'regle ok';
        rDiff.innerHTML = '<i class="bi bi-check-circle me-1"></i>Différent du mot de passe par défaut';
    } else {
        rDiff.className = 'regle';
        rDiff.innerHTML = '<i class="bi bi-circle me-1"></i>Différent du mot de passe par défaut';
    }

    let score = 0;
    if (valeur.length >= 8)  score++;
    if (valeur.length >= 12) score++;
    if (/[A-Z]/.test(valeur)) score++;
    if (/[0-9]/.test(valeur)) score++;
    if (/[^A-Za-z0-9]/.test(valeur)) score++;

    const niveaux = [
        { max: 0, label: '',           couleur: '#e9ecef', pct: 0   },
        { max: 1, label: 'Très faible',couleur: '#E63946', pct: 20  },
        { max: 2, label: 'Faible',     couleur: '#f0ad4e', pct: 40  },
        { max: 3, label: 'Moyen',      couleur: '#ffc107', pct: 60  },
        { max: 4, label: 'Fort',       couleur: '#198754', pct: 80  },
        { max: 5, label: 'Très fort',  couleur: '#0d6efd', pct: 100 },
    ];

    const n = niveaux[Math.min(score, 5)];
    fill.style.width      = n.pct + '%';
    fill.style.background = n.couleur;
    texte.textContent     = n.label;
    texte.style.color     = n.couleur;
}

function verifierCorrespondance() {
    const nouveau  = document.getElementById('nouveau_mdp').value;
    const confirm  = document.getElementById('confirmation_mdp').value;
    const feedback = document.getElementById('correspond-texte');

    if (confirm.length === 0) {
        feedback.textContent = '';
        return;
    }
    if (nouveau === confirm) {
        feedback.textContent = ' Les mots de passe correspondent';
        feedback.style.color = '#198754';
    } else {
        feedback.textContent = '❌ Les mots de passe ne correspondent pas';
        feedback.style.color = '#E63946';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.location.hash === '#mdp') {
        const section = document.getElementById('mdp');
        if (section) {
            setTimeout(() => {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                section.style.boxShadow = '0 0 0 3px rgba(26,58,92,0.3)';
                setTimeout(() => section.style.boxShadow = '', 2000);
            }, 300);
        }
    }
});
</script>

</body>
</html>