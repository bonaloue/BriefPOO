<?php
// includes/header_resp.php
$page_courante = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_titre ?? 'Espace Responsable' ?> </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    :root {
        --navy:      #1A3A5C;
        --navy-dark: #14304f;
        --green:     #198754;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #F0F2F5;
        margin: 0;
    }

    /* ── CONTENU — navbar horizontale, pas de sidebar latérale ── */
    .main-content {
        margin-left: 0 !important;
        min-height: calc(100vh - 61px);
        display: flex;
        flex-direction: column;
    }

    .topbar {
        background: #fff; border-bottom: 1px solid #e0e0e0;
        padding: 14px 28px; display: flex;
        align-items: center; justify-content: space-between;
        position: sticky; top: 0; z-index: 50;
    }
    .topbar .titre-page { font-size: 20px; font-weight: 700; color: var(--navy); margin: 0; }
    .topbar .topbar-droite { display: flex; align-items: center; gap: 16px; }
    .topbar .notif-badge { position: relative; cursor: pointer; }
    .topbar .notif-badge .dot {
        position: absolute; top: -3px; right: -3px;
        width: 8px; height: 8px; background: #E63946; border-radius: 50%;
    }
    .user-avatar {
        width: 36px; height: 36px; background: var(--navy);
        border-radius: 50%; display: flex; align-items: center;
        justify-content: center; color: #fff; font-weight: 700; font-size: 14px;
    }

    .page-content { padding: 28px; flex: 1; }

    /* Cartes stat */
    .stat-card {
        background: #fff; border-radius: 12px; padding: 22px 24px;
        display: flex; align-items: center; gap: 18px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        border-left: 4px solid transparent; transition: 0.2s;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
    .stat-card .icone {
        width: 52px; height: 52px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; flex-shrink: 0;
    }
    .stat-card .valeur { font-size: 28px; font-weight: 700; color: var(--navy); line-height: 1; }
    .stat-card .label  { font-size: 13px; color: #888; margin-top: 3px; }

    /* Tableaux */
    .table-card {
        background: #fff; border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden;
    }
    .table-card .table-header {
        padding: 18px 22px; border-bottom: 1px solid #f0f0f0;
        display: flex; align-items: center; justify-content: space-between;
    }
    .table-card .table-header h5 { margin: 0; font-size: 16px; font-weight: 700; color: var(--navy); }
    .table-card table { margin: 0; }
    .table-card table th {
        background: #F8F9FA; color: #666;
        font-size: 12px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.5px;
        border: none; padding: 12px 16px;
    }
    .table-card table td { padding: 13px 16px; vertical-align: middle; font-size: 14px; border-color: #f5f5f5; }
    .table-card table tr:last-child td { border: none; }

    /* Badges */
    .badge-statut { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .badge-en_attente { background: #fff3cd; color: #856404; }
    .badge-en_cours   { background: #cfe2ff; color: #0a4a9f; }
    .badge-termine    { background: #d1e7dd; color: #0f5132; }
    .badge-basse      { background: #e9ecef; color: #495057; }
    .badge-normale    { background: #cfe2ff; color: #0a4a9f; }
    .badge-haute      { background: #fde8e8; color: #c0392b; }

    /* Boutons */
    .btn-navy  { background: var(--navy);  color: #fff; border: none; }
    .btn-navy:hover  { background: var(--navy-dark); color: #fff; }
    .btn-green { background: var(--green); color: #fff; border: none; }
    .btn-green:hover { background: #157347; color: #fff; }

    /* Formulaires */
    .form-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .form-label { font-weight: 600; color: #444; font-size: 14px; }
    .form-control:focus, .form-select:focus {
        border-color: var(--navy);
        box-shadow: 0 0 0 3px rgba(26,58,92,0.1);
    }

    /* Alertes */
    .alerte-succes {
        background: #d4edda; border: 1px solid #c3e6cb; color: #155724;
        padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;
    }
    .alerte-erreur {
        background: #fde8e8; border: 1px solid #f5c6c6; color: #c0392b;
        padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;
    }
</style>
<!-- ✅ Bootstrap JS requis pour le dropdown de la navbar -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>