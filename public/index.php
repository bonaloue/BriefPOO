<?php
session_start();

require_once '../config/database.php';
require_once '../classes/Utilisateur.php';

if (Utilisateur::estConnecte()) {
    if (Utilisateur::estAdmin()) {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../responsable/dashboard.php');
    }
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $db          = (new Database())->connect();
    $utilisateur = new Utilisateur($db);

    $resultat = $utilisateur->connecter(
        trim($_POST['email']       ?? ''),
              $_POST['mot_de_passe'] ?? ''
    );

    if ($resultat['succes']) {

        if (!empty($_SESSION['doit_changer_mdp'])) {
            header('Location: ../responsable/changer_mdp.php');
            exit;
        }

        if (Utilisateur::estAdmin()) {
            header('Location: ../admin/dashboard.php');
        } else {
            header('Location: ../responsable/dashboard.php');
        }
        exit;
    }

    $erreur = $resultat['message'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Zandoinou Consulting</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .carte {
            background: #fff;
            padding: 35px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 420px;
        }

        .logo {
            display: block;
            margin: 0 auto 20px;
            width: 180px;
            max-width: 100%;
            object-fit: contain;
        }

        h2 {
            text-align: center;
            color: #1A3A5C;
            font-size: 22px;
            margin-bottom: 6px;
        }

        .sous-titre {
            text-align: center;
            color: #aaa;
            font-size: 13px;
            margin-bottom: 28px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 18px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #6e8efb;
            box-shadow: 0 0 8px rgba(110,142,251,0.35);
        }

        button[type="submit"] {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #1A3A5C;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 4px;
        }

        button[type="submit"]:hover {
            background: #14304f;
        }

        .erreur {
            background: #fde8e8;
            border: 1px solid #f5c6c6;
            color: #c0392b;
            padding: 11px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .note {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #aaa;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <form action="index.php" class="carte" method="POST">

        <img src="../image/logo.png" alt="Zandoinou Consulting" class="logo"
             onerror="this.style.display='none'">

        <h2>Connexion</h2>
        <p class="sous-titre">Zandoinou Consulting — Espace de gestion</p>

        <?php if ($erreur): ?>
            <div class="erreur"> <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email"
               placeholder="votre@email.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required autofocus>

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe"
               placeholder="Votre mot de passe"
               required>

        <button type="submit">Se connecter</button>

        <p class="note">
            Accès réservé aux membres de Zandoinou Consulting.<br>
            Contactez l'administrateur pour obtenir vos identifiants.
        </p>

    </form>
</body>
</html>