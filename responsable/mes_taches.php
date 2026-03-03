<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Utilisateur.php';
require_once '../classes/Tache.php';
require_once '../classes/Notification.php';

Utilisateur::exigerConnexion();
if (Utilisateur::estAdmin()) { header('Location: ../admin/taches.php'); exit; }
Utilisateur::exigerChangementMdp();

$db           = (new Database())->connect();
$tache        = new Tache($db);
$notification = new Notification($db);

$id_user = $_SESSION['user_id'];
$succes  = '';
$erreur  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'changer_statut') {
    $id_tache_post = (int)($_POST['id']    ?? 0);
    $nouveau_stat  = $_POST['statut']      ?? '';

    $verif = $db->prepare("SELECT id_tache FROM taches WHERE id_tache = ? AND id_responsable = ?");
    $verif->execute([$id_tache_post, $id_user]);

    if ($verif->fetch()) {
        $res = $tache->changerStatut($id_tache_post, $nouveau_stat, $id_user);

        if ($nouveau_stat === 'termine') {
            $t_info = $tache->getParId($id_tache_post);
            $admins = $db->query("SELECT id_user FROM utilisateurs WHERE role = 'admin'")->fetchAll();
            foreach ($admins as $admin) {
                $notification->creer(
                    $admin['id_user'],
                    " " . htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) .
                    " a marqué la tâche « {$t_info['titre']} » comme terminée."
                );
            }
        }

        $res['succes'] ? $succes = $res['message'] : $erreur = $res['message'];
    } else {
        $erreur = 'Action non autorisée.';
    }
}

$filtre_statut   = $_GET['statut']   ?? '';
$filtre_priorite = $_GET['priorite'] ?? '';

$sql    = "SELECT * FROM taches WHERE id_responsable = ?";
$params = [$id_user];

if ($filtre_statut === 'retard') {
    $sql .= " AND statut != 'termine' AND date_echeance < CURDATE()";
} elseif ($filtre_statut) {
    $sql .= " AND statut = ?";
    $params[] = $filtre_statut;
}
if ($filtre_priorite) {
    $sql .= " AND priorite = ?";
    $params[] = $filtre_priorite;
}
$sql .= " ORDER BY date_echeance IS NULL, date_echeance ASC, date_creation DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$mes_taches = $stmt->fetchAll();

$nb_notifs = $notification->compterNonLues($id_user);

$page_titre    = "Mes tâches";
$page_courante = 'mes_taches.php';
require_once '../includes/header_resp.php';
require_once '../includes/sidebar_resp.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page"><i class="bi bi-check2-square me-2"></i>Mes tâches</h1>
        <div class="topbar-droite">
            <span class="text-muted" style="font-size:14px">
                <?= count($mes_taches) ?> tâche(s) affichée(s)
            </span>
        </div>
    </div>

    <div class="page-content">

        <?php if ($succes): ?>
            <div class="alerte-succes"> <?= htmlspecialchars($succes) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur"> <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <div class="form-card mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <option value="">Toutes mes tâches</option>
                        <option value="en_attente" <?= $filtre_statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="en_cours"   <?= $filtre_statut === 'en_cours'   ? 'selected' : '' ?>>En cours</option>
                        <option value="termine"    <?= $filtre_statut === 'termine'    ? 'selected' : '' ?>>Terminé</option>
                        <option value="retard"     <?= $filtre_statut === 'retard'     ? 'selected' : '' ?>> En retard</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Priorité</label>
                    <select name="priorite" class="form-select">
                        <option value="">Toutes les priorités</option>
                        <option value="haute"   <?= $filtre_priorite === 'haute'   ? 'selected' : '' ?>>Haute</option>
                        <option value="normale" <?= $filtre_priorite === 'normale' ? 'selected' : '' ?>>Normale</option>
                        <option value="basse"   <?= $filtre_priorite === 'basse'   ? 'selected' : '' ?>>Basse</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-navy flex-fill">Filtrer</button>
                    <a href="mes_taches.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <?php if (empty($mes_taches)): ?>
            <div class="table-card">
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    Aucune tâche ne correspond à votre recherche.
                </div>
            </div>
        <?php else: ?>

        <div class="row g-3">
            <?php foreach ($mes_taches as $t): ?>
            <?php
            $retard  = $t['statut'] !== 'termine' && !empty($t['date_echeance']) && $t['date_echeance'] < date('Y-m-d');
            $aujourd = !empty($t['date_echeance']) && $t['date_echeance'] === date('Y-m-d');
            $bordure = $retard ? '#E63946' : ($t['priorite'] === 'haute' ? '#fd7e14' : '#1A3A5C');
            ?>
            <div class="col-md-6 col-xl-4">
                <div style="background:#fff; border-radius:12px; padding:20px;
                            box-shadow:0 2px 8px rgba(0,0,0,0.06);
                            border-left:4px solid <?= $bordure ?>;
                            height:100%">

                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge-statut badge-<?= $t['priorite'] ?>">
                            <?= ucfirst($t['priorite']) ?>
                        </span>
                        <?php if ($retard): ?>
                            <span class="badge bg-danger"> En retard</span>
                        <?php elseif ($aujourd): ?>
                            <span class="badge bg-warning text-dark">📅 Aujourd'hui</span>
                        <?php endif; ?>
                    </div>

                    <h6 class="fw-bold mb-1" style="color:#1A3A5C; font-size:15px">
                        <?= htmlspecialchars($t['titre']) ?>
                    </h6>

                    <?php if ($t['description']): ?>
                        <p class="text-muted mb-3" style="font-size:13px; line-height:1.4">
                            <?= htmlspecialchars(mb_strimwidth($t['description'], 0, 100, '…')) ?>
                        </p>
                    <?php else: ?>
                        <p class="text-muted mb-3" style="font-size:13px; font-style:italic">
                            Aucune description.
                        </p>
                    <?php endif; ?>

                    <?php if ($t['date_echeance']): ?>
                    <div class="mb-3" style="font-size:13px">
                        <i class="bi bi-calendar3 me-1 <?= $retard ? 'text-danger' : 'text-muted' ?>"></i>
                        <span class="<?= $retard ? 'text-danger fw-bold' : 'text-muted' ?>">
                            Échéance : <?= date('d/m/Y', strtotime($t['date_echeance'])) ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="changer_statut">
                        <input type="hidden" name="id" value="<?= $t['id_tache'] ?>">
                        <div class="d-flex gap-2 align-items-center">
                            <select name="statut" class="form-select form-select-sm flex-fill"
                                    style="font-size:13px">
                                <option value="en_attente" <?= $t['statut'] === 'en_attente' ? 'selected' : '' ?>>
                                    En attente
                                </option>
                                <option value="en_cours" <?= $t['statut'] === 'en_cours' ? 'selected' : '' ?>>
                                    En cours
                                </option>
                                <option value="termine" <?= $t['statut'] === 'termine' ? 'selected' : '' ?>>
                                    Terminé
                                </option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-navy" title="Mettre à jour">
                                <i class="bi bi-check2"></i>
                            </button>
                        </div>
                    </form>
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