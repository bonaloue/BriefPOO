<?php
$page_courante = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_titre ?? 'Admin' ?> </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    :root {
        --navy:      #1A3A5C;
        --navy-dark: #14304f;
        --red:       #E63946;
        --sidebar-w: 260px;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #F0F2F5;
        margin: 0;
    }

    .sidebar {
        position: fixed;
        top: 0; left: 0;
        width: var(--sidebar-w);
        height: 100vh;
        background: var(--navy);
        display: flex;
        flex-direction: column;
        z-index: 100;
        overflow-y: auto;
    }

    .sidebar-logo {
        padding: 22px 20px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .sidebar-logo .nom {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
        letter-spacing: 1px;
    }
    .sidebar-logo .sous {
        font-size: 11px;
        color: rgba(255,255,255,0.5);
        letter-spacing: 2px;
        text-transform: uppercase;
    }
    .sidebar-logo .badge-admin {
        background: var(--red);
        color: #fff;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 10px;
        margin-left: 6px;
        vertical-align: middle;
    }

    .sidebar-section {
        padding: 14px 20px 4px;
        font-size: 10px;
        font-weight: 700;
        color: rgba(255,255,255,0.35);
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    .sidebar nav a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 20px;
        color: rgba(255,255,255,0.7);
        text-decoration: none;
        font-size: 14px;
        transition: 0.2s;
        border-left: 3px solid transparent;
    }
    .sidebar nav a:hover {
        background: rgba(255,255,255,0.07);
        color: #fff;
    }
    .sidebar nav a.actif {
        background: rgba(255,255,255,0.1);
        color: #fff;
        border-left-color: var(--red);
        font-weight: 600;
    }
    .sidebar nav a i { font-size: 16px; width: 20px; }

    .sidebar-footer {
        margin-top: auto;
        padding: 16px 20px;
        border-top: 1px solid rgba(255,255,255,0.1);
        font-size: 13px;
        color: rgba(255,255,255,0.5);
    }
    .sidebar-footer .user-info { margin-bottom: 10px; }
    .sidebar-footer .user-info strong { color: #fff; display: block; font-size: 14px; }
    .sidebar-footer a {
        color: rgba(255,255,255,0.5);
        text-decoration: none;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: 0.2s;
    }
    .sidebar-footer a:hover { color: var(--red); }

    .main-content {
        margin-left: var(--sidebar-w);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .topbar {
        background: #fff;
        border-bottom: 1px solid #e0e0e0;
        padding: 14px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 50;
    }
    .topbar .titre-page {
        font-size: 20px;
        font-weight: 700;
        color: var(--navy);
        margin: 0;
    }
    .topbar .topbar-droite {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .topbar .notif-badge {
        position: relative;
        cursor: pointer;
    }
    .topbar .notif-badge .dot {
        position: absolute;
        top: -3px; right: -3px;
        width: 8px; height: 8px;
        background: var(--red);
        border-radius: 50%;
    }
    .topbar .user-avatar {
        width: 36px; height: 36px;
        background: var(--navy);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 14px;
    }

    .page-content {
        padding: 28px;
        flex: 1;
    }

    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 22px 24px;
        display: flex;
        align-items: center;
        gap: 18px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        border-left: 4px solid transparent;
        transition: 0.2s;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
    .stat-card .icone {
        width: 52px; height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .stat-card .valeur { font-size: 28px; font-weight: 700; color: var(--navy); line-height: 1; }
    .stat-card .label  { font-size: 13px; color: #888; margin-top: 3px; }

    .table-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .table-card .table-header {
        padding: 18px 22px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .table-card .table-header h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: var(--navy);
    }
    .table-card table { margin: 0; }
    .table-card table th {
        background: #F8F9FA;
        color: #666;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
        padding: 12px 16px;
    }
    .table-card table td {
        padding: 13px 16px;
        vertical-align: middle;
        font-size: 14px;
        border-color: #f5f5f5;
    }
    .table-card table tr:last-child td { border: none; }

    .badge-statut {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-en_attente  { background: #fff3cd; color: #856404; }
    .badge-en_cours    { background: #cfe2ff; color: #0a4a9f; }
    .badge-termine     { background: #d1e7dd; color: #0f5132; }
    .badge-retard      { background: #fde8e8; color: #c0392b; }

    .badge-basse   { background: #e9ecef; color: #495057; }
    .badge-normale { background: #cfe2ff; color: #0a4a9f; }
    .badge-haute   { background: #fde8e8; color: #c0392b; }

    .badge-admin       { background: var(--red); color: #fff; }
    .badge-responsable { background: var(--navy); color: #fff; }

    .btn-navy {
        background: var(--navy);
        color: #fff;
        border: none;
    }
    .btn-navy:hover { background: var(--navy-dark); color: #fff; }

    .btn-red {
        background: var(--red);
        color: #fff;
        border: none;
    }
    .btn-red:hover { background: #c0303a; color: #fff; }

    .form-card {
        background: #fff;
        border-radius: 12px;
        padding: 28px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .form-label { font-weight: 600; color: #444; font-size: 14px; }
    .form-control:focus, .form-select:focus {
        border-color: var(--navy);
        box-shadow: 0 0 0 3px rgba(26,58,92,0.1);
    }

    .alerte-succes {
        background: #d4edda; border: 1px solid #c3e6cb; color: #155724;
        padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;
    }
    .alerte-erreur {
        background: #fde8e8; border: 1px solid #f5c6c6; color: #c0392b;
        padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;
    }

    @media (max-width: 768px) {
        .sidebar { width: 100%; height: auto; position: relative; }
        .main-content { margin-left: 0; }
    }
</style>
</head>
<body>