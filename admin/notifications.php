<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Utilisateur.php';
require_once '../classes/Notification.php';

Utilisateur::exigerConnexion();
Utilisateur::exigerAdmin();

$db           = (new Database())->connect();
$notification = new Notification($db);

$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'marquer_lue') {
        $notification->marquerLue((int)($_POST['id'] ?? 0));
        $succes = 'Notification marquée comme lue.';
    }

    if ($action === 'supprimer') {
        $notification->supprimer((int)($_POST['id'] ?? 0));
        $succes = 'Notification supprimée.';
    }

    if ($action === 'tout_lire') {
        // ✅ CORRECTION : table "notifications" (inchangée, déjà correcte)
        $db->query("UPDATE notifications SET lu = 1");
        $succes = 'Toutes les notifications marquées comme lues.';
    }

    if ($action === 'envoyer') {
        $id_dest = (int)($_POST['id_destinataire'] ?? 0);
        $msg     = trim($_POST['message'] ?? '');
        if ($id_dest && $msg) {
            $notification->creer($id_dest, $msg);
            $succes = 'Notification envoyée avec succès.';
        }
    }
}

$notifications = $notification->getToutAdmin();

// ✅ CORRECTION : table "utilisateurs" et colonne "id_user" au lieu de "users" et "id"
$users       = $db->query("SELECT id_user, nom, prenom FROM utilisateurs ORDER BY nom")->fetchAll();
$nb_non_lues = $db->query("SELECT COUNT(*) FROM notifications WHERE lu = 0")->fetchColumn();

$page_titre = "Notifications";
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page">
            <i class="bi bi-bell-fill me-2"></i>Notifications
            <?php if ($nb_non_lues > 0): ?>
                <span class="badge bg-danger ms-2" style="font-size:14px"><?= $nb_non_lues ?></span>
            <?php endif; ?>
        </h1>
        <div class="d-flex gap-2">
            <?php if ($nb_non_lues > 0): ?>
            <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="tout_lire">
                <button type="submit" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-check2-all me-1"></i> Tout marquer comme lu
                </button>
            </form>
            <?php endif; ?>
            <button class="btn btn-navy rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalEnvoyer">
                <i class="bi bi-send me-1"></i> Envoyer une notification
            </button>
        </div>
    </div>

    <div class="page-content">

        <?php if ($succes): ?>
            <div class="alerte-succes">✅ <?= htmlspecialchars($succes) ?></div>
        <?php endif; ?>

        <div class="table-card">
            <div class="table-header">
                <h5><i class="bi bi-bell me-2"></i><?= count($notifications) ?> notification(s)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Destinataire</th>
                            <th>Message</th>
                            <th>État</th>
                            <th>Date d'envoi</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $n): ?>
                        <tr class="<?= !$n['lu'] ? 'fw-semibold' : '' ?>">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar" style="width:28px;height:28px;font-size:11px;flex-shrink:0">
                                        <?= strtoupper(substr($n['user_prenom'], 0, 1) . substr($n['user_nom'], 0, 1)) ?>
                                    </div>
                                    <?= htmlspecialchars($n['user_prenom'] . ' ' . $n['user_nom']) ?>
                                </div>
                            </td>
                            <td style="font-size:13px; max-width:400px">
                                <?= htmlspecialchars($n['message']) ?>
                            </td>
                            <td>
                                <?php if ($n['lu']): ?>
                                    <span class="badge bg-light text-muted border">Lu</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Non lu</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="font-size:13px; white-space:nowrap">
                                <?= date('d/m/Y à H:i', strtotime($n['date_envoi'])) ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <?php if (!$n['lu']): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="marquer_lue">
                                        <!-- ✅ CORRECTION : id_notif au lieu de id -->
                                        <input type="hidden" name="id" value="<?= $n['id_notif'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Marquer comme lue">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('Supprimer cette notification ?')">
                                        <input type="hidden" name="action" value="supprimer">
                                        <!-- ✅ CORRECTION : id_notif au lieu de id -->
                                        <input type="hidden" name="id" value="<?= $n['id_notif'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($notifications)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Aucune notification.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Envoyer notification manuelle -->
<div class="modal fade" id="modalEnvoyer" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="envoyer">
                <div class="modal-header" style="background:var(--navy);color:#fff">
                    <h5 class="modal-title"><i class="bi bi-send me-2"></i>Envoyer une notification</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Destinataire <span class="text-danger">*</span></label>
                        <select name="id_destinataire" class="form-select" required>
                            <option value="">Sélectionner un utilisateur</option>
                            <?php foreach ($users as $u): ?>
                                <!-- ✅ CORRECTION : id_user au lieu de id -->
                                <option value="<?= $u['id_user'] ?>">
                                    <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="3"
                                  placeholder="Votre message..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-navy">Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>