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

$page_titre = "À propos du système";
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page"><i class="bi bi-info-circle me-2"></i>À propos du système</h1>
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

        <div style="background: linear-gradient(135deg, #1A3A5C, #2d6a9f);
                    border-radius: 16px; padding: 50px 40px; color: #fff;
                    margin-bottom: 30px; position: relative; overflow: hidden;">
            <div style="position:absolute; top:-40px; right:-40px; width:200px; height:200px;
                        background:rgba(255,255,255,0.05); border-radius:50%"></div>
            <div style="position:absolute; bottom:-60px; right:80px; width:150px; height:150px;
                        background:rgba(255,255,255,0.05); border-radius:50%"></div>
            <div style="position:relative; z-index:1">
                <div style="font-size:48px; margin-bottom:16px">⚙️</div>
                <h2 style="font-size:28px; font-weight:700; margin-bottom:10px">
                    Système d'Automatisation des Tâches
                </h2>
                <p style="font-size:15px; opacity:0.85; max-width:600px; margin-bottom:20px">
                    Une solution web complète développée pour Zandoinou Consulting,
                    permettant de gérer, automatiser et suivre les tâches de l'entreprise
                    de manière efficace et centralisée.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <span style="background:rgba(255,255,255,0.15); border-radius:20px;
                                 padding:6px 16px; font-size:13px">
                        <i class="bi bi-code-slash me-1"></i> PHP 8.x
                    </span>
                    <span style="background:rgba(255,255,255,0.15); border-radius:20px;
                                 padding:6px 16px; font-size:13px">
                        <i class="bi bi-database me-1"></i> MySQL
                    </span>
                    <span style="background:rgba(255,255,255,0.15); border-radius:20px;
                                 padding:6px 16px; font-size:13px">
                        <i class="bi bi-bootstrap me-1"></i> Bootstrap 5
                    </span>
                    <span style="background:rgba(255,255,255,0.15); border-radius:20px;
                                 padding:6px 16px; font-size:13px">
                        Version 1.0.0
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-4">

            <div class="col-lg-6">
                <div class="table-card h-100">
                    <div class="table-header">
                        <h5><i class="bi bi-bullseye me-2"></i>Objectifs du système</h5>
                    </div>
                    <div style="padding: 20px">
                        <?php
                        $objectifs = [
                            ['icone' => 'bi-robot',              'titre' => 'Automatisation',         'desc' => 'Automatiser les tâches répétitives pour réduire les erreurs humaines et gagner du temps.'],
                            ['icone' => 'bi-people',             'titre' => 'Collaboration',          'desc' => 'Faciliter la coordination entre les équipes grâce à l\'attribution intelligente des tâches.'],
                            ['icone' => 'bi-bell',               'titre' => 'Notifications',          'desc' => 'Envoyer des alertes automatiques pour les délais et les rappels importants.'],
                            ['icone' => 'bi-graph-up',           'titre' => 'Suivi & Rapports',       'desc' => 'Générer des rapports et historiques pour un suivi complet de l\'activité.'],
                            ['icone' => 'bi-shield-lock',        'titre' => 'Sécurité',              'desc' => 'Protéger les accès avec un système d\'authentification par rôle (Admin / Responsable).'],
                        ];
                        foreach ($objectifs as $o): ?>
                        <div class="d-flex gap-3 mb-4">
                            <div style="width:40px; height:40px; background:#e8eef5;
                                        border-radius:10px; display:flex; align-items:center;
                                        justify-content:center; flex-shrink:0; color:#1A3A5C; font-size:18px">
                                <i class="bi <?= $o['icone'] ?>"></i>
                            </div>
                            <div>
                                <div class="fw-semibold" style="color:#1A3A5C; font-size:14px">
                                    <?= $o['titre'] ?>
                                </div>
                                <div class="text-muted" style="font-size:13px; margin-top:3px">
                                    <?= $o['desc'] ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="table-card h-100">
                    <div class="table-header">
                        <h5><i class="bi bi-grid me-2"></i>Modules du système</h5>
                    </div>
                    <div style="padding: 20px">
                        <?php
                        $modules = [
                            ['icone' => 'bi-speedometer2',       'couleur' => '#1A3A5C', 'bg' => '#e8eef5', 'nom' => 'Tableau de bord',    'desc' => 'Vue globale des statistiques et activités récentes.'],
                            ['icone' => 'bi-check2-square',      'couleur' => '#0a4a9f', 'bg' => '#cfe2ff', 'nom' => 'Gestion des tâches', 'desc' => 'Créer, modifier, supprimer et suivre les tâches.'],
                            ['icone' => 'bi-gear-wide-connected','couleur' => '#198754', 'bg' => '#d1e7dd', 'nom' => 'Automatisation',     'desc' => 'Définir des règles d\'exécution automatique.'],
                            ['icone' => 'bi-bell',               'couleur' => '#fd7e14', 'bg' => '#ffe5d0', 'nom' => 'Notifications',      'desc' => 'Alertes automatiques et manuelles aux utilisateurs.'],
                            ['icone' => 'bi-clock-history',      'couleur' => '#6f42c1', 'bg' => '#e9d8fd', 'nom' => 'Historique',         'desc' => 'Journal complet de toutes les actions effectuées.'],
                            ['icone' => 'bi-people',             'couleur' => '#E63946', 'bg' => '#fde8e8', 'nom' => 'Utilisateurs',       'desc' => 'Gérer les comptes et les rôles des membres.'],
                        ];
                        foreach ($modules as $m): ?>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width:36px; height:36px; background:<?= $m['bg'] ?>;
                                        border-radius:8px; display:flex; align-items:center;
                                        justify-content:center; flex-shrink:0;
                                        color:<?= $m['couleur'] ?>; font-size:16px">
                                <i class="bi <?= $m['icone'] ?>"></i>
                            </div>
                            <div>
                                <div class="fw-semibold" style="font-size:13px; color:#333">
                                    <?= $m['nom'] ?>
                                </div>
                                <div class="text-muted" style="font-size:12px">
                                    <?= $m['desc'] ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="table-card">
                    <div class="table-header">
                        <h5><i class="bi bi-cpu me-2"></i>Technologies utilisées</h5>
                    </div>
                    <div style="padding: 20px">
                        <?php
                        $techs = [
                            ['nom' => 'PHP 8.x',        'role' => 'Langage backend - logique métier et traitement des données',     'badge' => '#777bb3'],
                            ['nom' => 'MySQL',          'role' => 'Base de données relationnelle - stockage et gestion des données', 'badge' => '#00758f'],
                            ['nom' => 'Bootstrap 5',    'role' => 'Framework CSS - interface responsive et moderne',                 'badge' => '#7952b3'],
                            ['nom' => 'HTML5 / CSS3',   'role' => 'Structure et style des pages web',                               'badge' => '#e34c26'],
                            ['nom' => 'PDO',            'role' => 'Couche d\'accès à la base de données - requêtes sécurisées',     'badge' => '#1A3A5C'],
                            ['nom' => 'Bootstrap Icons','role' => 'Bibliothèque d\'icônes vectorielles',                            'badge' => '#198754'],
                        ];
                        foreach ($techs as $t): ?>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <span style="background:<?= $t['badge'] ?>; color:#fff;
                                             padding:3px 10px; border-radius:12px;
                                             font-size:12px; font-weight:600">
                                    <?= $t['nom'] ?>
                                </span>
                                <div class="text-muted mt-1" style="font-size:12px">
                                    <?= $t['role'] ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="table-card">
                    <div class="table-header">
                        <h5><i class="bi bi-file-text me-2"></i>Informations du projet</h5>
                    </div>
                    <div style="padding: 20px">
                        <?php
                        $infos = [
                            ['label' => 'Nom du projet',    'valeur' => 'Système d\'Automatisation des Tâches'],
                            ['label' => 'Client',           'valeur' => 'Zandoinou Consulting'],
                            ['label' => 'Année',            'valeur' => date('Y')],
                            ['label' => 'Rôles disponibles','valeur' => 'Administrateur / Responsable'],
                        ];
                        foreach ($infos as $i): ?>
                        <div class="d-flex justify-content-between align-items-center py-2"
                             style="border-bottom:1px solid #f5f5f5">
                            <span class="text-muted" style="font-size:13px"><?= $i['label'] ?></span>
                            <span class="fw-semibold" style="font-size:13px; color:#1A3A5C">
                                <?= $i['valeur'] ?>
                            </span>
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