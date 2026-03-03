<?php
session_start();

require_once '../config/database.php';
require_once '../classes/Utilisateur.php';

// Doit être connecté
Utilisateur::exigerConnexion();

// ✅ Un admin ne doit jamais atterrir ici
if (Utilisateur::estAdmin()) {
    header('Location: ../admin/dashboard.php');
    exit;
}

// ✅ Si doit_changer_mdp est déjà à false → pas besoin d'être ici
if (empty($_SESSION['doit_changer_mdp'])) {
    header('Location: ../responsable/dashboard.php');
    exit;
}

$erreur = '';
$succes = '';

$db          = (new Database())->connect();
$utilisateur = new Utilisateur($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Choix 1 : Changer le mot de passe ───────────────────────────────────
    if ($action === 'changer') {
        $resultat = $utilisateur->modifierMotDePasse(
            $_SESSION['user_id'],
            $_POST['ancien_mdp']       ?? '',
            $_POST['nouveau_mdp']      ?? '',
            $_POST['confirmation_mdp'] ?? ''
        );

        if ($resultat['succes']) {
            $succes = $resultat['message'];
            // doit_changer_mdp = 0 en BDD + session (géré dans modifierMotDePasse)
            header('refresh:2;url=../responsable/dashboard.php');
        } else {
            $erreur = $resultat['message'];
        }
    }

    // ── Choix 2 : Reporter à plus tard (session uniquement) ──────────────────
    if ($action === 'plus_tard') {
        // On désactive le blocage pour cette session uniquement
        // La BDD garde doit_changer_mdp = 1 → il sera re-invité à la prochaine connexion
        $_SESSION['doit_changer_mdp'] = false;
        header('Location: ../responsable/dashboard.php');
        exit;
    }

    // ── Choix 3 : Ne plus jamais demander (session + BDD) ───────────────────
    if ($action === 'ne_plus_demander') {
        // On met doit_changer_mdp = 0 en BDD → plus jamais invité
        $db->prepare(
            "UPDATE utilisateurs SET doit_changer_mdp = 0 WHERE id_user = ?"
        )->execute([$_SESSION['user_id']]);

        $_SESSION['doit_changer_mdp'] = false;
        header('Location: ../responsable/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changement de mot de passe - Zandoinou Consulting</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #6e8efb, #a777e3);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 30px 15px;
    }

    .carte {
        background: #fff;
        padding: 35px 40px;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        width: 100%;
        max-width: 480px;
    }

    h2 {
        text-align: center;
        color: #1A3A5C;
        margin-bottom: 6px;
        font-size: 22px;
    }
    .sous-titre {
        text-align: center;
        color: #888;
        font-size: 13px;
        margin-bottom: 22px;
    }

    /* Bandeau avertissement */
    .alerte-bandeau {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-left: 5px solid #ffc107;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 20px;
        font-size: 13px;
        color: #856404;
    }
    .alerte-bandeau strong { display: block; margin-bottom: 4px; font-size: 14px; }

    /* Info mdp par défaut */
    .info-mdp-defaut {
        background: #e8f4fd;
        border: 1px solid #bee5f5;
        color: #1A3A5C;
        padding: 10px 14px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 13px;
    }
    .info-mdp-defaut code {
        background: #1A3A5C;
        color: #fff;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 13px;
        letter-spacing: 1px;
    }

    /* Alertes */
    .erreur {
        background: #fde8e8; border: 1px solid #f5c6c6; color: #c0392b;
        padding: 10px 14px; border-radius: 8px; margin-bottom: 18px;
        font-size: 14px; text-align: center;
    }
    .succes {
        background: #d4edda; border: 1px solid #c3e6cb; color: #155724;
        padding: 10px 14px; border-radius: 8px; margin-bottom: 18px;
        font-size: 14px; text-align: center;
    }

    /* Formulaire */
    label {
        display: block; margin-bottom: 5px;
        font-weight: 600; color: #555; font-size: 13px;
    }
    input[type="password"] {
        width: 100%; padding: 11px 14px; margin-bottom: 14px;
        border: 1px solid #ccc; border-radius: 8px;
        font-size: 14px; outline: none; transition: 0.3s;
    }
    input[type="password"]:focus {
        border-color: #6e8efb;
        box-shadow: 0 0 8px rgba(110,142,251,0.3);
    }

    /* Bouton principal */
    .btn-changer {
        width: 100%; padding: 12px;
        border: none; border-radius: 8px;
        background: #1A3A5C; color: #fff;
        font-size: 15px; cursor: pointer; transition: 0.3s;
    }
    .btn-changer:hover { background: #14304f; }

    /* Séparateur */
    .separateur {
        display: flex; align-items: center; gap: 12px;
        margin: 20px 0 16px;
        color: #aaa; font-size: 12px;
    }
    .separateur::before, .separateur::after {
        content: ''; flex: 1;
        height: 1px; background: #e0e0e0;
    }

    /* Bloc des choix alternatifs */
    .choix-alternatifs {
        display: flex; flex-direction: column; gap: 10px;
    }

    .choix-card {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 12px 14px;
        border: 1px solid #e8e8e8;
        border-radius: 10px;
        background: #fafafa;
        transition: 0.2s;
    }
    .choix-card:hover { border-color: #ccc; background: #f5f5f5; }

    .choix-card .choix-icone {
        font-size: 20px; flex-shrink: 0; margin-top: 2px;
    }
    .choix-card .choix-texte {
        flex: 1;
    }
    .choix-card .choix-titre {
        font-size: 13px; font-weight: 600; color: #333; margin-bottom: 2px;
    }
    .choix-card .choix-desc {
        font-size: 11px; color: #888; line-height: 1.4;
    }
    .choix-card .choix-btn {
        flex-shrink: 0;
        padding: 6px 14px;
        border-radius: 20px;
        border: none;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
        align-self: center;
    }

    /* Bouton "Plus tard" */
    .btn-plus-tard {
        background: #e8eef5; color: #1A3A5C;
    }
    .btn-plus-tard:hover { background: #d0dcec; }

    /* Bouton "Ne plus demander" */
    .btn-ne-plus {
        background: #fde8e8; color: #c0392b;
    }
    .btn-ne-plus:hover { background: #fbd0d0; }

    /* Accordéon pour afficher/masquer le formulaire */
    #formulaireMdp { display: none; }
    #formulaireMdp.visible { display: block; }

    .btn-toggle-form {
        width: 100%; padding: 12px;
        border: 2px solid #1A3A5C; border-radius: 8px;
        background: #fff; color: #1A3A5C;
        font-size: 15px; font-weight: 600;
        cursor: pointer; transition: 0.3s;
        display: flex; align-items: center;
        justify-content: center; gap: 8px;
    }
    .btn-toggle-form:hover { background: #e8eef5; }
</style>
</head>
<body>
<div class="carte">

    <h2>🔐 Sécurité du compte</h2>
    <p class="sous-titre">
        Bonjour, <strong><?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></strong>
    </p>

    <!-- Bandeau d'avertissement -->
    <div class="alerte-bandeau">
        <strong>⚠️ Mot de passe par défaut détecté</strong>
        Votre compte utilise encore le mot de passe par défaut.
        Nous vous recommandons de le changer pour sécuriser votre accès.
    </div>

    <!-- Rappel du mot de passe par défaut -->
    <div class="info-mdp-defaut">
        Votre mot de passe actuel (par défaut) est : <code>passer123</code>
    </div>

    <?php if ($erreur): ?>
        <div class="erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if ($succes): ?>
        <div class="succes">
            ✅ <?= htmlspecialchars($succes) ?><br>
            <small>Redirection vers le tableau de bord dans 2 secondes...</small>
        </div>
    <?php else: ?>

    <!-- ── Bouton pour afficher le formulaire de changement ── -->
    <button class="btn-toggle-form" onclick="toggleFormulaire()" id="btnToggle">
        <span>🔑</span> Changer mon mot de passe maintenant
    </button>

    <!-- ── Formulaire de changement (masqué par défaut) ── -->
    <div id="formulaireMdp" class="<?= $erreur ? 'visible' : '' ?>">
        <br>
        <form method="POST">
            <input type="hidden" name="action" value="changer">

            <label>Mot de passe actuel (par défaut)</label>
            <input type="password" name="ancien_mdp"
                   placeholder="Entrez passer123" required>

            <label>
                Nouveau mot de passe
                <small style="color:#888; font-weight:normal">(8 caractères min.)</small>
            </label>
            <input type="password" name="nouveau_mdp"
                   placeholder="Votre nouveau mot de passe" required>

            <label>Confirmer le nouveau mot de passe</label>
            <input type="password" name="confirmation_mdp"
                   placeholder="Répétez votre nouveau mot de passe" required>

            <button type="submit" class="btn-changer">
                ✅ Valider le changement
            </button>
        </form>
    </div>

    <!-- ── Séparateur ── -->
    <div class="separateur">ou choisissez une autre option</div>

    <!-- ── Les deux choix alternatifs ── -->
    <div class="choix-alternatifs">

        <!-- Option 2 : Plus tard (session uniquement) -->
        <div class="choix-card">
            <div class="choix-icone">⏰</div>
            <div class="choix-texte">
                <div class="choix-titre">Me le rappeler à la prochaine connexion</div>
                <div class="choix-desc">
                    Vous accédez au système maintenant mais vous serez invité
                    à changer votre mot de passe à votre prochaine connexion.
                </div>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="plus_tard">
                <button type="submit" class="choix-btn btn-plus-tard">
                    Plus tard
                </button>
            </form>
        </div>

        <!-- Option 3 : Ne plus jamais demander -->
        <div class="choix-card">
            <div class="choix-icone">🚫</div>
            <div class="choix-texte">
                <div class="choix-titre">Conserver ce mot de passe définitivement</div>
                <div class="choix-desc">
                    Vous choisissez de garder <code style="background:#eee;padding:1px 5px;
                    border-radius:3px;font-size:11px">passer123</code> comme mot de passe.
                    Cette demande ne s'affichera plus. <strong style="color:#c0392b">
                    (Déconseillé)</strong>
                </div>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="ne_plus_demander">
                <button type="submit" class="choix-btn btn-ne-plus"
                        onclick="return confirm('Êtes-vous sûr de vouloir conserver le mot de passe par défaut ? Cela peut compromettre la sécurité de votre compte.')">
                    Conserver
                </button>
            </form>
        </div>

    </div>

    <?php endif; ?>

</div>

<script>
function toggleFormulaire() {
    const form    = document.getElementById('formulaireMdp');
    const btn     = document.getElementById('btnToggle');
    const visible = form.classList.toggle('visible');
    btn.innerHTML = visible
        ? '<span>✖</span> Annuler'
        : '<span>🔑</span> Changer mon mot de passe maintenant';
}

<?php if ($erreur): ?>
// Rouvrir automatiquement le formulaire si erreur
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('formulaireMdp').classList.add('visible');
    document.getElementById('btnToggle').innerHTML = '<span>✖</span> Annuler';
});
<?php endif; ?>
</script>
</body>
</html>