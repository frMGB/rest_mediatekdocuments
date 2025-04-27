<?php
header('Content-Type: application/json');

include_once("MyAccessBDD.php");

/**
 * Contrôleur : reçoit et traite les demandes du point d'entrée
 */
class Controle
{

    /**
     *
     * @var MyAccessBDD
     */
    private $myAaccessBDD;

    /**
     * constructeur : récupère l'instance d'accès à la BDD
     */
    public function __construct()
    {
        try {
            $this->myAaccessBDD = new MyAccessBDD();
        } catch (Exception $e) {
            $this->reponse(500, "erreur serveur");
            die();
        }
    }

    /**
     * réception d'une demande de requête
     * demande de traiter la requête puis demande d'afficher la réponse
     * @param string $methodeHTTP
     * @param string $table
     * @param string|null $id
     * @param array|null $champs
     */
    public function demande(string $methodeHTTP, string $table, ?string $id, ?array $champs)
    {
        $result = $this->myAaccessBDD->demande($methodeHTTP, $table, $id, $champs);
        $this->controleResult($result);
    }

    /**
     * Gère l'authentification d'un utilisateur
     * @param array|null $credentials Contient 'login' et 'password'
     */
    public function authenticate(?array $credentials)
    {
        if (empty($credentials) || !isset($credentials['login']) || !isset($credentials['password'])) {
            $this->reponse(400, "Identifiants manquants");
            return;
        }

        $login = $credentials['login'];
        $password = $credentials['password'];

        // Appeler MyAccessBDD pour vérifier les identifiants
        $userData = $this->myAaccessBDD->verifyUserCredentials($login, $password);

        if ($userData) {
            // Authentification réussie, renvoyer les informations nécessaires (dont idService)
            // Ne pas renvoyer le mot de passe !
            unset($userData['password']);
            $this->reponse(200, "Authentification réussie", $userData);
        } else {
            // Authentification échouée
            $this->unauthorized(); // Utilise la méthode existante pour réponse 401
        }
    }

    /**
     * Gère les requêtes à la racine de l'API
     * Retourne des informations générales sur l'API
     */
    public function accueil()
    {
        $infoAPI = [
            'nom' => 'MediaTek86 API',
            'version' => '1.0',
            'description' => 'API REST pour la gestion de la médiathèque',
            'endpoints' => [
                'GET /livre' => 'Liste tous les livres',
                'GET /dvd' => 'Liste tous les DVD',
                'GET /revue' => 'Liste toutes les revues',
                'GET /commandedocument/{id}' => 'Liste les commandes d\'un document (livre ou DVD)',
                'GET /commanderevue/{id}' => 'Liste les commandes (abonnements) d\'une revue',
                'GET /abofinproche' => 'Liste les abonnements de revues se terminant dans moins de 30 jours',
                'GET /suivi' => 'Liste les étapes de suivi des commandes',
                'POST /commandedocument' => 'Ajoute une commande de document',
                'POST /commanderevue' => 'Ajoute une commande de revue',
                'PUT /commandedocument/{id}' => 'Met à jour l\'étape de suivi d\'une commande',
                'DELETE /commandedocument/{id}' => 'Supprime une commande de document',
                'DELETE /commanderevue/{id}' => 'Supprime une commande de revue'
            ]
        ];

        $this->reponse(200, "OK", $infoAPI);
    }

    /**
     * réponse renvoyée (affichée) au client au format json
     * @param int $code code standard HTTP (200, 500, ...)
     * @param string $message message correspondant au code
     * @param array|int|string|null $result
     */
    public function reponse(int $code, string $message, array|int|string|null $result = "")
    {
        $retour = array(
            'code' => $code,
            'message' => $message,
            'result' => $result
        );
        echo json_encode($retour, JSON_UNESCAPED_UNICODE);
    }

    /**
     * contrôle si le résultat n'est pas null
     * demande l'affichage de la réponse adéquate
     * @param array|int|null $result résultat de la requête
     */
    private function controleResult(array|int|null $result)
    {
        if (!is_null($result)) {
            $this->reponse(200, "OK", $result);
        } else {
            $this->reponse(400, "requete invalide");
        }
    }

    /**
     * authentification incorrecte
     * demande d'afficher un messaage d'erreur
     */
    public function unauthorized()
    {
        $this->reponse(401, "authentification incorrecte");
    }
}
