<?php
/*
 * Index.php : point d'entrée de l'API
 * - contrôle l'authentification
 * - Récupère les variables envoyées (dans l'URL ou le body)
 * - récupère la méthode d'envoi HTTP (GET, POST, PUT, DELETE)
 * - demande au contrôleur de gérer la demande
 */
include_once("Url.php");
include_once("Controle.php");

// crée l'objet d'accès aux informations de l'URL qui sollicite l'API
$url = Url::getInstance();
// crée l'objet d'accès au contrôleur
$controle = new Controle();

// vérifie l'authentification
if (!$url->authentification()) {
    // l'authentification a échoué
    $controle->unauthorized();
} else {
    // récupère la méthode HTTP utilisée pour accéder à l'API
    $methodeHTTP = $url->recupMethodeHTTP();
    //récupère les données passées dans l'url (visibles ou cachées)
    $table = $url->recupVariable("table");
    $id = $url->recupVariable("id"); // Principalement pour PUT /table/id

    // Tenter de récupérer les champs depuis le segment JSON de l'URL ou la query string
    $champs = $url->recupVariable("champs", "json");

    // Pour POST, PUT, DELETE, si les champs n'ont pas été fournis via le segment JSON de l'URL
    // ou via un paramètre 'champs' dans la query string, récupérer toutes les données
    // (cela couvre les cas où les données sont dans le body ou en query string simple id=...)
    if (in_array($methodeHTTP, ['POST', 'PUT', 'DELETE'])) {
        if (is_null($champs)) {
            $champs = $url->getAllData();
        }
    }

    // Si on accède à la racine de l'API, on retourne des informations générales
    if (empty($table) && $methodeHTTP === 'GET') {
        $controle->accueil();
    }
    // Cas spécifique de l'authentification
    else if ($table === 'auth' && $methodeHTTP === 'POST') {
        $controle->authenticate($champs);
    } else {
        // Vérifier si $table est null avant de l'utiliser
        if (is_null($table)) {
            $controle->reponse(400, "Table non spécifiée");
        } else {
            // demande au controleur de traiter la demande
            $controle->demande($methodeHTTP, $table, $id, $champs);
        }
    }
}
