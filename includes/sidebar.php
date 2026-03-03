<?php
?>
<div class="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="nom">ZANDOINOU <span class="badge-admin">Admin</span></div>
        <div class="sous">Consulting</div>
    </div>

    <nav>
        <div class="sidebar-section">Général</div>
        <a href="dashboard.php" class="<?= $page_courante === 'dashboard.php' ? 'actif' : '' ?>">
            <i class="bi bi-speedometer2"></i> Tableau de bord
        </a>

        <div class="sidebar-section">Gestion</div>
        <a href="utilisateurs.php" class="<?= $page_courante === 'utilisateurs.php' ? 'actif' : '' ?>">
            <i class="bi bi-people-fill"></i> Utilisateurs
        </a>
        <a href="taches.php" class="<?= $page_courante === 'taches.php' ? 'actif' : '' ?>">
            <i class="bi bi-check2-square"></i> Tâches
        </a>
        <a href="automatisation.php" class="<?= $page_courante === 'automatisation.php' ? 'actif' : '' ?>">
            <i class="bi bi-gear-wide-connected"></i> Automatisation
        </a>

        <div class="sidebar-section">Rapports</div>
        <a href="historique.php" class="<?= $page_courante === 'historique.php' ? 'actif' : '' ?>">
            <i class="bi bi-clock-history"></i> Historique
        </a>
        <a href="notifications.php" class="<?= $page_courante === 'notifications.php' ? 'actif' : '' ?>">
            <i class="bi bi-bell-fill"></i> Notifications
        </a>

        <div class="sidebar-section">Informations</div>
        <a href="apropos.php" class="<?= $page_courante === 'apropos.php' ? 'actif' : '' ?>">
            <i class="bi bi-info-circle"></i> À propos
        </a>
        <a href="contact.php" class="<?= $page_courante === 'contact.php' ? 'actif' : '' ?>">
            <i class="bi bi-headset"></i> Contact / Support
        </a>
    </nav>

    <div class="sidebar-profil">

        <div class="profil-trigger" id="profilTrigger" onclick="toggleProfil()">
            <div class="profil-avatar">
                <?= strtoupper(
                    substr($_SESSION['user_prenom'], 0, 1) .
                    substr($_SESSION['user_nom'],    0, 1)
                ) ?>
            </div>
            <div class="profil-texte">
                <strong><?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></strong>
                <span><?= htmlspecialchars($_SESSION['user_email']) ?></span>
            </div>
            <i class="bi bi-chevron-up" id="profilChevron"></i>
        </div>

        <div class="profil-dropdown" id="profilDropdown">
            <a href="profil_admin.php"
               class="<?= $page_courante === 'profil_admin.php' ? 'actif' : '' ?>">
                <i class="bi bi-person-circle"></i> Mon profil
            </a>
            <a href="profil_admin.php#mdp">
                <i class="bi bi-shield-lock"></i> Changer mon mot de passe
            </a>
            <div class="profil-separateur"></div>
            <a href="../pages/logout.php" class="lien-logout">
                <i class="bi bi-box-arrow-left"></i> Déconnexion
            </a>
        </div>

    </div>

</div>

<style>

    .sidebar-profil {
        margin-top: auto;
        position: relative;
        border-top: 1px solid rgba(255,255,255,0.08);
    }

    .profil-trigger {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        cursor: pointer;
        transition: 0.2s;
        user-select: none;
    }
    .profil-trigger:hover {
        background: rgba(255,255,255,0.06);
    }

    .profil-avatar {
        width: 36px;
        height: 36px;
        background: rgba(255,255,255,0.15);
        border: 2px solid rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 13px;
        flex-shrink: 0;
    }

    .profil-texte {
        flex: 1;
        overflow: hidden;
    }
    .profil-texte strong {
        display: block;
        color: #fff;
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .profil-texte span {
        display: block;
        color: rgba(255,255,255,0.4);
        font-size: 11px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .profil-trigger .bi-chevron-up {
        color: rgba(255,255,255,0.35);
        font-size: 12px;
        flex-shrink: 0;
        transition: transform 0.3s ease;
    }
    .profil-trigger.ouvert .bi-chevron-up {
        transform: rotate(180deg);
    }

    .profil-dropdown {
        display: none;
        background: rgba(0,0,0,0.2);
        border-top: 1px solid rgba(255,255,255,0.07);
    }
    .profil-dropdown.visible {
        display: block;
    }
    .profil-dropdown a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 20px;
        color: rgba(255,255,255,0.6);
        text-decoration: none;
        font-size: 13px;
        transition: 0.18s;
        border-left: 3px solid transparent;
    }
    .profil-dropdown a:hover {
        background: rgba(255,255,255,0.07);
        color: #fff;
        border-left-color: rgba(255,255,255,0.2);
    }
    .profil-dropdown a.actif {
        color: #fff;
        font-weight: 600;
        border-left-color: var(--red);
        background: rgba(255,255,255,0.06);
    }
    .profil-dropdown a i {
        font-size: 15px;
        width: 18px;
    }
    .profil-dropdown a.lien-logout:hover {
        color: #E63946;
        border-left-color: #E63946;
    }
    .profil-separateur {
        height: 1px;
        background: rgba(255,255,255,0.07);
        margin: 4px 0;
    }
</style>

<script>
function toggleProfil() {
    const dropdown = document.getElementById('profilDropdown');
    const trigger  = document.getElementById('profilTrigger');
    dropdown.classList.toggle('visible');
    trigger.classList.toggle('ouvert');
}

<?php if ($page_courante === 'profil_admin.php'): ?>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('profilDropdown').classList.add('visible');
    document.getElementById('profilTrigger').classList.add('ouvert');
});
<?php endif; ?>
</script>