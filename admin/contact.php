<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Utilisateur.php';
require_once '../classes/Notification.php';

Utilisateur::exigerConnexion();
Utilisateur::exigerAdmin();

$db           = (new Database())->connect();
$notification = new Notification($db);
$nb_notifs    = $notification->compterNonLues($_SESSION['user_id']);

$succes = '';
$erreur = '';

// Traitement du formulaire de support
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sujet   = trim($_POST['sujet']   ?? '');
    $message = trim($_POST['message'] ?? '');
    $type    = trim($_POST['type']    ?? '');

    if (empty($sujet) || empty($message) || empty($type)) {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        // Enregistrement en notification interne vers l'admin lui-même (ou un super admin)
        $notification->creer(
            $_SESSION['user_id'],
            "📩 Demande de support [{$type}] : {$sujet} - {$message}"
        );
        $succes = 'Votre demande de support a été enregistrée avec succès.';
    }
}

$page_titre = "Contact / Support";
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page"><i class="bi bi-headset me-2"></i>Contact / Support</h1>
        <div class="topbar-droite">
            <div class="notif-badge">
                <i class="bi bi-bell fs-5 text-secondary"></i>
                <?php if ($nb_notifs > 0): ?>
                    <span class="dot"></span>
                <?php endif; ?>
            </div>
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['user_prenom'], 0, 1) . substr($_SESSION['user_nom'], 0, 1)) ?>
            </div>
        </div>
    </div>

    <div class="page-content">

        <?php if ($succes): ?>
            <div class="alerte-succes"> <?= htmlspecialchars($succes) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur"> <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- Formulaire de contact -->
            <div class="col-lg-7">
                <div class="form-card">
                    <h5 class="fw-bold mb-1" style="color:#1A3A5C">
                        <i class="bi bi-send me-2"></i>Envoyer une demande
                    </h5>
                    <p class="text-muted mb-4" style="font-size:13px">
                        Signalez un bug, posez une question ou suggérez une amélioration.
                    </p>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Type de demande <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="">Sélectionner</option>
                                <option value="Bug">Signalement de bug</option>
                                <option value="Question">Question technique</option>
                                <option value="Amélioration">Suggestion d'amélioration</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sujet <span class="text-danger">*</span></label>
                            <input type="text" name="sujet" class="form-control"
                                   placeholder="Résumez votre demande en une phrase" required
                                   value="<?= htmlspecialchars($_POST['sujet'] ?? '') ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="5"
                                      placeholder="Décrivez votre demande en détail..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-navy w-100">
                            <i class="bi bi-send me-1"></i> Envoyer la demande
                        </button>
                    </form>
                </div>
            </div>

            <!-- Informations de contact -->
            <div class="col-lg-5">

                <!-- Infos rapides -->
                <div class="table-card mb-4">
                    <div class="table-header">
                        <h5><i class="bi bi-telephone me-2"></i>Informations de contact</h5>
                    </div>
                    <div style="padding:20px">
                        <?php
                        $contacts = [
                            ['icone' => 'bi-envelope',     'couleur' => '#1A3A5C', 'bg' => '#e8eef5', 'label' => 'Email support',    'valeur' => 'support@zandoinou.com'],
                            ['icone' => 'bi-telephone',    'couleur' => '#198754', 'bg' => '#d1e7dd', 'label' => 'Téléphone',        'valeur' => '+226 XX XX XX XX'],
                            ['icone' => 'bi-geo-alt',      'couleur' => '#E63946', 'bg' => '#fde8e8', 'label' => 'Adresse',          'valeur' => 'Ouagadougou, Burkina Faso'],
                            ['icone' => 'bi-clock',        'couleur' => '#fd7e14', 'bg' => '#ffe5d0', 'label' => 'Disponibilité',    'valeur' => 'Lun – Ven : 8h à 17h'],
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
                                <div class="fw-semibold" style="font-size:13px; color:#333">
                                    <?= $c['valeur'] ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- FAQ rapide -->
                <div class="table-card">
                    <div class="table-header">
                        <h5><i class="bi bi-question-circle me-2"></i>FAQ</h5>
                    </div>
                    <div style="padding:20px">
                        <?php
                        $faqs = [
                            ['q' => 'Comment réinitialiser un mot de passe ?',
                             'r' => 'Allez dans Utilisateurs → cliquez sur l\'icône clé 🔑 à côté du compte concerné.'],
                            ['q' => 'Comment configurer une règle automatique ?',
                             'r' => 'Allez dans Automatisation → Nouvelle règle, puis définissez une condition SQL et une action.'],
                            ['q' => 'Comment exporter l\'historique ?',
                             'r' => 'Dans la page Historique, cliquez sur le bouton "Export CSV" en haut à droite.'],
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