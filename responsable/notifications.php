<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Utilisateur.php';
require_once '../classes/Notification.php';

Utilisateur::exigerConnexion();
if (Utilisateur::estAdmin()) { header('Location: ../admin/notifications.php'); exit; }
Utilisateur::exigerChangementMdp();

$db           = (new Database())->connect();
$notification = new Notification($db);
$id_user      = $_SESSION['user_id'];
$succes       = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'marquer_lue') {
        // ✅ CORRECTION : colonne "id_notif" et "id_responsable" au lieu de "id" et "id_utilisateur"
        $verif = $db->prepare(
            "SELECT id_notif FROM notifications WHERE id_notif = ? AND id_responsable = ?"
        );
        $verif->execute([(int)$_POST['id'], $id_user]);
        if ($verif->fetch()) {
            $notification->marquerLue((int)$_POST['id']);
            $succes = 'Notification marquée comme lue.';
        }
    }

    if ($action === 'tout_lire') {
        $notification->marquerToutesLues($id_user);
        $succes = 'Toutes vos notifications ont été marquées comme lues.';
    }
}

$mes_notifs = $notification->getTout($id_user);
$nb_notifs  = $notification->compterNonLues($id_user);

$page_titre    = "Mes notifications";
$page_courante = 'notifications.php';
require_once '../includes/header_resp.php';
require_once '../includes/sidebar_resp.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page">
            <i class="bi bi-bell-fill me-2"></i>Mes notifications
            <?php if ($nb_notifs > 0): ?>
                <span class="badge bg-danger ms-2" style="font-size:14px"><?= $nb_notifs ?></span>
            <?php endif; ?>
        </h1>
        <?php if ($nb_notifs > 0): ?>
        <form method="POST">
            <input type="hidden" name="action" value="tout_lire">
            <button type="submit" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-check2-all me-1"></i> Tout marquer comme lu
            </button>
        </form>
        <?php endif; ?>
    </div>

    <div class="page-content">

        <?php if ($succes): ?>
            <div class="alerte-succes">✅ <?= htmlspecialchars($succes) ?></div>
        <?php endif; ?>

        <?php if (empty($mes_notifs)): ?>
            <div class="table-card">
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1 d-block mb-3"></i>
                    Vous n'avez aucune notification pour le moment.
                </div>
            </div>
        <?php else: ?>

        <div class="d-flex flex-column gap-3">
            <?php foreach ($mes_notifs as $n): ?>
            <div style="background:#fff; border-radius:12px; padding:18px 22px;
                        box-shadow:0 2px 8px rgba(0,0,0,0.06);
                        border-left:4px solid <?= $n['lu'] ? '#dee2e6' : '#1A3A5C' ?>;
                        opacity: <?= $n['lu'] ? '0.75' : '1' ?>">

                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="d-flex align-items-start gap-3 flex-fill">
                        <div style="font-size:22px; margin-top:2px">
                            <?= $n['lu'] ? '📭' : '📬' ?>
                        </div>
                        <div>
                            <div style="font-size:14px; color:#333; font-weight: <?= $n['lu'] ? 'normal' : '600' ?>">
                                <?= htmlspecialchars($n['message']) ?>
                            </div>
                            <div style="font-size:12px; color:#aaa; margin-top:5px">
                                <i class="bi bi-clock me-1"></i>
                                <?= date('d/m/Y à H:i', strtotime($n['date_envoi'])) ?>
                                <?php if (!$n['lu']): ?>
                                    <span class="badge bg-danger ms-2" style="font-size:10px">Non lu</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!$n['lu']): ?>
                    <form method="POST" style="flex-shrink:0">
                        <input type="hidden" name="action" value="marquer_lue">
                        <!-- ✅ CORRECTION : id_notif au lieu de id -->
                        <input type="hidden" name="id" value="<?= $n['id_notif'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-success" title="Marquer comme lue">
                            <i class="bi bi-check2"></i> Lu
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>