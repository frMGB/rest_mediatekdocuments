<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD
{

    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct()
    {
        try {
            parent::__construct();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */
    protected function traitementSelect(string $table, ?array $champs): ?array
    {
        switch ($table) {
            case "livre":
                if (!empty($champs)) {
                    return $this->selectTuplesOneTable($table, $champs);
                } else {
                    return $this->selectAllLivres();
                }
            case "dvd":
                if (!empty($champs)) {
                    return $this->selectTuplesOneTable($table, $champs);
                } else {
                    return $this->selectAllDvd();
                }
            case "revue":
                if (!empty($champs)) {
                    return $this->selectOneRevue($champs);
                } else {
                    return $this->selectAllRevues();
                }
            case "exemplaire":
                return $this->selectExemplairesRevue($champs);
            case "genre":
            case "public":
            case "rayon":
            case "etat":
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "commandedocument":
                return $this->selectCommandesDocument($champs);
            case "commanderevue":
                return $this->selectCommandesRevue($champs);
            case "suivi":
                return $this->selectSuivi();
            case "abofinproche":
                return $this->selectAboFinProche();
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */
    protected function traitementInsert(string $table, ?array $champs): ?int
    {
        switch ($table) {
            case "commandedocument":
                return $this->insertCommandeDocument($champs);
            case "commanderevue":
                return $this->insertCommandeRevue($champs);
            default:
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);
        }
    }

    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */
    protected function traitementUpdate(string $table, ?string $id, ?array $champs): ?int
    {
        switch ($table) {
            case "commandedocument":
                return $this->updateSuiviCommandeDocument($id, $champs);
            default:
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }
    }

    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */
    protected function traitementDelete(string $table, ?array $champs): ?int
    {
        switch ($table) {
            case "commandedocument":
                return $this->deleteCommandeDocument($champs);
            case "commanderevue":
                return $this->deleteCommandeRevue($champs);
            default:
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);
        }
    }

    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null
     */
    private function selectTuplesOneTable(string $table, ?array $champs): ?array
    {
        if (empty($champs)) {
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);
        } else {
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value) {
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete) - 5);
            return $this->conn->queryBDD($requete, $champs);
        }
    }

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */
    private function insertOneTupleOneTable(string $table, ?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value) {
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $requete .= ") values (";
        foreach ($champs as $key => $value) {
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        if (is_null($id)) {
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value) {
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $champs["id"] = $id;
        $requete .= " where id=:id;";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value) {
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete) - 5);
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table): ?array
    {
        $requete = "select * from $table order by libelle;";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres(): ?array
    {
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd(): ?array
    {
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues(): ?array
    {
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs): ?array
    {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * récupère toutes les commandes d'un document (livre ou DVD)
     * @param array|null $champs
     * @return array|null
     */
    private function selectCommandesDocument(?array $champs): ?array
    {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "SELECT c.id, c.dateCommande, c.montant, cd.nbExemplaire, cd.idSuivi, s.etape AS LibelleSuivi ";
        $requete .= "FROM commande c ";
        $requete .= "JOIN commandedocument cd ON c.id = cd.id ";
        $requete .= "JOIN suivi s ON cd.idSuivi = s.id ";
        $requete .= "WHERE cd.idLivreDvd = :id ";
        $requete .= "ORDER BY c.dateCommande DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * récupère toutes les commandes (abonnements) d'une revue
     * @param array|null $champs
     * @return array|null
     */
    private function selectCommandesRevue(?array $champs): ?array
    {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "SELECT c.id, c.dateCommande, c.montant, a.dateFinAbonnement ";
        $requete .= "FROM commande c ";
        $requete .= "JOIN abonnement a ON c.id = a.id ";
        $requete .= "WHERE a.idRevue = :id ";
        $requete .= "ORDER BY c.dateCommande DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Insère une nouvelle commande de document (livre ou DVD)
     * @param array|null $champs
     * @return int|null
     */
    private function insertCommandeDocument(?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }

        // Vérification des champs requis
        $requiredFields = ['dateCommande', 'montant', 'nbExemplaire', 'idLivreDvd'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $champs)) {
                return null;
            }
        }

        // Démarrer une transaction
        $this->conn->updateBDD("START TRANSACTION;");

        try {
            // Générer un nouvel ID unique de 5 caractères
            $id = bin2hex(random_bytes(3));
            $id = substr($id, 0, 5);

            // Insérer dans la table commande
            $commandeData = [
                'id' => $id,
                'dateCommande' => $champs['dateCommande'],
                'montant' => $champs['montant']
            ];

            $resultCommande = $this->insertOneTupleOneTable('commande', $commandeData);

            if ($resultCommande === null) {
                $this->conn->updateBDD("ROLLBACK;");
                return null;
            }

            // Insérer dans la table commandedocument
            $commandeDocData = [
                'id' => $id,
                'nbExemplaire' => $champs['nbExemplaire'],
                'idLivreDvd' => $champs['idLivreDvd'],
                'idSuivi' => 1 // "en cours"
            ];

            $resultCommandeDoc = $this->insertOneTupleOneTable('commandedocument', $commandeDocData);

            if ($resultCommandeDoc === null) {
                $this->conn->updateBDD("ROLLBACK;");
                return null;
            }

            // Valider la transaction
            $this->conn->updateBDD("COMMIT;");
            return 1;

        } catch (\Exception $e) {
            $this->conn->updateBDD("ROLLBACK;");
            return null;
        }
    }

    /**
     * Insère une nouvelle commande de revue (abonnement)
     * @param array|null $champs
     * @return int|null
     */
    private function insertCommandeRevue(?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }

        // Vérification des champs requis
        $requiredFields = ['dateCommande', 'montant', 'idRevue', 'dateFinAbonnement'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $champs)) {
                return null;
            }
        }

        // Démarrer une transaction
        $this->conn->updateBDD("START TRANSACTION;");

        try {
            // Générer un nouvel ID unique de 5 caractères
            $id = bin2hex(random_bytes(3));
            $id = substr($id, 0, 5);

            // Insérer dans la table commande
            $commandeData = [
                'id' => $id,
                'dateCommande' => $champs['dateCommande'],
                'montant' => $champs['montant']
            ];

            $resultCommande = $this->insertOneTupleOneTable('commande', $commandeData);

            if ($resultCommande === null) {
                $this->conn->updateBDD("ROLLBACK;");
                return null;
            }

            // Insérer dans la table abonnement
            $abonnementData = [
                'id' => $id,
                'dateFinAbonnement' => $champs['dateFinAbonnement'],
                'idRevue' => $champs['idRevue']
            ];

            $resultAbonnement = $this->insertOneTupleOneTable('abonnement', $abonnementData);

            if ($resultAbonnement === null) {
                $this->conn->updateBDD("ROLLBACK;");
                return null;
            }

            // Valider la transaction
            $this->conn->updateBDD("COMMIT;");
            return 1;

        } catch (\Exception $e) {
            $this->conn->updateBDD("ROLLBACK;");
            return null;
        }
    }

    /**
     * Met à jour l'étape de suivi d'une commande de document
     * @param string|null $id ID de la commande
     * @param array|null $champs
     * @return int|null
     */
    private function updateSuiviCommandeDocument(?string $id, ?array $champs): ?int
    {
        if (empty($champs) || is_null($id)) {
            return null;
        }

        if (!array_key_exists('idSuivi', $champs)) {
            return null;
        }

        // Vérifier les règles de changement d'étape
        $requete = "SELECT idSuivi FROM commandedocument WHERE id = :id";
        $param = ['id' => $id];
        $currentEtape = $this->conn->queryBDD($requete, $param);

        if (empty($currentEtape)) {
            return null;
        }

        $currentEtapeId = $currentEtape[0]['idSuivi'];
        $newEtapeId = $champs['idSuivi'];

        // Une commande livrée (2) ou réglée (3) ne peut pas revenir à une étape précédente (en cours (1) ou relancée (4))
        if (($currentEtapeId == 2 || $currentEtapeId == 3) && ($newEtapeId == 1 || $newEtapeId == 4)) {
            return 0;
        }

        // Une commande ne peut pas être réglée (3) si elle n'est pas livrée (2)
        if ($newEtapeId == 3 && $currentEtapeId != 2) {
            return 0;
        }

        // Mise à jour de l'étape
        $requete = "UPDATE commandedocument SET idSuivi = :idSuivi WHERE id = :id";
        $param = [
            'id' => $id,
            'idSuivi' => $newEtapeId
        ];

        return $this->conn->updateBDD($requete, $param);
    }

    /**
     * Supprime une commande de document
     * @param array|null $champs
     * @return int|null
     */
    private function deleteCommandeDocument(?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }

        if (!array_key_exists('id', $champs)) {
            return null;
        }

        // Vérifier que la commande n'est pas encore livrée
        $requete = "SELECT idSuivi FROM commandedocument WHERE id = :id";
        $param = ['id' => $champs['id']];
        $result = $this->conn->queryBDD($requete, $param);

        if (empty($result)) {
            return null;
        }

        $etapeId = $result[0]['idSuivi'];

        // Si la commande est livrée (2) ou réglée (3), on ne peut pas la supprimer
        if ($etapeId == 2 || $etapeId == 3) {
            return 0;
        }

        // Supprimer la commande
        return $this->deleteTuplesOneTable('commande', $champs);
    }

    /**
     * Supprime une commande de revue
     * @param array|null $champs
     * @return int|null
     */
    private function deleteCommandeRevue(?array $champs): ?int
    {
        if (empty($champs) || !array_key_exists('id', $champs)) {
            return null;
        }

        $idToDelete = $champs['id'];

        $this->conn->updateBDD("START TRANSACTION;");

        try {
            // 1. Vérifier qu'aucun exemplaire n'est rattaché à cet abonnement
            $requete = "SELECT a.id, a.dateFinAbonnement, a.idRevue, c.dateCommande
                        FROM abonnement a
                        JOIN commande c ON a.id = c.id
                        WHERE a.id = :id";
            $param = ['id' => $idToDelete];
            $abonnement = $this->conn->queryBDD($requete, $param);

            if (empty($abonnement)) {
                // Annuler la transaction, l'abonnement n'existe pas ou la requête a échoué
                $this->conn->updateBDD("ROLLBACK;");
                return null;
            }

            $dateCommande = $abonnement[0]['dateCommande'];
            $dateFinAbonnement = $abonnement[0]['dateFinAbonnement'];
            $idRevue = $abonnement[0]['idRevue'];

            $requete = "SELECT COUNT(*) AS nbExemplaires
                        FROM exemplaire
                        WHERE id = :idRevue
                        AND dateAchat BETWEEN :dateCommande AND :dateFinAbonnement";
            $param = [
                'idRevue' => $idRevue,
                'dateCommande' => $dateCommande,
                'dateFinAbonnement' => $dateFinAbonnement
            ];
            $exemplaires = $this->conn->queryBDD($requete, $param);

            // Vérifier si la requête a échoué
            if ($exemplaires === null) {
                $this->conn->updateBDD("ROLLBACK;");
                return null;
            }

            if ($exemplaires[0]['nbExemplaires'] > 0) {
                // Annuler la transaction, impossible de supprimer car des exemplaires existent
                $this->conn->updateBDD("ROLLBACK;");
                return 0;
            }

            // 2. Supprimer d'abord l'abonnement
            $requeteAbo = "DELETE FROM abonnement WHERE id = :id";
            $paramAbo = ['id' => $idToDelete];
            $resultAbo = $this->conn->updateBDD($requeteAbo, $paramAbo);

            // Si la suppression de l'abonnement a échoué
            if ($resultAbo === null || $resultAbo === 0) {
                $this->conn->updateBDD("ROLLBACK;");
                return null;
            }

            // 3. Puis supprimer la commande
            $resultCmd = $this->deleteTuplesOneTable('commande', ['id' => $idToDelete]);

            // Si la suppression de la commande a échoué
            if ($resultCmd === null || $resultCmd === 0) {
                $this->conn->updateBDD("ROLLBACK;");
                return null;
            }

            // Si les deux suppressions ont réussi, valider la transaction
            $this->conn->updateBDD("COMMIT;");
            // Retourne le nombre de lignes supprimées de commande (devrait être 1)
            return $resultCmd;

        } catch (\Exception $e) {
            // Annuler la transaction en cas d'exception
            $this->conn->updateBDD("ROLLBACK;");
            return null;
        }
    }

    /**
     * Vérifie si une date de parution est comprise dans la période d'un abonnement
     * @param string $dateCommande
     * @param string $dateFinAbonnement
     * @param string $dateParution
     * @return bool
     */
    public function parutionDansAbonnement(string $dateCommande, string $dateFinAbonnement, string $dateParution): bool
    {
        return $dateParution >= $dateCommande && $dateParution <= $dateFinAbonnement;
    }

    /**
     * Récupère la liste des abonnements de revues se terminant dans moins de 30 jours
     * @return array|null
     */
    private function selectAboFinProche(): ?array
    {
        $requete = "SELECT a.id, a.dateFinAbonnement, a.idRevue, d.titre, r.periodicite, r.delaiMiseADispo
                    FROM abonnement a
                    JOIN revue r ON a.idRevue = r.id
                    JOIN document d ON r.id = d.id
                    WHERE a.dateFinAbonnement BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                    ORDER BY a.dateFinAbonnement ASC";

        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Suivi
     * @return array|null
     */
    private function selectSuivi(): ?array
    {
        $requete = "select * from suivi order by etape;";
        return $this->conn->queryBDD($requete);
    }

    /**
     * Vérifie les identifiants d'un utilisateur.
     * @param string $login
     * @param string $password Mot de passe en clair fourni par l'utilisateur
     * @return array|false Retourne les données de l'utilisateur (sans le mot de passe) si succès, sinon false.
     */
    public function verifyUserCredentials(string $login, string $password): array|false
    {
        // Récupérer l'utilisateur par son login
        $requete = "SELECT u.id, u.login, u.password, u.nom, u.prenom, u.mail, u.idService, s.libelle as service
                    FROM utilisateur u
                    JOIN service s ON u.idService = s.id
                    WHERE u.login = :login";
        $param = ['login' => $login];
        $user = $this->conn->queryBDD($requete, $param);

        // Vérifier si l'utilisateur existe
        if (empty($user)) {
            return false;
        }

        $userData = $user[0];
        $hashedPassword = $userData['password'];

        // Vérifier le mot de passe fourni avec le hash stocké
        if (password_verify($password, $hashedPassword)) {
            return $userData;
        } else {
            return false;
        }
    }

    /**
     * récupère une seule revue et les tables associées par son ID
     * @param array|null $champs Doit contenir la clé 'id'
     * @return array|null
     */
    private function selectOneRevue(?array $champs): ?array
    {
        if (empty($champs) || !array_key_exists('id', $champs)) {
            return null;
        }
        $param = ['id' => $champs['id']];
        $requete = "SELECT r.id, r.periodicite, d.titre, d.image, r.delaiMiseADispo,
                    d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, ry.libelle as rayon
                    FROM revue r
                    JOIN document d ON r.id=d.id
                    JOIN genre g ON g.id=d.idGenre
                    JOIN public p ON p.id=d.idPublic
                    JOIN rayon ry ON ry.id=d.idRayon
                    WHERE r.id = :id";
        return $this->conn->queryBDD($requete, $param);
    }
}
