<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Utilisateur.php';
require_once '../classes/Tache.php';
require_once '../classes/Notification.php';

Utilisateur::exigerConnexion();
Utilisateur::exigerAdmin();

$db           = (new Database())->connect();
$tache        = new Tache($db);
$notification = new Notification($db);

$succes = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'creer') {
        $nom       = trim($_POST['nom_regle']     ?? '');
        $condition = trim($_POST['condition_sql'] ?? '');
        $act       = trim($_POST['action_regle']  ?? '');

        if (empty($nom) || empty($condition) || empty($act)) {
            $erreur = 'Tous les champs sont obligatoires.';
        } else {
            $db->prepare(
                "INSERT INTO automatisation (nom_regle, condition_auto, action_auto, actif) VALUES (?, ?, ?, 1)"
            )->execute([$nom, $condition, $act]);
            $succes = 'Règle créée avec succès.';
        }
    }

    if ($action === 'modifier') {
        $db->prepare(
            "UPDATE automatisation SET nom_regle = ?, condition_auto = ?, action_auto = ? WHERE id_auto = ?"
        )->execute([
            trim($_POST['nom_regle']     ?? ''),
            trim($_POST['condition_sql'] ?? ''),
            trim($_POST['action_regle']  ?? ''),
            (int)($_POST['id']           ?? 0)
        ]);
        $succes = 'Règle modifiée avec succès.';
    }

    if ($action === 'toggle') {
        $id   = (int)($_POST['id']   ?? 0);
        $etat = (int)($_POST['actif'] ?? 0);
        $db->prepare("UPDATE automatisation SET actif = ? WHERE id_auto = ?")->execute([$etat ? 0 : 1, $id]);
        $succes = $etat ? 'Règle désactivée.' : 'Règle activée.';
    }

    if ($action === 'supprimer') {
        $db->prepare("DELETE FROM automatisation WHERE id_auto = ?")->execute([(int)($_POST['id'] ?? 0)]);
        $succes = 'Règle supprimée.';
    }

    if ($action === 'executer') {
        $nb_notifs = 0;

        $regles = $db->query("SELECT * FROM automatisation WHERE actif = 1")->fetchAll();

        foreach ($regles as $regle) {
            $sql_taches = "SELECT t.*, u.nom, u.prenom 
                           FROM taches t
                           LEFT JOIN utilisateurs u ON t.id_responsable = u.id_user
                           WHERE {$regle['condition_auto']}";
            try {
                $taches_cibles = $db->query($sql_taches)->fetchAll();
            } catch (\PDOException $e) {
                continue;
            }

            foreach ($taches_cibles as $t) {
                if (!$t['id_responsable']) continue;

                $deja_notifie = $db->prepare(
                    "SELECT COUNT(*) FROM notifications 
                     WHERE id_responsable = ? 
                       AND message LIKE ? 
                       AND DATE(date_envoi) = CURDATE()"
                );
                $deja_notifie->execute([$t['id_responsable'], '%' . $t['titre'] . '%']);

                if ($deja_notifie->fetchColumn() == 0) {
                    $message = match($regle['action_auto']) {
                        'creer_notification'    => " Rappel automatique : la tâche « {$t['titre']} » est en retard ou proche de son échéance.",
                        'changer_statut_retard' => "🔴 La tâche « {$t['titre']} » a été automatiquement signalée en retard.",
                        default                 => "📌 Action automatique [{$regle['action_auto']}] pour la tâche « {$t['titre']} »."
                    };

                    $notification->creer($t['id_responsable'], $message);
                    $tache->ajouterHistorique($t['id_tache'], $_SESSION['user_id'], "Automatisation déclenchée : {$regle['nom_regle']}");
                    $nb_notifs++;
                }
            }
        }

        $succes = "Automatisation exécutée avec succès. $nb_notifs notification(s) envoyée(s).";
    }
}

$regles = $db->query(
    "SELECT * FROM automatisation ORDER BY date_creation DESC"
)->fetchAll();

$libelles_action = [
    "creer_notification"    => "Envoyer une notification de retard au responsable assigné",
    "changer_statut_retard" => "Marquer automatiquement comme terminé après 30 jours de retard",
];

$page_titre    = "Automatisation";
$page_courante = "automatisation.php";
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page"><i class="bi bi-gear-wide-connected me-2"></i>Automatisation</h1>
        <div class="d-flex gap-2">
            <form method="POST" onsubmit="return confirm('Exécuter les règles actives maintenant ?')">
                <input type="hidden" name="action" value="executer">
                <button type="submit" class="btn btn-red rounded-pill px-4">
                    <i class="bi bi-play-fill me-1"></i> Exécuter maintenant
                </button>
            </form>
            <button class="btn btn-navy rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalCreer">
                <i class="bi bi-plus-lg me-1"></i> Nouvelle règle
            </button>
        </div>
    </div>

    <div class="page-content">

        <?php if ($succes): ?>
            <div class="alerte-succes"> <?= htmlspecialchars($succes) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur"> <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <!-- <div class="form-card mb-4" style="border-left:4px solid #1A3A5C">
            <h6 class="fw-bold text-navy mb-2"><i class="bi bi-info-circle me-2"></i>Comment fonctionne l'automatisation ?</h6>
            <p class="text-muted mb-2" style="font-size:14px">
                Chaque règle active est évaluée lors de l'exécution (manuelle ou via cron job).
                Si la <strong>condition SQL</strong> retourne des tâches, l'<strong>action</strong> correspondante est exécutée pour chaque tâche trouvée.
            </p>
            <p class="text-muted mb-0" style="font-size:13px">
                <strong>Exemple de condition :</strong>
                <code>statut != 'termine' AND date_echeance &lt; CURDATE()</code>
                - cible toutes les tâches en retard non terminées.
            </p>
        </div> -->

        <div class="table-card">
            <div class="table-header">
                <h5><i class="bi bi-list-check me-2"></i><?= count($regles) ?> règle(s)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nom de la règle</th>
                            <!-- <th>Condition SQL</th> -->
                            <th>Action</th>
                            <th>État</th>
                            <th>Créée le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($regles as $r): ?>
                        <tr class="<?= !$r['actif'] ? 'text-muted' : '' ?>">
                            <td class="fw-semibold"><?= htmlspecialchars($r['nom_regle']) ?></td>
                            <!-- <td>
                                <code style="font-size:12px; background:#f5f5f5; padding:3px 6px; border-radius:4px">
                                    <?= htmlspecialchars($r['condition_auto']) ?>
                                </code>
                            </td> -->
                            <td>
                                <span class="badge bg-dark" style="font-size:11px;white-space:normal;text-align:left;line-height:1.4">
                                    <?= htmlspecialchars($libelles_action[$r['action_auto']] ?? $r['action_auto']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($r['actif']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="bi bi-pause-circle me-1"></i>Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= date('d/m/Y', strtotime($r['date_creation'])) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $r['id_auto'] ?>">
                                        <input type="hidden" name="actif" value="<?= $r['actif'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $r['actif'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                                title="<?= $r['actif'] ? 'Désactiver' : 'Activer' ?>">
                                            <i class="bi bi-<?= $r['actif'] ? 'pause' : 'play' ?>-fill"></i>
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-outline-secondary" title="Modifier"
                                        data-bs-toggle="modal" data-bs-target="#modalModifier"
                                        data-id="<?= $r['id_auto'] ?>"
                                        data-nom="<?= htmlspecialchars($r['nom_regle']) ?>"
                                        data-condition="<?= htmlspecialchars($r['condition_auto']) ?>"
                                        data-action="<?= htmlspecialchars($r['action_auto']) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('Supprimer cette règle définitivement ?')">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id" value="<?= $r['id_auto'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($regles)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Aucune règle configurée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCreer" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="creer">
                <div class="modal-header" style="background:var(--navy);color:#fff">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Nouvelle règle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom de la règle <span class="text-danger">*</span></label>
                        <input type="text" name="nom_regle" class="form-control" placeholder="Ex : Alerte tâche en retard" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Condition SQL <span class="text-danger">*</span></label>
                        <input type="text" name="condition_sql" class="form-control font-monospace"
                               placeholder="statut != 'termine' AND date_echeance < CURDATE()" required>
                        <div class="form-text">Condition appliquée sur la table <code>taches</code>.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Action <span class="text-danger">*</span></label>
                        <input type="text" name="action_regle" class="form-control"
                               placeholder="Ex : Envoyer une notification de retard au responsable assigné" required>
                        <div class="form-text">Décrivez l'action à effectuer lorsque la condition est remplie.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-navy">Créer la règle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalModifier" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="modifier">
                <input type="hidden" name="id" id="r_id">
                <div class="modal-header" style="background:var(--navy);color:#fff">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Modifier la règle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom de la règle</label>
                        <input type="text" name="nom_regle" id="r_nom" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Condition SQL</label>
                        <input type="text" name="condition_sql" id="r_condition" class="form-control font-monospace" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Action</label>
                        <input type="text" name="action_regle" id="r_action" class="form-control"
                               placeholder="Description de l'action" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-navy">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('modalModifier').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    document.getElementById('r_id').value        = b.dataset.id;
    document.getElementById('r_nom').value       = b.dataset.nom;
    document.getElementById('r_condition').value = b.dataset.condition;
    document.getElementById('r_action').value    = b.dataset.action;
});
</script>
</body>
</html>