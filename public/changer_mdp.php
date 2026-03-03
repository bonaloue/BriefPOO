<?php
session_start();

require_once '../config/database.php';
require_once '../classes/Utilisateur.php';

// Doit être connecté
Utilisateur::exigerConnexion();

// ✅ CORRECTION 1 : vérification sécurisée (évite l'erreur si clé absente)
// ✅ CORRECTION 2 : un admin ne doit jamais atterrir ici
if (Utilisateur::estAdmin()) {
    header('Location: ../admin/dashboard.php');
    exit;
}

// ✅ CORRECTION 3 : isset() pour éviter le crash si la clé n'existe pas
if (empty($_SESSION['doit_changer_mdp'])) {
    header('Location: ../responsable/dashboard.php');
    exit;
}

$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db          = (new Database())->connect();
    $utilisateur = new Utilisateur($db);

    $resultat = $utilisateur->modifierMotDePasse(
        $_SESSION['user_id'],
        $_POST['ancien_mdp']      ?? '',
        $_POST['nouveau_mdp']     ?? '',
        $_POST['confirmation_mdp'] ?? ''
    );

    if ($resultat['succes']) {
        $succes = $resultat['message'];
        // ✅ CORRECTION 4 : redirection vers le bon dashboard selon le rôle
        header('refresh:2;url=../responsable/dashboard.php');
    } else {
        $erreur = $resultat['message'];
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
        max-width: 460px;
    }

    /* Bandeau d'alerte en haut */
    .alerte-bandeau {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-left: 5px solid #ffc107;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 25px;
        font-size: 14px;
        color: #856404;
    }
    .alerte-bandeau strong { display: block; margin-bottom: 4px; font-size: 15px; }

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
        margin-bottom: 25px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #555;
        font-size: 14px;
    }

    input[type="password"] {
        width: 100%;
        padding: 12px 15px;
        margin-bottom: 18px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 15px;
        outline: none;
        transition: 0.3s;
    }
    input[type="password"]:focus {
        border-color: #6e8efb;
        box-shadow: 0 0 8px rgba(110,142,251,0.4);
    }

    button {
        width: 100%;
        padding: 13px;
        border: none;
        border-radius: 8px;
        background: #1A3A5C;
        color: #fff;
        font-size: 17px;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 5px;
    }
    button:hover { background: #14304f; }

    .erreur {
        background: #fde8e8;
        border: 1px solid #f5c6c6;
        color: #c0392b;
        padding: 10px 14px;
        border-radius: 8px;
        margin-bottom: 18px;
        font-size: 14px;
        text-align: center;
    }
    .succes {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        padding: 10px 14px;
        border-radius: 8px;
        margin-bottom: 18px;
        font-size: 14px;
        text-align: center;
    }

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
        font-size: 14px;
        letter-spacing: 1px;
    }
</style>
</head>
<body>
    <div class="carte">

        <h2>🔐 Changement de mot de passe</h2>
        <p class="sous-titre">Bienvenue, <?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></p>

        <!-- Bandeau d'avertissement -->
        <div class="alerte-bandeau">
            <strong>⚠️ Action requise avant de continuer</strong>
            Votre compte vient d'être créé par l'administrateur.
            Pour des raisons de sécurité, vous devez changer votre mot de passe par défaut avant d'accéder au système.
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
        <?php endif; ?>

        <?php if (!$succes): ?>
        <form action="changer_mdp.php" method="POST">

            <label>Mot de passe actuel (par défaut)</label>
            <input type="password" name="ancien_mdp" placeholder="Entrez passer123" required>

            <label>Nouveau mot de passe <small style="color:#888; font-weight:normal">(8 caractères min.)</small></label>
            <input type="password" name="nouveau_mdp" placeholder="Votre nouveau mot de passe" required>

            <label>Confirmer le nouveau mot de passe</label>
            <input type="password" name="confirmation_mdp" placeholder="Répétez votre nouveau mot de passe" required>

            <button type="submit">Changer mon mot de passe</button>

        </form>
        <?php endif; ?>

    </div>
</body>
</html>