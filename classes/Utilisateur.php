<?php

class Utilisateur {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ──────────────────────────────────────────────────────────
    //  UTILITAIRES PRIVÉS
    // ──────────────────────────────────────────────────────────

    private function emailExiste($email) {
        $stmt = $this->db->prepare("SELECT id_user FROM utilisateurs WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    private function trouverParEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM utilisateurs WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function trouverParId($id) {
        $stmt = $this->db->prepare("SELECT * FROM utilisateurs WHERE id_user = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Vérifie si une colonne existe dans une table (évite les crashs si migration pas faite)
    private function colonneExiste($colonne, $table) {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM `$table` LIKE '$colonne'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    // ──────────────────────────────────────────────────────────
    //  RÉCUPÉRER TOUS LES UTILISATEURS
    // ──────────────────────────────────────────────────────────

    public function getTous() {
        $stmt = $this->db->query(
            "SELECT id_user, nom, prenom, email, role, date_creation
             FROM utilisateurs
             ORDER BY date_creation DESC"
        );
        return $stmt->fetchAll();
    }

    // ──────────────────────────────────────────────────────────
    //  INSCRIPTION — réservée à l'admin
    // ──────────────────────────────────────────────────────────

    const MDP_PAR_DEFAUT = 'passer123';

    public function inscrire($nom, $prenom, $email, $role = 'responsable') {

        if (empty($nom) || empty($prenom) || empty($email)) {
            return ['succes' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['succes' => false, 'message' => 'Adresse e-mail invalide.'];
        }

        if (!in_array($role, ['admin', 'responsable'])) {
            return ['succes' => false, 'message' => 'Rôle invalide.'];
        }

        if ($this->emailExiste($email)) {
            return ['succes' => false, 'message' => 'Cette adresse e-mail est déjà utilisée.'];
        }

        $hash = password_hash(self::MDP_PAR_DEFAUT, PASSWORD_BCRYPT);

        // Insertion adaptée : avec ou sans colonne doit_changer_mdp
        if ($this->colonneExiste('doit_changer_mdp', 'utilisateurs')) {
            $this->db->prepare(
                "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, doit_changer_mdp)
                 VALUES (?, ?, ?, ?, ?, 1)"
            )->execute([$nom, $prenom, $email, $hash, $role]);
        } else {
            $this->db->prepare(
                "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role)
                 VALUES (?, ?, ?, ?, ?)"
            )->execute([$nom, $prenom, $email, $hash, $role]);
        }

        return ['succes' => true, 'message' => "Compte créé. Mot de passe par défaut : " . self::MDP_PAR_DEFAUT];
    }

    // ──────────────────────────────────────────────────────────
    //  CONNEXION
    // ──────────────────────────────────────────────────────────

    public function connecter($email, $mot_de_passe) {

        if (empty($email) || empty($mot_de_passe)) {
            return ['succes' => false, 'message' => 'Veuillez remplir tous les champs.'];
        }

        $utilisateur = $this->trouverParEmail($email);

        if (!$utilisateur || !password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            return ['succes' => false, 'message' => 'Email ou mot de passe incorrect.'];
        }

        session_regenerate_id(true);

        $_SESSION['user_id']     = $utilisateur['id_user'];
        $_SESSION['user_nom']    = $utilisateur['nom'];
        $_SESSION['user_prenom'] = $utilisateur['prenom'];
        $_SESSION['user_email']  = $utilisateur['email'];
        $_SESSION['user_role']   = $utilisateur['role'];

        // Un admin ne doit JAMAIS être bloqué par doit_changer_mdp
        if ($utilisateur['role'] === 'admin') {
            $_SESSION['doit_changer_mdp'] = false;
        } else {
            $_SESSION['doit_changer_mdp'] = isset($utilisateur['doit_changer_mdp'])
                ? (bool) $utilisateur['doit_changer_mdp']
                : false;
        }

        return ['succes' => true, 'message' => 'Connexion réussie.'];
    }

    // ──────────────────────────────────────────────────────────
    //  DÉCONNEXION
    // ──────────────────────────────────────────────────────────

    public function deconnecter() {
        session_unset();
        session_destroy();
    }

    // ──────────────────────────────────────────────────────────
    //  MODIFICATION DU PROFIL
    // ──────────────────────────────────────────────────────────

    public function modifierProfil($id, $nom, $prenom) {

        if (empty(trim($nom)) || empty(trim($prenom))) {
            return ['succes' => false, 'message' => 'Le nom et le prénom sont obligatoires.'];
        }

        $this->db->prepare(
            "UPDATE utilisateurs SET nom = ?, prenom = ? WHERE id_user = ?"
        )->execute([trim($nom), trim($prenom), $id]);

        $_SESSION['user_nom']    = trim($nom);
        $_SESSION['user_prenom'] = trim($prenom);

        return ['succes' => true, 'message' => 'Profil mis à jour avec succès.'];
    }

    // ──────────────────────────────────────────────────────────
    //  MODIFICATION DU MOT DE PASSE
    // ──────────────────────────────────────────────────────────

    public function modifierMotDePasse($id, $ancien, $nouveau, $confirmation) {

        if (empty($ancien) || empty($nouveau) || empty($confirmation)) {
            return ['succes' => false, 'message' => 'Veuillez remplir tous les champs.'];
        }

        if ($nouveau !== $confirmation) {
            return ['succes' => false, 'message' => 'Les nouveaux mots de passe ne correspondent pas.'];
        }

        if (strlen($nouveau) < 8) {
            return ['succes' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'];
        }

        if ($nouveau === self::MDP_PAR_DEFAUT) {
            return ['succes' => false, 'message' => 'Vous ne pouvez pas garder le mot de passe par défaut.'];
        }

        $utilisateur = $this->trouverParId($id);

        if (!$utilisateur || !password_verify($ancien, $utilisateur['mot_de_passe'])) {
            return ['succes' => false, 'message' => 'Ancien mot de passe incorrect.'];
        }

        $hash = password_hash($nouveau, PASSWORD_BCRYPT);

        // Mise à jour adaptée : avec ou sans colonne doit_changer_mdp
        if ($this->colonneExiste('doit_changer_mdp', 'utilisateurs')) {
            $this->db->prepare(
                "UPDATE utilisateurs SET mot_de_passe = ?, doit_changer_mdp = 0 WHERE id_user = ?"
            )->execute([$hash, $id]);
        } else {
            $this->db->prepare(
                "UPDATE utilisateurs SET mot_de_passe = ? WHERE id_user = ?"
            )->execute([$hash, $id]);
        }

        $_SESSION['doit_changer_mdp'] = false;

        return ['succes' => true, 'message' => 'Mot de passe modifié avec succès.'];
    }

    // ──────────────────────────────────────────────────────────
    //  MÉTHODES STATIQUES — vérification de session
    // ──────────────────────────────────────────────────────────

    public static function estConnecte() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function exigerConnexion() {
        if (!self::estConnecte()) {
            header('Location: ../public/index.php');
            exit;
        }
    }

    public static function estAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    public static function exigerAdmin() {
        if (!self::estAdmin()) {
            header('Location: ../responsable/dashboard.php');
            exit;
        }
    }

    // Bloquer toutes les pages responsable si mdp par défaut pas encore changé
    public static function exigerChangementMdp() {
        if (!empty($_SESSION['doit_changer_mdp'])) {
            header('Location: ../responsable/changer_mdp.php');
            exit;
        }
    }
}