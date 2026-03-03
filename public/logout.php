<?php
session_start();

require_once '../config/database.php';
require_once '../classes/Utilisateur.php';

if (Utilisateur::estConnecte()) {
    $db          = (new Database())->connect();
    $utilisateur = new Utilisateur($db);
    $utilisateur->deconnecter();
}

header('Location: index.php');
exit;