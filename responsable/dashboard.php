<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Utilisateur.php';
require_once '../classes/Tache.php';
require_once '../classes/Notification.php';

Utilisateur::exigerConnexion();

if (Utilisateur::estAdmin()) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$db           = (new Database())->connect();
$tache        = new Tache($db);
$notification = new Notification($db);

$id_user    = $_SESSION['user_id'];
$mes_taches = $tache->getParResponsable($id_user);
$nb_notifs  = $notification->compterNonLues($id_user);
$notifs     = $notification->getNonLues($id_user);

// Statistiques personnelles (calculées en PHP, pas de SQL à corriger ici)
$stats = [
    'total'      => count($mes_taches),
    'en_attente' => count(array_filter($mes_taches, fn($t) => $t['statut'] === 'en_attente')),
    'en_cours'   => count(array_filter($mes_taches, fn($t) => $t['statut'] === 'en_cours')),
    'termine'    => count(array_filter($mes_taches, fn($t) => $t['statut'] === 'termine')),
    'en_retard'  => count(array_filter($mes_taches, fn($t) =>
        $t['statut'] !== 'termine' && !empty($t['date_echeance']) && $t['date_echeance'] < date('Y-m-d')
    )),
];

$urgentes = array_filter($mes_taches, fn($t) => $t['statut'] !== 'termine' && !empty($t['date_echeance']));
usort($urgentes, fn($a, $b) => strcmp($a['date_echeance'], $b['date_echeance']));
$urgentes = array_slice($urgentes, 0, 5);

$page_titre    = "Mon tableau de bord";
$page_courante = 'dashboard.php';
require_once '../includes/header_resp.php';
require_once '../includes/sidebar_resp.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page"><i class="bi bi-speedometer2 me-2"></i>Mon tableau de bord</h1>
        <div class="topbar-droite">

            <!-- Cloche notifications -->
            <?php if ($nb_notifs > 0): ?>
            <a href="notifications.php" class="notif-badge text-decoration-none">
                <i class="bi bi-bell-fill fs-5 text-warning"></i>
                <span class="dot"></span>
            </a>
            <?php endif; ?>

            <!-- ✅ Dropdown profil responsable dans la topbar -->
            <div class="dropdown">
                <div class="d-flex align-items-center gap-2 profil-topbar-btn"
                     data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['user_prenom'], 0, 1) . substr($_SESSION['user_nom'], 0, 1)) ?>
                    </div>
                    <div class="d-none d-md-block text-start">
                        <div style="font-size:13px;font-weight:600;color:#1A3A5C;line-height:1.2">
                            <?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?>
                        </div>
                        <div style="font-size:11px;color:#aaa">Responsable</div>
                    </div>
                    <i class="bi bi-chevron-down text-muted ms-1" style="font-size:11px"></i>
                </div>

                <ul class="dropdown-menu dropdown-menu-end shadow border-0 profil-dropdown-menu">
                    <!-- En-tête -->
                    <li>
                        <div class="profil-dd-header">
                            <div class="profil-dd-avatar">
                                <?= strtoupper(substr($_SESSION['user_prenom'], 0, 1) . substr($_SESSION['user_nom'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size:14px;color:#1A3A5C">
                                    <?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?>
                                </div>
                                <div class="text-muted" style="font-size:12px">
                                    <?= htmlspecialchars($_SESSION['user_email']) ?>
                                </div>
                                <span class="badge mt-1" style="background:#198754;font-size:10px;font-weight:600">
                                    Responsable
                                </span>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item profil-dd-lien" href="profil.php">
                            <i class="bi bi-person-circle"></i> Mon profil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item profil-dd-lien" href="profil.php#mdp">
                            <i class="bi bi-shield-lock"></i> Changer mon mot de passe
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item profil-dd-lien text-danger" href="../pages/logout.php">
                            <i class="bi bi-box-arrow-left"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>

<style>
    .profil-topbar-btn {
        cursor: pointer; padding: 6px 10px; border-radius: 8px; transition: 0.2s;
    }
    .profil-topbar-btn:hover { background: #f0f4f8; }
    .profil-topbar-btn::after { display: none !important; }
    .profil-dropdown-menu {
        border-radius: 12px !important; min-width: 230px;
        margin-top: 8px !important;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
    }
    .profil-dd-header {
        display: flex; align-items: center; gap: 12px; padding: 14px 16px 10px;
    }
    .profil-dd-avatar {
        width: 42px; height: 42px; background: #1A3A5C; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 15px; flex-shrink: 0;
    }
    .profil-dd-lien {
        display: flex !important; align-items: center; gap: 10px;
        font-size: 13px !important; padding: 9px 16px !important;
        color: #444 !important; transition: 0.15s;
    }
    .profil-dd-lien:hover { background: #f0f4f8 !important; color: #1A3A5C !important; }
    .profil-dd-lien i { font-size: 15px; width: 18px; color: #888; }
    .profil-dd-lien.text-danger { color: #E63946 !important; }
    .profil-dd-lien.text-danger i { color: #E63946; }
</style>

    <div class="page-content">

        <p class="text-muted mb-4">
            Bonjour, <strong><?= htmlspecialchars($_SESSION['user_prenom']) ?></strong> 👋
            - <?= date('l d F Y') ?>
        </p>

        <!-- Cartes statistiques personnelles -->
        <div class="row g-3 mb-4">

            <div class="col-md-6 col-xl-3">
                <div class="stat-card" style="border-left-color:#1A3A5C">
                    <div class="icone" style="background:#e8eef5;color:#1A3A5C">
                        <i class="bi bi-list-task"></i>
                    </div>
                    <div>
                        <div class="valeur"><?= $stats['total'] ?></div>
                        <div class="label">Mes tâches</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="stat-card" style="border-left-color:#f0ad4e">
                    <div class="icone" style="background:#fff3cd;color:#856404">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="valeur"><?= $stats['en_attente'] ?></div>
                        <div class="label">En attente</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="stat-card" style="border-left-color:#0d6efd">
                    <div class="icone" style="background:#cfe2ff;color:#0a4a9f">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <div>
                        <div class="valeur"><?= $stats['en_cours'] ?></div>
                        <div class="label">En cours</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="stat-card" style="border-left-color:#198754">
                    <div class="icone" style="background:#d1e7dd;color:#0f5132">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="valeur"><?= $stats['termine'] ?></div>
                        <div class="label">Terminées</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Alerte retard -->
        <?php if ($stats['en_retard'] > 0): ?>
        <div class="alert alert-danger d-flex align-items-center gap-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            <div>
                <strong><?= $stats['en_retard'] ?> tâche(s) en retard !</strong>
                Certaines de vos tâches ont dépassé leur date d'échéance.
                <a href="mes_taches.php?statut=retard" class="alert-link ms-2">Voir les tâches →</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- Tâches urgentes -->
            <div class="col-xl-7">
                <div class="table-card">
                    <div class="table-header">
                        <h5><i class="bi bi-alarm me-2"></i>Tâches à venir (par échéance)</h5>
                        <a href="mes_taches.php" class="btn btn-sm btn-navy rounded-pill px-3">
                            Voir tout <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Priorité</th>
                                    <th>Statut</th>
                                    <th>Échéance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($urgentes as $t): ?>
                                <?php $retard = $t['date_echeance'] < date('Y-m-d'); ?>
                                <tr class="<?= $retard ? 'table-danger' : '' ?>">
                                    <td class="fw-semibold"><?= htmlspecialchars($t['titre']) ?></td>
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
                                        <span class="<?= $retard ? 'text-danger fw-bold' : 'text-muted' ?>">
                                            <?= $retard ? '⚠️ ' : '' ?>
                                            <?= date('d/m/Y', strtotime($t['date_echeance'])) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($urgentes)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-check-circle text-success fs-3 d-block mb-2"></i>
                                            Aucune tâche urgente 🎉
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Notifications non lues -->
            <div class="col-xl-5">
                <div class="table-card h-100">
                    <div class="table-header">
                        <h5><i class="bi bi-bell me-2"></i>Mes notifications</h5>
                        <?php if ($nb_notifs > 0): ?>
                            <span class="badge bg-danger"><?= $nb_notifs ?> non lue(s)</span>
                        <?php endif; ?>
                    </div>
                    <div style="padding:8px 0; max-height:320px; overflow-y:auto">
                        <?php if (empty($notifs)): ?>
                            <p class="text-center text-muted py-4">
                                <i class="bi bi-check2-all text-success fs-3 d-block mb-2"></i>
                                Aucune nouvelle notification
                            </p>
                        <?php else: ?>
                            <?php foreach ($notifs as $n): ?>
                            <div style="padding:12px 20px; border-bottom:1px solid #f5f5f5">
                                <div style="font-size:13px; color:#333">
                                    <?= htmlspecialchars($n['message']) ?>
                                </div>
                                <div style="font-size:11px; color:#aaa; margin-top:4px">
                                    <?= date('d/m/Y à H:i', strtotime($n['date_envoi'])) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div style="padding:12px 20px">
                                <a href="notifications.php" class="btn btn-sm btn-outline-secondary w-100">
                                    Voir toutes mes notifications
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>