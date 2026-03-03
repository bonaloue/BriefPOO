<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Utilisateur.php';
require_once '../classes/Notification.php';

Utilisateur::exigerConnexion();
if (Utilisateur::estAdmin()) { header('Location: ../admin/contact.php'); exit; }
Utilisateur::exigerChangementMdp();

$db           = (new Database())->connect();
$notification = new Notification($db);
$id_user      = $_SESSION['user_id'];
$nb_notifs    = $notification->compterNonLues($id_user);

$succes = '';
$erreur = '';

// Traitement du formulaire : envoie une notification à tous les admins
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sujet   = trim($_POST['sujet']   ?? '');
    $message = trim($_POST['message'] ?? '');
    $type    = trim($_POST['type']    ?? '');

    if (empty($sujet) || empty($message) || empty($type)) {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        // ✅ Envoyer la demande à tous les admins via le système de notifications
        $admins = $db->query("SELECT id_user FROM utilisateurs WHERE role = 'admin'")->fetchAll();
        foreach ($admins as $admin) {
            $notification->creer(
                $admin['id_user'],
                "📩 Demande de support de " .
                htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) .
                " [{$type}] : {$sujet} - {$message}"
            );
        }
        $succes = 'Votre demande a été transmise à l\'équipe d\'administration.';
    }
}

$page_titre    = "Contact / Support";
$page_courante = 'contact.php';
require_once '../includes/header_resp.php';
require_once '../includes/sidebar_resp.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page"><i class="bi bi-headset me-2"></i>Contact / Support</h1>
        <div class="topbar-droite">
            <?php if ($nb_notifs > 0): ?>
            <a href="notifications.php" class="notif-badge text-decoration-none">
                <i class="bi bi-bell-fill fs-5 text-warning"></i>
                <span class="dot"></span>
            </a>
            <?php endif; ?>
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['user_prenom'], 0, 1) . substr($_SESSION['user_nom'], 0, 1)) ?>
            </div>
        </div>
    </div>

    <div class="page-content">

        <?php if ($succes): ?>
            <div class="alerte-succes">✅ <?= htmlspecialchars($succes) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur">⚠️ <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- Formulaire de contact -->
            <div class="col-lg-7">
                <div class="form-card">
                    <h5 class="fw-bold mb-1" style="color:#1A3A5C">
                        <i class="bi bi-send me-2"></i>Contacter l'administrateur
                    </h5>
                    <p class="text-muted mb-4" style="font-size:13px">
                        Signalez un problème, posez une question ou suggérez une amélioration.
                        Votre message sera transmis directement à l'administrateur.
                    </p>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Type de demande <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="">— Sélectionner -</option>
                                <option value="Bug">🐛 Signalement de bug</option>
                                <option value="Question">❓ Question</option>
                                <option value="Amélioration">💡 Suggestion</option>
                                <option value="Accès">🔑 Problème d'accès</option>
                                <option value="Autre">📌 Autre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sujet <span class="text-danger">*</span></label>
                            <input type="text" name="sujet" class="form-control"
                                   placeholder="Résumez votre demande en une phrase" required
                                   value="<?= htmlspecialchars($_POST['sujet'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="5"
                                      placeholder="Décrivez votre problème ou votre question en détail..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>

                        <!-- Infos de l'expéditeur (pré-remplies automatiquement) -->
                        <div class="alert alert-info py-2 mb-3" style="font-size:13px">
                            <i class="bi bi-person-check me-1"></i>
                            Ce message sera envoyé en tant que
                            <strong><?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></strong>
                            (<?= htmlspecialchars($_SESSION['user_email']) ?>).
                        </div>

                        <button type="submit" class="btn btn-navy w-100">
                            <i class="bi bi-send me-1"></i> Envoyer à l'administrateur
                        </button>
                    </form>
                </div>
            </div>

            <!-- Infos et FAQ -->
            <div class="col-lg-5">

                <!-- Infos rapides -->
                <div class="table-card mb-4">
                    <div class="table-header">
                        <h5><i class="bi bi-telephone me-2"></i>Informations de contact</h5>
                    </div>
                    <div style="padding:20px">
                        <?php
                        $contacts = [
                            ['icone' => 'bi-envelope',  'couleur' => '#1A3A5C', 'bg' => '#e8eef5', 'label' => 'Email support',  'valeur' => 'support@zandoinou.com'],
                            ['icone' => 'bi-telephone', 'couleur' => '#198754', 'bg' => '#d1e7dd', 'label' => 'Téléphone',      'valeur' => '+226 XX XX XX XX'],
                            ['icone' => 'bi-geo-alt',   'couleur' => '#E63946', 'bg' => '#fde8e8', 'label' => 'Adresse',        'valeur' => 'Ouagadougou, Burkina Faso'],
                            ['icone' => 'bi-clock',     'couleur' => '#fd7e14', 'bg' => '#ffe5d0', 'label' => 'Disponibilité',  'valeur' => 'Lun – Ven : 8h à 17h'],
                        ];
                        foreach ($contacts as $c): ?>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width:38px; height:38px; background:<?= $c['bg'] ?>;
                                        border-radius:10px; display:flex; align-items:center;
                                        justify-content:center; flex-shrink:0;
                                        color:<?= $c['couleur'] ?>; font-size:17px">
                                <i class="bi <?= $c['icone'] ?>"></i>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size:11px"><?= $c['label'] ?></div>
                                <div class="fw-semibold" style="font-size:13px; color:#333"><?= $c['valeur'] ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- FAQ responsable -->
                <div class="table-card">
                    <div class="table-header">
                        <h5><i class="bi bi-question-circle me-2"></i>Questions fréquentes</h5>
                    </div>
                    <div style="padding:20px">
                        <?php
                        $faqs = [
                            ['q' => 'Comment changer mon mot de passe ?',
                             'r' => 'Allez dans Mon Profil → section "Modifier mon mot de passe".'],
                            ['q' => 'Je ne vois pas mes tâches assignées ?',
                             'r' => 'Vérifiez dans "Mes tâches" et contactez votre admin si elles manquent.'],
                            ['q' => 'Comment signaler une tâche terminée ?',
                             'r' => 'Dans "Mes tâches", changez le statut en "Terminé" et cliquez sur valider.'],
                        ];
                        foreach ($faqs as $index => $f): ?>
                        <div class="mb-3 <?= $index < count($faqs) - 1 ? 'pb-3' : '' ?>"
                             style="<?= $index < count($faqs) - 1 ? 'border-bottom:1px solid #f5f5f5' : '' ?>">
                            <div class="fw-semibold" style="font-size:13px; color:#1A3A5C; margin-bottom:4px">
                                ❓ <?= $f['q'] ?>
                            </div>
                            <div class="text-muted" style="font-size:12px; line-height:1.5">
                                <?= $f['r'] ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>