<?php

class Notification {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function creer($id_utilisateur, $message) {
        $this->db->prepare(
            "INSERT INTO notifications (id_responsable, message) VALUES (?, ?)"
        )->execute([$id_utilisateur, $message]);

        return ['succes' => true];
    }

    public function getNonLues($id_utilisateur) {
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications 
             WHERE id_responsable = ? AND lu = 0 
             ORDER BY date_envoi DESC"
        );
        $stmt->execute([$id_utilisateur]);
        return $stmt->fetchAll();
    }

    public function compterNonLues($id_utilisateur) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifications WHERE id_responsable = ? AND lu = 0"
        );
        $stmt->execute([$id_utilisateur]);
        return (int) $stmt->fetchColumn();
    }

    public function getTout($id_utilisateur) {
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications 
             WHERE id_responsable = ? 
             ORDER BY date_envoi DESC"
        );
        $stmt->execute([$id_utilisateur]);
        return $stmt->fetchAll();
    }

    public function getToutAdmin() {
        $sql = "SELECT n.*, u.nom AS user_nom, u.prenom AS user_prenom
                FROM notifications n
                LEFT JOIN utilisateurs u ON n.id_responsable = u.id_user
                ORDER BY n.date_envoi DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function marquerLue($id) {
        $this->db->prepare("UPDATE notifications SET lu = 1 WHERE id_notif = ?")
                 ->execute([$id]);
    }

    public function marquerToutesLues($id_utilisateur) {
        $this->db->prepare(
            "UPDATE notifications SET lu = 1 WHERE id_responsable = ?"
        )->execute([$id_utilisateur]);
    }

    public function supprimer($id) {
        $this->db->prepare("DELETE FROM notifications WHERE id_notif = ?")->execute([$id]);
    }
}