CREATE DATABASE briefpoo;
USE briefpoo;

CREATE TABLE utilisateurs (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    doit_changer_mdp TINYINT(1) NOT NULL DEFAULT 0
    role ENUM('admin', 'responsable') NOT NULL DEFAULT 'responsable',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE taches (
    id_tache INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(100) NOT NULL,
    description TEXT,
    statut ENUM('en_attente', 'en_cours', 'termine') NOT NULL DEFAULT 'en_attente',
    priorite ENUM('basse', 'normale', 'haute') NOT NULL DEFAULT 'normale',
    id_responsable INT,
    id_createur INT,
    date_echeance DATE,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tache_responsable FOREIGN KEY (id_responsable) REFERENCES utilisateurs(id_user),
    CONSTRAINT fk_tache_createur FOREIGN KEY (id_createur) REFERENCES utilisateurs(id_user)
);

CREATE TABLE notifications (
    id_notif INT AUTO_INCREMENT PRIMARY KEY,
    id_responsable INT,
    message TEXT,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_responsable FOREIGN KEY (id_responsable) REFERENCES utilisateurs(id_user)
);

CREATE TABLE automatisation (
    id_auto INT AUTO_INCREMENT PRIMARY KEY,
    nom_regle VARCHAR(150) NOT NULL,
    condition_auto TEXT NOT NULL,
    action_auto TEXT NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE historique (
    id_historique INT AUTO_INCREMENT PRIMARY KEY,
    id_tache INT,
    id_responsable INT,
    description_action VARCHAR(200) NOT NULL,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historique_tache FOREIGN KEY (id_tache) REFERENCES taches(id_tache),
    CONSTRAINT fk_historique_utilisateur FOREIGN KEY (id_responsable) REFERENCES utilisateurs(id_user)
);


INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role)
VALUES ('Admin', 'Super', 'bonaloue@gmail.com','$2y$10$9TPbelCGoJ7PY4EijZSnyOM3.y6lPt6w5z3wLWu1uCNRw/sGCCXzy', 'admin');
