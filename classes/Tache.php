<?php

class Tache {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ── Récupérer toutes les tâches avec le nom du responsable ──────────────
    public function getTout() {
        // ✅ CORRECTION : table "taches" et colonne "id_tache" / "id_user"
        $sql = "SELECT t.*, 
                       u.nom AS responsable_nom, 
                       u.prenom AS responsable_prenom
                FROM taches t
                LEFT JOIN utilisateurs u ON t.id_responsable = u.id_user
                ORDER BY t.date_creation DESC";
        return $this->db->query($sql)->fetchAll();
    }

    // ── Récupérer une tâche par ID ───────────────────────────────────────────
    public function getParId($id) {
        // ✅ CORRECTION : table "taches", colonne "id_tache"
        $stmt = $this->db->prepare(
            "SELECT t.*, u.nom AS responsable_nom, u.prenom AS responsable_prenom
             FROM taches t
             LEFT JOIN utilisateurs u ON t.id_responsable = u.id_user
             WHERE t.id_tache = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ── Récupérer les tâches par statut ─────────────────────────────────────
    public function getParStatut($statut) {
        $stmt = $this->db->prepare(
            "SELECT t.*, u.nom AS responsable_nom, u.prenom AS responsable_prenom
             FROM taches t
             LEFT JOIN utilisateurs u ON t.id_responsable = u.id_user
             WHERE t.statut = ?
             ORDER BY t.date_echeance ASC"
        );
        $stmt->execute([$statut]);
        return $stmt->fetchAll();
    }

    // ── Récupérer les tâches d'un responsable ───────────────────────────────
    public function getParResponsable($id_user) {
        $stmt = $this->db->prepare(
            "SELECT t.*, u.nom AS responsable_nom, u.prenom AS responsable_prenom
             FROM taches t
             LEFT JOIN utilisateurs u ON t.id_responsable = u.id_user
             WHERE t.id_responsable = ?
             ORDER BY t.date_creation DESC"
        );
        $stmt->execute([$id_user]);
        return $stmt->fetchAll();
    }

    // ── Récupérer les tâches en retard ──────────────────────────────────────
    public function getEnRetard() {
        $sql = "SELECT t.*, u.nom AS responsable_nom, u.prenom AS responsable_prenom
                FROM taches t
                LEFT JOIN utilisateurs u ON t.id_responsable = u.id_user
                WHERE t.statut != 'termine'
                  AND t.date_echeance < CURDATE()
                ORDER BY t.date_echeance ASC";
        return $this->db->query($sql)->fetchAll();
    }

    // ── Statistiques pour le dashboard ──────────────────────────────────────
    public function getStats() {
        $stats = [];

        // ✅ CORRECTION : table "taches" au lieu de "tasks"
        $stats['total']      = $this->db->query("SELECT COUNT(*) FROM taches")->fetchColumn();
        $stats['en_attente'] = $this->db->query("SELECT COUNT(*) FROM taches WHERE statut = 'en_attente'")->fetchColumn();
        $stats['en_cours']   = $this->db->query("SELECT COUNT(*) FROM taches WHERE statut = 'en_cours'")->fetchColumn();
        $stats['termine']    = $this->db->query("SELECT COUNT(*) FROM taches WHERE statut = 'termine'")->fetchColumn();
        $stats['en_retard']  = $this->db->query(
            "SELECT COUNT(*) FROM taches WHERE statut != 'termine' AND date_echeance < CURDATE()"
        )->fetchColumn();

        return $stats;
    }

    // ── Créer une tâche ──────────────────────────────────────────────────────
    public function creer($titre, $description, $statut, $priorite, $id_responsable, $date_echeance, $id_createur) {

        if (empty(trim($titre))) {
            return ['succes' => false, 'message' => 'Le titre de la tâche est obligatoire.'];
        }

        if (!in_array($statut, ['en_attente', 'en_cours', 'termine'])) {
            return ['succes' => false, 'message' => 'Statut invalide.'];
        }

        if (!in_array($priorite, ['basse', 'normale', 'haute'])) {
            return ['succes' => false, 'message' => 'Priorité invalide.'];
        }

        // ✅ CORRECTION : table "taches" + ajout id_createur
        $sql = "INSERT INTO taches (titre, description, statut, priorite, id_responsable, id_createur, date_echeance)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            trim($titre),
            trim($description),
            $statut,
            $priorite,
            $id_responsable ?: null,
            $id_createur ?: null,
            $date_echeance ?: null
        ]);

        $id_tache = $this->db->lastInsertId();

        $this->ajouterHistorique($id_tache, $id_createur, 'Tâche créée');

        return ['succes' => true, 'message' => 'Tâche créée avec succès.', 'id' => $id_tache];
    }

    // ── Modifier une tâche ───────────────────────────────────────────────────
    public function modifier($id, $titre, $description, $statut, $priorite, $id_responsable, $date_echeance, $id_editeur) {

        if (empty(trim($titre))) {
            return ['succes' => false, 'message' => 'Le titre de la tâche est obligatoire.'];
        }

        // ✅ CORRECTION : table "taches", colonne "id_tache"
        $sql = "UPDATE taches 
                SET titre = ?, description = ?, statut = ?, priorite = ?, 
                    id_responsable = ?, date_echeance = ?
                WHERE id_tache = ?";
        $this->db->prepare($sql)->execute([
            trim($titre),
            trim($description),
            $statut,
            $priorite,
            $id_responsable ?: null,
            $date_echeance ?: null,
            $id
        ]);

        $this->ajouterHistorique($id, $id_editeur, "Tâche modifiée (statut : $statut)");

        return ['succes' => true, 'message' => 'Tâche modifiée avec succès.'];
    }

    // ── Changer uniquement le statut ─────────────────────────────────────────
    public function changerStatut($id, $statut, $id_user) {
        // ✅ CORRECTION : table "taches", colonne "id_tache"
        $this->db->prepare("UPDATE taches SET statut = ? WHERE id_tache = ?")
                 ->execute([$statut, $id]);

        $labels = ['en_attente' => 'En attente', 'en_cours' => 'En cours', 'termine' => 'Terminé'];
        $this->ajouterHistorique($id, $id_user, "Statut changé en « {$labels[$statut]} »");

        return ['succes' => true, 'message' => 'Statut mis à jour.'];
    }

    // ── Supprimer une tâche ──────────────────────────────────────────────────
    public function supprimer($id, $id_user) {
        $tache = $this->getParId($id);
        if (!$tache) {
            return ['succes' => false, 'message' => 'Tâche introuvable.'];
        }

        $this->ajouterHistorique(null, $id_user, "Tâche supprimée : « {$tache['titre']} »");

        // ✅ CORRECTION : table "taches", colonne "id_tache"
        $this->db->prepare("DELETE FROM taches WHERE id_tache = ?")->execute([$id]);

        return ['succes' => true, 'message' => 'Tâche supprimée avec succès.'];
    }

    // ── Ajouter une entrée dans l'historique ─────────────────────────────────
    public function ajouterHistorique($id_tache, $id_responsable, $action) {
        // ✅ CORRECTION : table "historique" + colonne "description_action"
        $this->db->prepare(
            "INSERT INTO historique (id_tache, id_responsable, description_action) VALUES (?, ?, ?)"
        )->execute([$id_tache, $id_responsable, $action]);
    }

    // ── Récupérer l'historique complet ───────────────────────────────────────
    public function getHistorique($limite = 50) {
        // ✅ CORRECTION : tables "historique", "taches", "utilisateurs"
        //                 colonnes "id_tache", "id_user", "description_action"
        $sql = "SELECT h.*, 
                       t.titre AS tache_titre,
                       u.nom AS user_nom, u.prenom AS user_prenom
                FROM historique h
                LEFT JOIN taches t ON h.id_tache = t.id_tache
                LEFT JOIN utilisateurs u ON h.id_responsable = u.id_user
                ORDER BY h.date_action DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }
}