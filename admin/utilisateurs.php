<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Utilisateur.php';
require_once '../classes/Tache.php';

Utilisateur::exigerConnexion();
Utilisateur::exigerAdmin();

$db          = (new Database())->connect();
$utilisateur = new Utilisateur($db);
$tache       = new Tache($db);

$succes = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'creer') {
        $res = $utilisateur->inscrire(
            trim($_POST['nom']    ?? ''),
            trim($_POST['prenom'] ?? ''),
            trim($_POST['email']  ?? ''),
            $_POST['role']        ?? 'responsable'
        );
        $res['succes'] ? $succes = $res['message'] : $erreur = $res['message'];
    }

    if ($action === 'modifier') {
        $res = $utilisateur->modifierProfil(
            (int)($_POST['id']    ?? 0),
            trim($_POST['nom']    ?? ''),
            trim($_POST['prenom'] ?? '')
        );

        if ($res['succes'] && isset($_POST['role'])) {
            $nouveau_role = in_array($_POST['role'], ['admin', 'responsable']) ? $_POST['role'] : 'responsable';
            $db->prepare("UPDATE utilisateurs SET role = ? WHERE id_user = ?")
               ->execute([$nouveau_role, (int)$_POST['id']]);
        }

        $res['succes'] ? $succes = 'Utilisateur modifié avec succès.' : $erreur = $res['message'];
    }

    if ($action === 'supprimer') {
        $id_cible = (int)($_POST['id'] ?? 0);

        if ($id_cible === (int)$_SESSION['user_id']) {
            $erreur = 'Vous ne pouvez pas supprimer votre propre compte.';
        } else {
            $db->prepare("DELETE FROM utilisateurs WHERE id_user = ?")->execute([$id_cible]);
            $succes = 'Utilisateur supprimé avec succès.';
        }
    }

    if ($action === 'reinitialiser_mdp') {
        $id_cible = (int)($_POST['id'] ?? 0);
        $hash = password_hash('passer123', PASSWORD_BCRYPT);
        $db->prepare("UPDATE utilisateurs SET mot_de_passe = ?, doit_changer_mdp = 1 WHERE id_user = ?")
           ->execute([$hash, $id_cible]);
        $succes = 'Mot de passe réinitialisé à "passer123". L\'utilisateur devra le changer à sa prochaine connexion.';
    }
}

$utilisateurs = $db->query(
    "SELECT u.*, 
            COUNT(t.id_tache) AS nb_taches,
            SUM(CASE WHEN t.statut = 'en_cours'   THEN 1 ELSE 0 END) AS nb_en_cours,
            SUM(CASE WHEN t.statut = 'termine'     THEN 1 ELSE 0 END) AS nb_termine,
            SUM(CASE WHEN t.statut != 'termine' AND t.date_echeance < CURDATE() THEN 1 ELSE 0 END) AS nb_retard
     FROM utilisateurs u
     LEFT JOIN taches t ON t.id_responsable = u.id_user
     GROUP BY u.id_user
     ORDER BY u.date_creation DESC"
)->fetchAll();

$page_titre    = "Gestion des utilisateurs";
$page_courante = 'utilisateurs.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="main-content">

    <div class="topbar">
        <h1 class="titre-page"><i class="bi bi-people-fill me-2"></i>Utilisateurs</h1>
        <button class="btn btn-navy rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalCreer">
            <i class="bi bi-plus-lg me-1"></i> Nouvel utilisateur
        </button>
    </div>

    <div class="page-content">

        <?php if ($succes): ?>
            <div class="alerte-succes"> <?= htmlspecialchars($succes) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur"> <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <div class="table-card">
            <div class="table-header">
                <h5><i class="bi bi-people me-2"></i><?= count($utilisateurs) ?> utilisateur(s)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Tâches</th>
                            <th>En cours</th>
                            <th>Terminées</th>
                            <th>En retard</th>
                            <th>Membre depuis</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar" style="width:34px;height:34px;font-size:12px;flex-shrink:0">
                                        <?= strtoupper(substr($u['prenom'], 0, 1) . substr($u['nom'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></div>
                                        <?php if (!empty($u['doit_changer_mdp'])): ?>
                                            <small class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>Doit changer son mot de passe</small>
                                        <?php endif; ?>
                                        <!--  CORRECTION : id_user au lieu de id -->
                                        <?php if ($u['id_user'] == $_SESSION['user_id']): ?>
                                            <small class="text-muted">(vous)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge badge-<?= $u['role'] ?> badge-statut">
                                    <?= $u['role'] === 'admin' ? 'Admin' : 'Responsable' ?>
                                </span>
                            </td>
                            <td><span class="fw-bold"><?= $u['nb_taches'] ?></span></td>
                            <td><span class="text-primary"><?= $u['nb_en_cours'] ?></span></td>
                            <td><span class="text-success"><?= $u['nb_termine'] ?></span></td>
                            <td>
                                <?php if ($u['nb_retard'] > 0): ?>
                                    <span class="text-danger fw-bold"><?= $u['nb_retard'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= date('d/m/Y', strtotime($u['date_creation'])) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-secondary" title="Modifier"
                                        data-bs-toggle="modal" data-bs-target="#modalModifier"
                                        data-id="<?= $u['id_user'] ?>"
                                        data-nom="<?= htmlspecialchars($u['nom']) ?>"
                                        data-prenom="<?= htmlspecialchars($u['prenom']) ?>"
                                        data-role="<?= $u['role'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('Réinitialiser le mot de passe de cet utilisateur à « passer123 » ?')">
                                        <input type="hidden" name="action" value="reinitialiser_mdp">
                                        <input type="hidden" name="id" value="<?= $u['id_user'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Réinitialiser le mot de passe">
                                            <i class="bi bi-key"></i>
                                        </button>
                                    </form>
                                    <a href="taches.php?responsable=<?= $u['id_user'] ?>" class="btn btn-sm btn-outline-primary" title="Voir les tâches">
                                        <i class="bi bi-list-task"></i>
                                    </a>
                                    <?php if ($u['id_user'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('Supprimer cet utilisateur ? Ses tâches resteront mais sans responsable assigné.')">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id" value="<?= $u['id_user'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalCreer" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="creer">
                <div class="modal-header" style="background:var(--navy);color:#fff">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Nouvel utilisateur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" class="form-control" placeholder="Nom de famille" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="prenom" class="form-control" placeholder="Prénom" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="email@zandoinou.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rôle <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="responsable">Responsable</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="alert alert-info py-2 mb-0" style="font-size:13px">
                        <i class="bi bi-info-circle me-1"></i>
                        Le mot de passe par défaut sera <strong>passer123</strong>.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-navy">Créer le compte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalModifier" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="modifier">
                <input type="hidden" name="id" id="modifier_id">
                <div class="modal-header" style="background:var(--navy);color:#fff">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Modifier l'utilisateur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" id="modifier_nom" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="prenom" id="modifier_prenom" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rôle</label>
                        <select name="role" id="modifier_role" class="form-select">
                            <option value="responsable">Responsable</option>
                            <option value="admin">Administrateur</option>
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
    const btn = e.relatedTarget;
    document.getElementById('modifier_id').value     = btn.dataset.id;
    document.getElementById('modifier_nom').value    = btn.dataset.nom;
    document.getElementById('modifier_prenom').value = btn.dataset.prenom;
    document.getElementById('modifier_role').value   = btn.dataset.role;
});
</script>
</body>
</html>