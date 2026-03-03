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
        $res = $tache->creer(
            trim($_POST['titre']           ?? ''),
            trim($_POST['description']     ?? ''),
            $_POST['statut']               ?? 'en_attente',
            $_POST['priorite']             ?? 'normale',
            (int)($_POST['id_responsable'] ?? 0) ?: null,
            $_POST['date_echeance']        ?? null,
            $_SESSION['user_id']
        );
        if ($res['succes']) {
            if (!empty($_POST['id_responsable'])) {
                $notification->creer(
                    (int)$_POST['id_responsable'],
                    "Vous avez été assigné à la tâche : « " . htmlspecialchars(trim($_POST['titre'])) . " »"
                );
            }
            $succes = $res['message'];
        } else {
            $erreur = $res['message'];
        }
    }

    if ($action === 'modifier') {
        $res = $tache->modifier(
            (int)($_POST['id']             ?? 0),
            trim($_POST['titre']           ?? ''),
            trim($_POST['description']     ?? ''),
            $_POST['statut']               ?? 'en_attente',
            $_POST['priorite']             ?? 'normale',
            (int)($_POST['id_responsable'] ?? 0) ?: null,
            $_POST['date_echeance']        ?? null,
            $_SESSION['user_id']
        );
        $res['succes'] ? $succes = $res['message'] : $erreur = $res['message'];
    }

    if ($action === 'supprimer') {
        $res = $tache->supprimer((int)($_POST['id'] ?? 0), $_SESSION['user_id']);
        $res['succes'] ? $succes = $res['message'] : $erreur = $res['message'];
    }

    if ($action === 'changer_statut') {
        $res = $tache->changerStatut(
            (int)($_POST['id'] ?? 0),
            $_POST['statut']   ?? 'en_attente',
            $_SESSION['user_id']
        );
        $res['succes'] ? $succes = $res['message'] : $erreur = $res['message'];
    }
}

$filtre_statut      = $_GET['statut']      ?? '';
$filtre_responsable = (int)($_GET['responsable'] ?? 0);
$filtre_priorite    = $_GET['priorite']    ?? '';

$sql = "SELECT t.*, u.nom AS responsable_nom, u.prenom AS responsable_prenom
        FROM taches t
        LEFT JOIN utilisateurs u ON t.id_responsable = u.id_user
        WHERE 1=1";
$params = [];

if ($filtre_statut) {
    $sql .= " AND t.statut = ?";
    $params[] = $filtre_statut;
}
if ($filtre_responsable) {
    $sql .= " AND t.id_responsable = ?";
    $params[] = $filtre_responsable;
}
if ($filtre_priorite) {
    $sql .= " AND t.priorite = ?";
    $params[] = $filtre_priorite;
}
$sql .= " ORDER BY t.date_creation DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$taches = $stmt->fetchAll();

$users = $db->query("SELECT id_user, nom, prenom, role FROM utilisateurs ORDER BY nom")->fetchAll();

$stats = $tache->getStats();

$page_titre    = "Gestion des tâches";
$page_courante = 'taches.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page"><i class="bi bi-check2-square me-2"></i>Tâches</h1>
        <button class="btn btn-navy rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalCreer">
            <i class="bi bi-plus-lg me-1"></i> Nouvelle tâche
        </button>
    </div>

    <div class="page-content">

        <?php if ($succes): ?>
            <div class="alerte-succes"> <?= htmlspecialchars($succes) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur"> <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <?php
            $mini_stats = [
                ['label' => 'Total',       'val' => $stats['total'],      'couleur' => '#1A3A5C', 'bg' => '#e8eef5', 'filtre' => ''],
                ['label' => 'En attente',  'val' => $stats['en_attente'], 'couleur' => '#856404', 'bg' => '#fff3cd', 'filtre' => 'en_attente'],
                ['label' => 'En cours',    'val' => $stats['en_cours'],   'couleur' => '#0a4a9f', 'bg' => '#cfe2ff', 'filtre' => 'en_cours'],
                ['label' => 'Terminées',   'val' => $stats['termine'],    'couleur' => '#0f5132', 'bg' => '#d1e7dd', 'filtre' => 'termine'],
                ['label' => 'En retard',   'val' => $stats['en_retard'],  'couleur' => '#c0392b', 'bg' => '#fde8e8', 'filtre' => 'retard'],
            ];
            foreach ($mini_stats as $s):
            ?>
            <div class="col">
                <a href="<?= $s['filtre'] ? '?statut=' . $s['filtre'] : 'taches.php' ?>"
                   class="stat-card text-decoration-none"
                   style="border-left-color:<?= $s['couleur'] ?>">
                    <div>
                        <div class="valeur" style="color:<?= $s['couleur'] ?>"><?= $s['val'] ?></div>
                        <div class="label"><?= $s['label'] ?></div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="form-card mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" <?= $filtre_statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="en_cours"   <?= $filtre_statut === 'en_cours'   ? 'selected' : '' ?>>En cours</option>
                        <option value="termine"    <?= $filtre_statut === 'termine'    ? 'selected' : '' ?>>Terminé</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priorité</label>
                    <select name="priorite" class="form-select">
                        <option value="">Toutes les priorités</option>
                        <option value="haute"   <?= $filtre_priorite === 'haute'   ? 'selected' : '' ?>>Haute</option>
                        <option value="normale" <?= $filtre_priorite === 'normale' ? 'selected' : '' ?>>Normale</option>
                        <option value="basse"   <?= $filtre_priorite === 'basse'   ? 'selected' : '' ?>>Basse</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Responsable</label>
                    <select name="responsable" class="form-select">
                        <option value="">Tous les responsables</option>
                        <?php foreach ($users as $u): ?>
                            <!--  CORRECTION : id_user au lieu de id -->
                            <option value="<?= $u['id_user'] ?>" <?= $filtre_responsable === $u['id_user'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-navy flex-fill">Filtrer</button>
                    <a href="taches.php" class="btn btn-outline-secondary">Actualiser</a>
                </div>
            </form>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h5><i class="bi bi-list-task me-2"></i><?= count($taches) ?> tâche(s)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Responsable</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Échéance</th>
                            <th>Créée le</th>
                            <th>Changer statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($taches as $t): ?>
                        <?php $en_retard_cell = $t['statut'] !== 'termine' && !empty($t['date_echeance']) && $t['date_echeance'] < date('Y-m-d'); ?>
                        <tr class="<?= $en_retard_cell ? 'table-danger' : '' ?>">
                            <td>
                                <strong><?= htmlspecialchars($t['titre']) ?></strong>
                                <?php if (!empty($t['description'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($t['description'], 0, 60, '…')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['responsable_nom']): ?>
                                    <i class="bi bi-person me-1 text-muted"></i>
                                    <?= htmlspecialchars($t['responsable_prenom'] . ' ' . $t['responsable_nom']) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-statut badge-<?= $t['priorite'] ?>">
                                    <?= ucfirst($t['priorite']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-statut badge-<?= $t['statut'] ?>">
                                    <?= match($t['statut']) {
                                        'en_attente' => 'En attente',
                                        'en_cours'   => 'En cours',
                                        'termine'    => 'Terminé',
                                        default      => $t['statut']
                                    } ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($t['date_echeance']): ?>
                                    <span class="<?= $en_retard_cell ? 'text-danger fw-bold' : 'text-muted' ?>">
                                        <?= $en_retard_cell ? ' ' : '' ?>
                                        <?= date('d/m/Y', strtotime($t['date_echeance'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= date('d/m/Y', strtotime($t['date_creation'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="changer_statut">
                                    <input type="hidden" name="id" value="<?= $t['id_tache'] ?>">
                                    <select name="statut" class="form-select form-select-sm" style="width:130px;display:inline-block"
                                            onchange="this.form.submit()">
                                        <option value="en_attente" <?= $t['statut'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                        <option value="en_cours"   <?= $t['statut'] === 'en_cours'   ? 'selected' : '' ?>>En cours</option>
                                        <option value="termine"    <?= $t['statut'] === 'termine'    ? 'selected' : '' ?>>Terminé</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-secondary" title="Modifier"
                                        data-bs-toggle="modal" data-bs-target="#modalModifier"
                                        data-id="<?= $t['id_tache'] ?>"
                                        data-titre="<?= htmlspecialchars($t['titre']) ?>"
                                        data-description="<?= htmlspecialchars($t['description'] ?? '') ?>"
                                        data-statut="<?= $t['statut'] ?>"
                                        data-priorite="<?= $t['priorite'] ?>"
                                        data-responsable="<?= $t['id_responsable'] ?? '' ?>"
                                        data-echeance="<?= $t['date_echeance'] ?? '' ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('Supprimer cette tâche définitivement ?')">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id" value="<?= $t['id_tache'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($taches)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Aucune tâche trouvée.</td></tr>
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
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Nouvelle tâche</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" name="titre" class="form-control" placeholder="Titre de la tâche" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Description détaillée (optionnel)"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="en_attente">En attente</option>
                            <option value="en_cours">En cours</option>
                            <option value="termine">Terminé</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Priorité</label>
                        <select name="priorite" class="form-select">
                            <option value="normale" selected>Normale</option>
                            <option value="haute">Haute</option>
                            <option value="basse">Basse</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date d'échéance</label>
                        <input type="date" name="date_echeance" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Responsable assigné</label>
                        <select name="id_responsable" class="form-select">
                            <option value="">— Non assigné -</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id_user'] ?>">
                                    <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                                    (<?= $u['role'] === 'admin' ? 'Admin' : 'Responsable' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-navy">Créer la tâche</button>
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
                <input type="hidden" name="id" id="m_id">
                <div class="modal-header" style="background:var(--navy);color:#fff">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Modifier la tâche</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" name="titre" id="m_titre" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="m_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select name="statut" id="m_statut" class="form-select">
                            <option value="en_attente">En attente</option>
                            <option value="en_cours">En cours</option>
                            <option value="termine">Terminé</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Priorité</label>
                        <select name="priorite" id="m_priorite" class="form-select">
                            <option value="basse">Basse</option>
                            <option value="normale">Normale</option>
                            <option value="haute">Haute</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date d'échéance</label>
                        <input type="date" name="date_echeance" id="m_echeance" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Responsable</label>
                        <select name="id_responsable" id="m_responsable" class="form-select">
                            <option value="">— Non assigné -</option>
                            <?php foreach ($users as $u): ?>
                                <!--  CORRECTION : id_user au lieu de id -->
                                <option value="<?= $u['id_user'] ?>">
                                    <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                                    (<?= $u['role'] === 'admin' ? 'Admin' : 'Responsable' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
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
    document.getElementById('m_id').value          = b.dataset.id;
    document.getElementById('m_titre').value       = b.dataset.titre;
    document.getElementById('m_description').value = b.dataset.description;
    document.getElementById('m_statut').value      = b.dataset.statut;
    document.getElementById('m_priorite').value    = b.dataset.priorite;
    document.getElementById('m_echeance').value    = b.dataset.echeance;
    document.getElementById('m_responsable').value = b.dataset.responsable;
});
</script>
</body>
</html>