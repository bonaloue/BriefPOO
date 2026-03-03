<?php
$nb = isset($nb_notifs) ? (int)$nb_notifs : 0;
?>

<nav class="navbar navbar-expand-lg navbar-resp sticky-top">
    <div class="container-fluid px-4">

        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
            <span class="brand-nom">ZANDOINOU</span>
            <span class="badge-resp">Responsable</span>
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navResponsable"
                aria-controls="navResponsable" aria-expanded="false">
            <i class="bi bi-list text-white fs-4"></i>
        </button>

        <div class="collapse navbar-collapse" id="navResponsable">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link-resp <?= $page_courante === 'dashboard.php' ? 'actif' : '' ?>"
                       href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link-resp <?= $page_courante === 'mes_taches.php' ? 'actif' : '' ?>"
                       href="mes_taches.php">
                        <i class="bi bi-check2-square"></i>
                        <span>Mes tâches</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link-resp position-relative <?= $page_courante === 'notifications.php' ? 'actif' : '' ?>"
                       href="notifications.php">
                        <i class="bi bi-bell-fill"></i>
                        <span>Notifications</span>
                        <?php if ($nb > 0): ?>
                            <span class="badge bg-danger badge-notif-nav"><?= $nb ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link-resp <?= $page_courante === 'apropos.php' ? 'actif' : '' ?>"
                       href="apropos.php">
                        <i class="bi bi-info-circle"></i>
                        <span>À propos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link-resp <?= $page_courante === 'contact.php' ? 'actif' : '' ?>"
                       href="contact.php">
                        <i class="bi bi-headset"></i>
                        <span>Support</span>
                    </a>
                </li>

            </ul>

            <ul class="navbar-nav ms-auto align-items-center">

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 px-2"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-navbar">
                            <?= strtoupper(
                                substr($_SESSION['user_prenom'], 0, 1) .
                                substr($_SESSION['user_nom'],    0, 1)
                            ) ?>
                        </div>
                        <div class="d-none d-lg-block text-start">
                            <div class="avatar-nom">
                                <?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?>
                            </div>
                            <div class="avatar-role">Responsable</div>
                        </div>
                        <i class="bi bi-chevron-down text-white-50 ms-1" style="font-size:11px"></i>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 dropdown-profil">

                        <li>
                            <div class="dropdown-entete">
                                <div class="dropdown-avatar">
                                    <?= strtoupper(
                                        substr($_SESSION['user_prenom'], 0, 1) .
                                        substr($_SESSION['user_nom'],    0, 1)
                                    ) ?>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size:14px; color:#1A3A5C">
                                        <?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:12px">
                                        <?= htmlspecialchars($_SESSION['user_email']) ?>
                                    </div>
                                    <span class="badge mt-1" style="background:#198754; font-size:10px; font-weight:600">
                                        Responsable
                                    </span>
                                </div>
                            </div>
                        </li>

                        <li><hr class="dropdown-divider my-1"></li>

                        <li>
                            <a class="dropdown-item dropdown-lien <?= $page_courante === 'profil.php' ? 'actif-dd' : '' ?>"
                               href="profil.php">
                                <i class="bi bi-person-circle"></i> Mon profil
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item dropdown-lien" href="profil.php#mdp">
                                <i class="bi bi-shield-lock"></i> Changer mon mot de passe
                            </a>
                        </li>

                        <li><hr class="dropdown-divider my-1"></li>

                        <li>
                            <a class="dropdown-item dropdown-lien text-danger" href="../pages/logout.php">
                                <i class="bi bi-box-arrow-left"></i> Déconnexion
                            </a>
                        </li>

                    </ul>
                </li>

            </ul>
        </div>
    </div>
</nav>

<style>

    .navbar-resp {
        background: var(--navy);
        border-bottom: 3px solid #198754;
        padding: 0;
        z-index: 100;
        min-height: 58px;
    }

    .navbar-resp .brand-nom {
        font-weight: 800;
        font-size: 15px;
        color: #fff;
        letter-spacing: 1px;
    }
    .navbar-resp .badge-resp {
        background: #198754;
        color: #fff;
        font-size: 10px;
        padding: 3px 9px;
        border-radius: 10px;
        font-weight: 600;
    }

    .nav-link-resp {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 18px 14px !important;
        color: rgba(255,255,255,0.65);
        text-decoration: none;
        font-size: 13.5px;
        border-bottom: 3px solid transparent;
        transition: 0.2s;
        position: relative;
        white-space: nowrap;
    }
    .nav-link-resp:hover {
        color: #fff;
        background: rgba(255,255,255,0.06);
    }
    .nav-link-resp.actif {
        color: #fff;
        border-bottom-color: #198754;
        font-weight: 600;
        background: rgba(255,255,255,0.08);
    }
    .nav-link-resp i { font-size: 15px; }

    .badge-notif-nav {
        font-size: 9px;
        padding: 2px 5px;
        position: absolute;
        top: 10px;
        right: 6px;
    }

    .avatar-navbar {
        width: 34px;
        height: 34px;
        background: rgba(255,255,255,0.15);
        border: 2px solid rgba(255,255,255,0.25);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        flex-shrink: 0;
    }
    .avatar-nom {
        font-size: 13px;
        font-weight: 600;
        color: #fff;
        line-height: 1.2;
    }
    .avatar-role {
        font-size: 10px;
        color: rgba(255,255,255,0.45);
    }

    .navbar-resp .dropdown-toggle::after {
        display: none; /* on utilise notre propre chevron */
    }
    .dropdown-profil {
        border-radius: 12px !important;
        min-width: 230px;
        margin-top: 6px !important;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
    }
    .dropdown-entete {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px 10px;
    }
    .dropdown-avatar {
        width: 40px;
        height: 40px;
        background: var(--navy);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 14px;
        flex-shrink: 0;
    }
    .dropdown-lien {
        display: flex !important;
        align-items: center;
        gap: 10px;
        font-size: 13px !important;
        padding: 9px 16px !important;
        color: #444 !important;
        transition: 0.15s;
    }
    .dropdown-lien:hover {
        background: #f0f4f8 !important;
        color: var(--navy) !important;
    }
    .dropdown-lien.actif-dd {
        background: #e8eef5 !important;
        color: var(--navy) !important;
        font-weight: 600 !important;
    }
    .dropdown-lien i { font-size: 15px; width: 18px; color: #888; }
    .dropdown-lien.text-danger i { color: #E63946; }

    .navbar-resp .navbar-toggler {
        border: 1px solid rgba(255,255,255,0.2);
        padding: 4px 10px;
    }
    .navbar-resp .navbar-toggler:focus { box-shadow: none; }

    .main-content {
        margin-left: 0 !important;
        min-height: calc(100vh - 61px);
    }

    @media (max-width: 991px) {
        .navbar-resp { padding: 4px 0; }
        .nav-link-resp {
            padding: 10px 16px !important;
            border-bottom: none;
            border-left: 3px solid transparent;
        }
        .nav-link-resp.actif {
            border-bottom: none;
            border-left-color: #198754;
        }
        .dropdown-menu-end { right: 0; left: auto; }
    }
</style>