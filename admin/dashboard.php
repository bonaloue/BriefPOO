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

$stats        = $tache->getStats();
$en_retard    = $tache->getEnRetard();
$derniers     = array_slice($tache->getTout(), 0, 8);
$nb_notifs    = $notification->compterNonLues($_SESSION['user_id']);

// ✅ CORRECTION : table "utilisateurs" au lieu de "users"
$nb_utilisateurs = $db->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();

$page_titre    = "Tableau de bord";
$page_courante = 'dashboard.php';

// ── Données pour les graphiques ──────────────────────────────────────────────

// 1. Répartition par statut
$chart_statuts = [
    'En attente' => (int)$stats['en_attente'],
    'En cours'   => (int)$stats['en_cours'],
    'Terminées'  => (int)$stats['termine'],
    'En retard'  => (int)$stats['en_retard'],
];

// 2. Tâches par priorité
$prios = $db->query("
    SELECT priorite, COUNT(*) AS nb FROM taches GROUP BY priorite
")->fetchAll(PDO::FETCH_KEY_PAIR);
$chart_priorites = [
    'Haute'   => (int)($prios['haute']   ?? 0),
    'Normale' => (int)($prios['normale'] ?? 0),
    'Basse'   => (int)($prios['basse']   ?? 0),
];

// 3. Activité sur les 7 derniers jours
$activite = $db->query("
    SELECT DATE(date_creation) AS jour, COUNT(*) AS nb
    FROM taches
    WHERE date_creation >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(date_creation)
    ORDER BY jour ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$jours_labels = [];
$jours_data   = [];
for ($i = 6; $i >= 0; $i--) {
    $jour           = date('Y-m-d', strtotime("-$i days"));
    $jours_labels[] = date('d/m', strtotime($jour));
    $jours_data[]   = (int)($activite[$jour] ?? 0);
}

// 4. Top 5 responsables par nombre de tâches
$par_resp = $db->query("
    SELECT CONCAT(u.prenom, ' ', u.nom) AS nom, COUNT(t.id_tache) AS nb
    FROM utilisateurs u
    LEFT JOIN taches t ON t.id_responsable = u.id_user
    WHERE u.role = 'responsable'
    GROUP BY u.id_user
    ORDER BY nb DESC
    LIMIT 5
")->fetchAll();
$resp_labels = array_column($par_resp, 'nom');
$resp_data   = array_map('intval', array_column($par_resp, 'nb'));

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="main-content">

    <!-- Topbar avec dropdown profil -->
    <div class="topbar">
        <h1 class="titre-page"><i class="bi bi-speedometer2 me-2"></i>Tableau de bord</h1>
        <div class="topbar-droite">

            <!-- Cloche notifications -->
            <a href="notifications.php" class="notif-badge text-decoration-none">
                <i class="bi bi-bell fs-5 text-secondary"></i>
                <?php if ($nb_notifs > 0): ?>
                    <span class="dot"></span>
                <?php endif; ?>
            </a>

            <!-- ✅ AJOUT : Dropdown profil admin -->
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
                        <div style="font-size:11px;color:#aaa">Administrateur</div>
                    </div>
                    <i class="bi bi-chevron-down text-muted ms-1" style="font-size:11px"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 profil-dropdown-menu">
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
                                <span class="badge mt-1" style="background:#E63946;font-size:10px">Administrateur</span>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item profil-dd-lien" href="profil_admin.php">
                            <i class="bi bi-person-circle"></i> Mon profil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item profil-dd-lien" href="profil_admin.php#mdp">
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

    <div class="page-content">

        <!-- Message de bienvenue -->
        <p class="text-muted mb-4">
            Bonjour, <strong><?= htmlspecialchars($_SESSION['user_prenom']) ?></strong> 👋 
            - <?= date('l d F Y') ?>
        </p>

        <!-- Cartes statistiques -->
        <div class="row g-3 mb-4">

            <div class="col-md-6 col-xl-3">
                <div class="stat-card" style="border-left-color:#1A3A5C">
                    <div class="icone" style="background:#e8eef5; color:#1A3A5C">
                        <i class="bi bi-list-task"></i>
                    </div>
                    <div>
                        <div class="valeur"><?= $stats['total'] ?></div>
                        <div class="label">Tâches totales</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="stat-card" style="border-left-color:#f0ad4e">
                    <div class="icone" style="background:#fff3cd; color:#856404">
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
                    <div class="icone" style="background:#cfe2ff; color:#0a4a9f">
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
                    <div class="icone" style="background:#d1e7dd; color:#0f5132">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="valeur"><?= $stats['termine'] ?></div>
                        <div class="label">Terminées</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- 2ème rangée : retard + utilisateurs + notifs -->
        <div class="row g-3 mb-4">

            <div class="col-md-4">
                <div class="stat-card" style="border-left-color:#E63946">
                    <div class="icone" style="background:#fde8e8; color:#c0392b">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <div class="valeur"><?= $stats['en_retard'] ?></div>
                        <div class="label">Tâches en retard</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card" style="border-left-color:#6f42c1">
                    <div class="icone" style="background:#e9d8fd; color:#6f42c1">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="valeur"><?= $nb_utilisateurs ?></div>
                        <div class="label">Utilisateurs</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card" style="border-left-color:#fd7e14">
                    <div class="icone" style="background:#ffe5d0; color:#fd7e14">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                    <div>
                        <div class="valeur"><?= $nb_notifs ?></div>
                        <div class="label">Notifications non lues</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── GRAPHIQUES ─────────────────────────────────────────── -->
        <div class="row g-4 mb-4">

            <!-- Camembert : statuts -->
            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="table-header">
                        <h5><i class="bi bi-pie-chart-fill me-2"></i>Répartition des statuts</h5>
                    </div>
                    <div style="padding:20px;display:flex;align-items:center;
                                justify-content:center;min-height:230px">
                        <canvas id="chartStatuts" style="max-height:210px"></canvas>
                    </div>
                </div>
            </div>

            <!-- Barres : priorités -->
            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="table-header">
                        <h5><i class="bi bi-bar-chart-fill me-2"></i>Tâches par priorité</h5>
                    </div>
                    <div style="padding:20px;display:flex;align-items:center;
                                justify-content:center;min-height:230px">
                        <canvas id="chartPriorites" style="max-height:210px"></canvas>
                    </div>
                </div>
            </div>

            <!-- Barres horizontales : top responsables -->
            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="table-header">
                        <h5><i class="bi bi-person-lines-fill me-2"></i>Top responsables</h5>
                    </div>
                    <div style="padding:20px;display:flex;align-items:center;
                                justify-content:center;min-height:230px">
                        <canvas id="chartResponsables" style="max-height:210px"></canvas>
                    </div>
                </div>
            </div>

        </div>

        <!-- Courbe activité 7 jours -->
        <div class="table-card mb-4">
            <div class="table-header">
                <h5><i class="bi bi-graph-up me-2"></i>Activité des 7 derniers jours</h5>
                <span class="text-muted" style="font-size:13px">Tâches créées par jour</span>
            </div>
            <div style="padding:20px;height:180px">
                <canvas id="chartActivite"></canvas>
            </div>
        </div>

        <div class="row g-4">

            <!-- Tâches récentes -->
            <div class="col-xl-8">
                <div class="table-card">
                    <div class="table-header">
                        <h5><i class="bi bi-check2-square me-2"></i>Tâches récentes</h5>
                        <a href="taches.php" class="btn btn-sm btn-navy rounded-pill px-3">
                            Voir tout <i class="bi bi-arrow-right ms-1"></i>
                        </a>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($derniers as $t): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($t['titre']) ?></strong>
                                        <?php if (!empty($t['description'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($t['description'], 0, 50, '…')) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($t['responsable_nom']): ?>
                                            <i class="bi bi-person me-1 text-muted"></i>
                                            <?= htmlspecialchars($t['responsable_prenom'] . ' ' . $t['responsable_nom']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
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
                                            <?php
                                            $en_retard_cell = $t['statut'] !== 'termine' && $t['date_echeance'] < date('Y-m-d');
                                            ?>
                                            <span class="<?= $en_retard_cell ? 'text-danger fw-bold' : 'text-muted' ?>">
                                                <?= $en_retard_cell ? ' ' : '' ?>
                                                <?= date('d/m/Y', strtotime($t['date_echeance'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($derniers)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">Aucune tâche pour le moment.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tâches en retard -->
            <div class="col-xl-4">
                <div class="table-card h-100">
                    <div class="table-header">
                        <h5><i class="bi bi-exclamation-triangle text-danger me-2"></i>En retard</h5>
                        <span class="badge bg-danger"><?= count($en_retard) ?></span>
                    </div>
                    <div style="padding: 8px 0;">
                        <?php if (empty($en_retard)): ?>
                            <p class="text-center text-muted py-4">
                                <i class="bi bi-check-circle text-success fs-3 d-block mb-2"></i>
                                Aucune tâche en retard 
                            </p>
                        <?php else: ?>
                            <?php foreach ($en_retard as $t): ?>
                            <div style="padding: 12px 20px; border-bottom: 1px solid #f5f5f5;">
                                <div class="fw-semibold" style="font-size:14px; color:#1A3A5C">
                                    <?= htmlspecialchars($t['titre']) ?>
                                </div>
                                <div style="font-size:12px; color:#888; margin-top:3px;">
                                    <?php if ($t['responsable_nom']): ?>
                                        <i class="bi bi-person me-1"></i>
                                        <?= htmlspecialchars($t['responsable_prenom'] . ' ' . $t['responsable_nom']) ?> - 
                                    <?php endif; ?>
                                    <span class="text-danger">
                                        Échéance : <?= date('d/m/Y', strtotime($t['date_echeance'])) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div><!-- /page-content -->
</div><!-- /main-content -->

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.font.size   = 12;

// ── 1. Camembert : statuts ───────────────────────────────────────────────────
new Chart(document.getElementById('chartStatuts'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($chart_statuts)) ?>,
        datasets: [{
            data:            <?= json_encode(array_values($chart_statuts)) ?>,
            backgroundColor: ['#f0ad4e', '#0d6efd', '#198754', '#E63946'],
            borderWidth: 2, borderColor: '#fff', hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 12, boxWidth: 12, font: { size: 11 } } },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label} : ${ctx.parsed} tâche(s)` } }
        }
    }
});

// ── 2. Barres verticales : priorités ────────────────────────────────────────
new Chart(document.getElementById('chartPriorites'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($chart_priorites)) ?>,
        datasets: [{
            label: 'Tâches',
            data:  <?= json_encode(array_values($chart_priorites)) ?>,
            backgroundColor: ['#E63946', '#0d6efd', '#adb5bd'],
            borderRadius: 6, borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} tâche(s)` } }
        },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});

// ── 3. Barres horizontales : top responsables ────────────────────────────────
new Chart(document.getElementById('chartResponsables'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($resp_labels ?: ['Aucun responsable']) ?>,
        datasets: [{
            label: 'Tâches assignées',
            data:  <?= json_encode($resp_data ?: [0]) ?>,
            backgroundColor: '#1A3A5C',
            borderRadius: 4, borderSkipped: false,
        }]
    },
    options: {
        indexAxis: 'y', responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x} tâche(s)` } }
        },
        scales: {
            x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f0f0f0' } },
            y: { grid: { display: false } }
        }
    }
});

// ── 4. Courbe : activité 7 jours ─────────────────────────────────────────────
new Chart(document.getElementById('chartActivite'), {
    type: 'line',
    data: {
        labels: <?= json_encode($jours_labels) ?>,
        datasets: [{
            label: 'Tâches créées',
            data:  <?= json_encode($jours_data) ?>,
            borderColor: '#1A3A5C',
            backgroundColor: 'rgba(26,58,92,0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#1A3A5C',
            pointRadius: 4, pointHoverRadius: 6,
            fill: true, tension: 0.4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} tâche(s) créée(s)` } }
        },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
</body>
</html>