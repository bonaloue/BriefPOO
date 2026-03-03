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


INSERT INTO `utilisateurs` (`id_user`, `nom`, `prenom`, `email`, `mot_de_passe`, `doit_changer_mdp`, `role`, `date_creation`) VALUES
(1, 'Bonaventure', 'LOUE', 'bonaloue@gmail.com', '$2y$10$9TPbelCGoJ7PY4EijZSnyOM3.y6lPt6w5z3wLWu1uCNRw/sGCCXzy', 0, 'admin', '2026-02-23 12:41:40'),
(2, 'LOUE', 'Bonaventure', 'bonaventureloue@gmail.com', '$2y$10$9TPbelCGoJ7PY4EijZSnyOM3.y6lPt6w5z3wLWu1uCNRw/sGCCXzy', 0, 'responsable', '2026-02-23 13:30:31'),
(3, 'test', 'test', 'test@gmail.com', '$2y$10$yALbfbCl2DL4HCmeJXPgf.1iUEsQW21sBdFZGBLUSlmKVDtck/nAa', 0, 'responsable', '2026-02-23 23:16:15'),
(4, 'SAWADOGO', 'Aminata', 'aminata.sawadogo@zandoinou.com', '$2y$10$9TPbelCGoJ7PY4EijZSnyOM3.y6lPt6w5z3wLWu1uCNRw/sGCCXzy', 1, 'responsable', '2026-02-23 23:42:28'),
(5, 'TRAORE', 'Moussa', 'moussa.traore@zandoinou.com', '$2y$10$9TPbelCGoJ7PY4EijZSnyOM3.y6lPt6w5z3wLWu1uCNRw/sGCCXzy', 0, 'responsable', '2026-02-23 23:42:28'),
(6, 'KABORE', 'Issouf', 'issouf.kabore@zandoinou.com', '$2y$10$9TPbelCGoJ7PY4EijZSnyOM3.y6lPt6w5z3wLWu1uCNRw/sGCCXzy', 0, 'admin', '2026-02-23 23:42:28');


INSERT INTO `taches` (`id_tache`, `titre`, `description`, `statut`, `priorite`, `id_responsable`, `id_createur`, `date_echeance`, `date_creation`) VALUES
(1, 'Creation d\'utilisateur', '', 'termine', 'haute', 1, 1, '2026-02-24', '2026-02-23 23:23:37'),
(2, 'Effectuer la 1ere tache', '', 'en_attente', 'normale', 3, 1, '2026-02-23', '2026-02-23 23:24:46'),
(3, 'Audit du système de gestion', 'Analyser les processus actuels et proposer des améliorations', 'en_cours', 'haute', 2, 1, '2026-03-15', '2026-02-23 10:00:00'),
(4, 'Rédiger le rapport mensuel', 'Préparer le rapport d\'activité du mois de février', 'en_attente', 'normale', 4, 1, '2026-03-05', '2026-02-23 11:00:00'),
(5, 'Mise à jour des procédures', 'Réviser et mettre à jour les procédures internes', 'en_cours', 'haute', 5, 1, '2026-02-10', '2026-02-01 08:00:00'),
(6, 'Formation sur le nouveau logiciel', 'Organiser une session de formation pour l\'équipe', 'en_attente', 'haute', 2, 1, '2026-02-15', '2026-02-10 09:00:00'),
(7, 'Archivage des documents 2025', 'Classer et archiver tous les documents de l\'année 2025', 'en_attente', 'basse', 4, 1, NULL, '2026-02-23 14:00:00'),
(8, 'Optimisation des coûts opérationnels', 'Identifier les postes de dépenses à réduire', 'en_cours', 'normale', 5, 3, '2026-03-31', '2026-02-20 10:00:00'),
(9, 'Inventaire du matériel informatique', 'Recenser tout le matériel IT de l\'entreprise', 'termine', 'normale', 4, 1, '2026-02-20', '2026-02-15 08:00:00'),
(10, 'Définir la stratégie digitale 2026', 'Élaborer la feuille de route numérique pour 2026', 'en_attente', 'haute', NULL, 1, '2026-04-01', '2026-02-23 15:00:00');

INSERT INTO `historique` (`id_historique`, `id_tache`, `id_responsable`, `description_action`, `date_action`) VALUES
(1, 1, 1, 'Tâche créée', '2026-02-23 23:23:37'),
(2, 1, 1, 'Statut changé en « Terminé »', '2026-02-23 23:23:55'),
(3, 2, 1, 'Tâche créée', '2026-02-23 23:24:46'),
(4, 2, 3, 'Statut changé en « Terminé »', '2026-02-23 23:25:27'),
(5, 1, 1, 'Tâche créée', '2026-02-23 08:00:00'),
(6, 1, 1, 'Statut changé : en_attente → en_cours', '2026-02-23 10:00:00'),
(7, 1, 1, 'Statut changé : en_cours → termine', '2026-02-23 15:00:00'),
(8, 2, 1, 'Tâche créée', '2026-02-23 09:00:00'),
(9, 2, 3, 'Statut changé : en_attente → termine', '2026-02-23 14:00:00'),
(10, 3, 1, 'Tâche créée et assignée à Bonaventure LOUE', '2026-02-23 10:00:00'),
(11, 3, 2, 'Statut changé : en_attente → en_cours', '2026-02-24 08:30:00'),
(12, 4, 1, 'Tâche créée et assignée à Aminata SAWADOGO', '2026-02-23 11:00:00'),
(13, 5, 1, 'Tâche créée et assignée à Moussa TRAORE', '2026-02-01 08:00:00'),
(14, 5, 5, 'Statut changé : en_attente → en_cours', '2026-02-03 09:00:00'),
(15, 6, 1, 'Tâche créée et assignée à Bonaventure LOUE', '2026-02-10 09:00:00'),
(16, 7, 1, 'Tâche créée et assignée à Aminata SAWADOGO', '2026-02-23 14:00:00'),
(17, 8, 3, 'Tâche créée et assignée à Moussa TRAORE', '2026-02-20 10:00:00'),
(18, 8, 5, 'Statut changé : en_attente → en_cours', '2026-02-21 08:00:00'),
(19, 9, 1, 'Tâche créée et assignée à Aminata SAWADOGO', '2026-02-15 08:00:00'),
(20, 9, 4, 'Statut changé : en_attente → en_cours', '2026-02-17 10:00:00'),
(21, 9, 4, 'Statut changé : en_cours → termine', '2026-02-20 16:00:00'),
(22, 10, 1, 'Tâche créée — non assignée', '2026-02-23 15:00:00'),
(23, 2, 3, 'Statut changé en « En attente »', '2026-03-03 18:50:18');


INSERT INTO `notifications` (`id_notif`, `id_responsable`, `message`, `lu`, `date_envoi`) VALUES
(1, 1, 'Vous avez été assigné à la tâche : « Creation d&#039;utilisateur »', 0, '2026-02-23 23:23:37'),
(2, 3, 'Vous avez été assigné à la tâche : « Effectuer la 1ere tache »', 1, '2026-02-23 23:24:46'),
(3, 1, 'test test a marqué la tâche « Effectuer la 1ere tache » comme terminée.', 0, '2026-02-23 23:25:27'),
(4, 4, 'Vous avez été assignée à la tâche : « Rédiger le rapport mensuel »', 0, '2026-02-23 11:01:00'),
(5, 4, 'Vous avez été assignée à la tâche : « Archivage des documents 2025 »', 1, '2026-02-23 14:01:00'),
(6, 5, 'Vous avez été assigné à la tâche : « Mise à jour des procédures »', 1, '2026-02-01 08:01:00'),
(7, 5, 'La tâche « Mise à jour des procédures » est en retard depuis le 10/02/2026', 0, '2026-02-11 08:00:00'),
(8, 5, 'Vous avez été assigné à la tâche : « Optimisation des coûts opérationnels »', 0, '2026-02-20 10:01:00'),
(9, 3, 'Vous avez été assigné à la tâche : « Effectuer la 1ere tache »', 1, '2026-02-23 09:01:00'),
(11, 2, 'Vous avez été assigné à la tâche : « Audit du système de gestion »', 0, '2026-02-23 10:01:00'),
(12, 2, 'Vous avez été assigné à la tâche : « Formation sur le nouveau logiciel »', 0, '2026-02-10 09:01:00'),
(13, 2, 'La tâche « Formation sur le nouveau logiciel » est en retard depuis le 15/02/2026', 0, '2026-02-16 08:00:00');







INSERT INTO `automatisation` (`id_auto`, `nom_regle`, `condition_auto`, `action_auto`, `actif`, `date_creation`) VALUES
(1, 'Alerte retard automatique', 'statut != \"termine\" AND date_echeance < CURDATE()', 'Envoyer une notification de retard au responsable assigné', 1, '2026-02-01 08:00:00'),
(2, 'Rappel échéance J-3', 'statut != \"termine\" AND date_echeance = DATE_ADD(CURDATE(), INTERVAL 3 DAY)', 'Envoyer un rappel d\'échéance au responsable 3 jours avant', 1, '2026-02-01 08:00:00'),
(3, 'Notification nouvelle assignation', 'id_responsable IS NOT NULL AND date_creation = CURDATE()', 'Notifier le responsable lors de l\'assignation d\'une nouvelle tâche', 1, '2026-02-01 08:00:00'),
(4, 'Clôture automatique tâches basses', 'priorite = \"basse\" AND statut = \"en_cours\" AND date_echeance < DATE_SUB(CURDATE(), INTERVAL 30 DAY)', 'Marquer automatiquement comme terminé après 30 jours de retard', 0, '2026-02-15 10:00:00'),
(5, 'Escalade tâches haute priorité', 'priorite = \"haute\" AND statut = \"en_attente\" AND date_creation < DATE_SUB(CURDATE(), INTERVAL 7 DAY)', 'Notifier l\'admin si une tâche haute priorité reste en attente plus de 7 jours', 1, '2026-02-20 09:00:00');

