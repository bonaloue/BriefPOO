<?php

class Notification {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ── Créer une notification ───────────────────────────────────────────────
    public function creer($id_utilisateur, $message) {
        // ✅ CORRECTION : id_responsable (nom de colonne dans ta BDD)
        $this->db->prepare(
            "INSERT INTO notifications (id_responsable, message) VALUES (?, ?)"
        )->execute([$id_utilisateur, $message]);

        return ['succes' => true];
    }

    // ── Récupérer les non lues d'un utilisateur ──────────────────────────────
    public function getNonLues($id_utilisateur) {
        // ✅ CORRECTION : id_responsable au lieu de id_utilisateur
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications 
             WHERE id_responsable = ? AND lu = 0 
             ORDER BY date_envoi DESC"
        );
        $stmt->execute([$id_utilisateur]);
        return $stmt->fetchAll();
    }

    // ── Compter les non lues ──────────────────────────────────────────────────
    public function compterNonLues($id_utilisateur) {
        // ✅ CORRECTION : id_responsable au lieu de id_utilisateur
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifications WHERE id_responsable = ? AND lu = 0"
        );
        $stmt->execute([$id_utilisateur]);
        return (int) $stmt->fetchColumn();
    }

    // ── Récupérer toutes les notifications d'un utilisateur ──────────────────
    public function getTout($id_utilisateur) {
        // ✅ CORRECTION : id_responsable au lieu de id_utilisateur
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications 
             WHERE id_responsable = ? 
             ORDER BY date_envoi DESC"
        );
        $stmt->execute([$id_utilisateur]);
        return $stmt->fetchAll();
    }

    // ── Récupérer toutes les notifications (vue admin) ────────────────────────
    public function getToutAdmin() {
        // ✅ CORRECTION : id_user et table utilisateurs (noms corrects de ta BDD)
        $sql = "SELECT n.*, u.nom AS user_nom, u.prenom AS user_prenom
                FROM notifications n
                LEFT JOIN utilisateurs u ON n.id_responsable = u.id_user
                ORDER BY n.date_envoi DESC";
        return $this->db->query($sql)->fetchAll();
    }

    // ── Marquer une notification comme lue ───────────────────────────────────
    public function marquerLue($id) {
        // ✅ CORRECTION : id_notif au lieu de id
        $this->db->prepare("UPDATE notifications SET lu = 1 WHERE id_notif = ?")
                 ->execute([$id]);
    }

    // ── Marquer toutes les notifications d'un utilisateur comme lues ─────────
    public function marquerToutesLues($id_utilisateur) {
        // ✅ CORRECTION : id_responsable au lieu de id_utilisateur
        $this->db->prepare(
            "UPDATE notifications SET lu = 1 WHERE id_responsable = ?"
        )->execute([$id_utilisateur]);
    }

    // ── Supprimer une notification ────────────────────────────────────────────
    public function supprimer($id) {
        // ✅ CORRECTION : id_notif au lieu de id
        $this->db->prepare("DELETE FROM notifications WHERE id_notif = ?")->execute([$id]);
    }
}