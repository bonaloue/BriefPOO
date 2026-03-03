<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Utilisateur.php';
require_once '../classes/Tache.php';

Utilisateur::exigerConnexion();
Utilisateur::exigerAdmin();

$db    = (new Database())->connect();
$tache = new Tache($db);

$date_debut = $_GET['date_debut'] ?? '';
$date_fin   = $_GET['date_fin']   ?? '';

$sql = "SELECT h.*, 
               t.titre AS tache_titre,
               u.nom AS user_nom, u.prenom AS user_prenom, u.role AS user_role
        FROM historique h
        LEFT JOIN taches t ON h.id_tache = t.id_tache
        LEFT JOIN utilisateurs u ON h.id_responsable = u.id_user
        WHERE 1=1";
$params = [];

if ($date_debut) { $sql .= " AND DATE(h.date_action) >= ?"; $params[] = $date_debut; }
if ($date_fin)   { $sql .= " AND DATE(h.date_action) <= ?"; $params[] = $date_fin;   }

$sql .= " ORDER BY h.date_action DESC LIMIT 200";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$historique = $stmt->fetchAll();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="historique_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Tâche', 'Utilisateur', 'Rôle', 'Action', 'Date']);
    foreach ($historique as $h) {
        fputcsv($out, [
            //  CORRECTION : id_historique au lieu de id
            $h['id_historique'],
            $h['tache_titre'] ?? 'Tâche supprimée',
            $h['user_prenom'] . ' ' . $h['user_nom'],
            $h['user_role'] ?? '—',
            //  CORRECTION : description_action au lieu de action
            $h['description_action'],
            date('d/m/Y H:i', strtotime($h['date_action']))
        ]);
    }
    fclose($out);
    exit;
}

$page_titre    = "Historique";
$page_courante = 'historique.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page"><i class="bi bi-clock-history me-2"></i>Historique des actions</h1>
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>"
           class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
    </div>

    <div class="page-content">

        <div class="form-card mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Du</label>
                    <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Au</label>
                    <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-navy flex-fill">Filtrer</button>
                    <a href="historique.php" class="btn btn-outline-secondary">Actualiser</a>
                </div>
            </form>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h5><i class="bi bi-journal-text me-2"></i><?= count($historique) ?> entrée(s)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Utilisateur</th>
                            <th>Tâche concernée</th>
                            <th>Action effectuée</th>
                            <th>Date et heure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historique as $h): ?>
                        <tr>
                            <td class="text-muted"><?= $h['id_historique'] ?></td>
                            <td>
                                <?php if ($h['user_nom']): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar" style="width:28px;height:28px;font-size:11px;flex-shrink:0">
                                            <?= strtoupper(substr($h['user_prenom'], 0, 1) . substr($h['user_nom'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-size:13px;font-weight:600">
                                                <?= htmlspecialchars($h['user_prenom'] . ' ' . $h['user_nom']) ?>
                                            </div>
                                            <span class="badge badge-<?= $h['user_role'] ?> badge-statut" style="font-size:10px">
                                                <?= $h['user_role'] === 'admin' ? 'Admin' : 'Responsable' ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Système</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($h['tache_titre']): ?>
                                    <span class="fw-semibold" style="font-size:13px">
                                        <?= htmlspecialchars($h['tache_titre']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Tâche supprimée</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:13px"><?= htmlspecialchars($h['description_action']) ?></td>
                            <td class="text-muted" style="font-size:13px; white-space:nowrap">
                                <?= date('d/m/Y à H:i', strtotime($h['date_action'])) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($historique)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Aucune entrée dans l'historique.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>