<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Utilisateur.php';
require_once '../classes/Notification.php';

Utilisateur::exigerConnexion();
if (Utilisateur::estAdmin()) { header('Location: ../admin/dashboard.php'); exit; }
Utilisateur::exigerChangementMdp();

$db           = (new Database())->connect();
$utilisateur  = new Utilisateur($db);
$notification = new Notification($db);

$id_user   = $_SESSION['user_id'];
$nb_notifs = $notification->compterNonLues($id_user);
$user      = $utilisateur->trouverParId($id_user);

$succes_profil = '';
$erreur_profil = '';
$succes_mdp    = '';
$erreur_mdp    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profil') {
        $res = $utilisateur->modifierProfil(
            $id_user,
            trim($_POST['nom']    ?? ''),
            trim($_POST['prenom'] ?? '')
        );
        $res['succes']
            ? $succes_profil = $res['message']
            : $erreur_profil = $res['message'];

        $user = $utilisateur->trouverParId($id_user);
    }

    if ($action === 'mdp') {
        $res = $utilisateur->modifierMotDePasse(
            $id_user,
            $_POST['ancien_mdp']       ?? '',
            $_POST['nouveau_mdp']      ?? '',
            $_POST['confirmation_mdp'] ?? ''
        );
        $res['succes']
            ? $succes_mdp = $res['message']
            : $erreur_mdp = $res['message'];
    }
}

$page_titre    = "Mon profil";
$page_courante = 'profil.php';
require_once '../includes/header_resp.php';
require_once '../includes/sidebar_resp.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page"><i class="bi bi-person-circle me-2"></i>Mon profil</h1>
    </div>

    <div class="page-content">
        <div class="row g-4">

            <!-- Carte profil + modification infos -->
            <div class="col-lg-5">

                <!-- Carte identité -->
                <div class="table-card mb-4" style="text-align:center; padding:30px 20px">
                    <div style="width:80px;height:80px;background:#1A3A5C;border-radius:50%;
                                display:flex;align-items:center;justify-content:center;
                                margin:0 auto 14px;color:#fff;font-size:30px;font-weight:700">
                        <?= strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold mb-1" style="color:#1A3A5C">
                        <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
                    </h5>
                    <p class="text-muted mb-2" style="font-size:14px">
                        <?= htmlspecialchars($user['email']) ?>
                    </p>
                    <span class="badge" style="background:#198754;color:#fff;padding:5px 14px;border-radius:20px;font-size:13px">
                        Responsable
                    </span>
                    <hr class="my-3">
                    <div style="font-size:13px;color:#888">
                        Membre depuis le <?= date('d/m/Y', strtotime($user['date_creation'])) ?>
                    </div>
                </div>

                <!-- Modifier les informations -->
                <div class="form-card">
                    <h6 class="fw-bold mb-3" style="color:#1A3A5C">
                        <i class="bi bi-pencil me-2"></i>Modifier mes informations
                    </h6>

                    <?php if ($succes_profil): ?>
                        <div class="alerte-succes mb-3">✅ <?= htmlspecialchars($succes_profil) ?></div>
                    <?php endif; ?>
                    <?php if ($erreur_profil): ?>
                        <div class="alerte-erreur mb-3">⚠️ <?= htmlspecialchars($erreur_profil) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="profil">
                        <div class="mb-3">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control"
                                   value="<?= htmlspecialchars($user['nom']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control"
                                   value="<?= htmlspecialchars($user['prenom']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control"
                                   value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <div class="form-text">L'email ne peut être modifié que par l'administrateur.</div>
                        </div>
                        <button type="submit" class="btn btn-navy w-100">
                            <i class="bi bi-save me-1"></i> Enregistrer
                        </button>
                    </form>
                </div>
            </div>

            <!-- Modifier le mot de passe + statistiques -->
            <div class="col-lg-7">
                <div class="form-card">
                    <h6 class="fw-bold mb-3" style="color:#1A3A5C">
                        <i class="bi bi-shield-lock me-2"></i>Modifier mon mot de passe
                    </h6>

                    <?php if ($succes_mdp): ?>
                        <div class="alerte-succes mb-3">✅ <?= htmlspecialchars($succes_mdp) ?></div>
                    <?php endif; ?>
                    <?php if ($erreur_mdp): ?>
                        <div class="alerte-erreur mb-3">⚠️ <?= htmlspecialchars($erreur_mdp) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="mdp">
                        <div class="mb-3">
                            <label class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                            <input type="password" name="ancien_mdp" class="form-control"
                                   placeholder="Votre mot de passe actuel" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Nouveau mot de passe <span class="text-danger">*</span>
                                <small class="text-muted fw-normal">(8 caractères minimum)</small>
                            </label>
                            <input type="password" name="nouveau_mdp" class="form-control"
                                   placeholder="Nouveau mot de passe" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                            <input type="password" name="confirmation_mdp" class="form-control"
                                   placeholder="Répétez le nouveau mot de passe" required>
                        </div>

                        <div class="alert alert-warning py-2 mb-3" style="font-size:13px">
                            <i class="bi bi-info-circle me-1"></i>
                            Vous ne pouvez pas réutiliser le mot de passe par défaut <strong>passer123</strong>.
                        </div>

                        <button type="submit" class="btn btn-navy w-100">
                            <i class="bi bi-lock me-1"></i> Changer mon mot de passe
                        </button>
                    </form>
                </div>

                <!-- Récap rapide des tâches -->
                <?php
                // ✅ CORRECTION : table "taches" + colonne "id_tache" au lieu de "tasks" + "id"
                $recap = $db->prepare(
                    "SELECT 
                        COUNT(*) AS total,
                        SUM(statut = 'en_attente') AS en_attente,
                        SUM(statut = 'en_cours')   AS en_cours,
                        SUM(statut = 'termine')    AS termine,
                        SUM(statut != 'termine' AND date_echeance < CURDATE()) AS en_retard
                     FROM taches WHERE id_responsable = ?"
                );
                $recap->execute([$id_user]);
                $r = $recap->fetch();
                ?>
                <div class="form-card mt-4">
                    <h6 class="fw-bold mb-3" style="color:#1A3A5C">
                        <i class="bi bi-bar-chart me-2"></i>Mes statistiques
                    </h6>
                    <div class="row g-2 text-center">
                        <div class="col-6 col-md-3">
                            <div style="background:#e8eef5;border-radius:10px;padding:14px 8px">
                                <div style="font-size:24px;font-weight:700;color:#1A3A5C"><?= $r['total'] ?></div>
                                <div style="font-size:11px;color:#888">Total</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div style="background:#fff3cd;border-radius:10px;padding:14px 8px">
                                <div style="font-size:24px;font-weight:700;color:#856404"><?= $r['en_attente'] ?></div>
                                <div style="font-size:11px;color:#888">En attente</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div style="background:#d1e7dd;border-radius:10px;padding:14px 8px">
                                <div style="font-size:24px;font-weight:700;color:#0f5132"><?= $r['termine'] ?></div>
                                <div style="font-size:11px;color:#888">Terminées</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div style="background:#fde8e8;border-radius:10px;padding:14px 8px">
                                <div style="font-size:24px;font-weight:700;color:#c0392b"><?= $r['en_retard'] ?></div>
                                <div style="font-size:11px;color:#888">En retard</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>