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
                return $this->selectAllLivres($champs);
            case "dvd":
                return $this->selectAllDvd($champs);
            case "revue":
                return $this->selectAllRevues();
            case "exemplaire":
                return $this->selectExemplairesRevue($champs);
            case "commandedocument":
                return $this->selectCommandesLivre($champs);
            case "genre":
            case "public":
            case "rayon":
            case "etat":
            case "suivi":
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "":
            // return $this->uneFonction(parametres);
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
            case "exemplaire":
                return $this->insertOneTupleOneTable($table, $champs);
            case "":
            // return $this->uneFonction(parametres);
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
            case "":
            // return $this->uneFonction(parametres);
            default:
                // cas général
                error_log("traitementUpdate: Cas général appelé pour table '$table', ID '$id'. La fonction updateOneTupleOneTable pourrait échouer si le format des champs n'est pas direct.");
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
            case "commande":
                return $this->deleteCommande($champs);
            case "":
            // return $this->uneFonction(parametres);
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
            // enlève le dernier and
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
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle, ou id et etape pour suivi)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table): ?array
    {
        // Adapte la requête pour la table 'suivi' qui a 'etape' au lieu de 'libelle'
        $orderByField = ($table === 'suivi') ? 'etape' : 'libelle';
        $requete = "select * from $table order by $orderByField;";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * peut filtrer par id si fourni dans $champs
     * @param array|null $champs
     * @return array|null
     */
    private function selectAllLivres(?array $champs = null): ?array
    {
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";

        $params = [];
        if (!empty($champs) && isset($champs['id'])) {
            $requete .= "WHERE l.id = :id ";
            $params['id'] = $champs['id'];
        }

        $requete .= "order by titre;";

        return $this->conn->queryBDD($requete, $params);
    }

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * peut filtrer par id si fourni dans $champs
     * @param array|null $champs
     * @return array|null
     */
    private function selectAllDvd(?array $champs = null): ?array
    {
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";

        $params = [];
        if (!empty($champs) && isset($champs['id'])) {
            $requete .= "WHERE l.id = :id ";
            $params['id'] = $champs['id'];
        }

        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete, $params);
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
     * Récupère les commandes d'un livre ou DVD spécifique avec les informations jointes
     * @param array|null $champs Doit contenir la clé 'idLivreDvd'
     * @return array|null
     */
    private function selectCommandesLivre(?array $champs): ?array
    {
        // Vérification du paramètre requis
        if (empty($champs) || !isset($champs['idLivreDvd'])) {
            error_log("selectCommandesLivre: Paramètre 'idLivreDvd' manquant.");
            return null;
        }

        $params = ['idLivreDvd' => $champs['idLivreDvd']];

        // Requête joignant commande, commandedocument et suivi
        $requete = "SELECT
                        c.id AS IdCommande,
                        c.dateCommande AS DateCommande,
                        c.montant AS Montant,
                        cd.nbExemplaire AS NbExemplaire,
                        cd.idLivreDvd AS IdLivreDvd,
                        cd.idSuivi AS IdSuivi,
                        s.etape AS LibelleSuivi
                    FROM commande c
                    JOIN commandedocument cd ON c.id = cd.id
                    JOIN suivi s ON cd.idSuivi = s.id
                    WHERE cd.idLivreDvd = :idLivreDvd
                    ORDER BY c.dateCommande DESC";

        return $this->conn->queryBDD($requete, $params);
    }

    /**
     * Insère une nouvelle commande et sa ligne de document associée
     * @param array|null $champs Doit contenir les clés 'commande' et 'commandeDoc' avec des chaînes JSON
     * @return int|null 1 si succès, 0 ou null si erreur
     */
    private function insertCommandeDocument(?array $champs): ?int
    {
        // Vérification des paramètres requis
        if (empty($champs) || !isset($champs['commande']) || !isset($champs['commandeDoc'])) {
            error_log("insertCommandeDocument: Paramètres 'commande' ou 'commandeDoc' manquants ou vides.");
            return null;
        }

        // Décodage des chaînes JSON
        $commandeData = json_decode($champs['commande'], true);
        $commandeDocData = json_decode($champs['commandeDoc'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("insertCommandeDocument: Erreur de décodage JSON - " . json_last_error_msg());
            return null;
        }

        // Génération d'un ID unique
        $newId = substr(uniqid(), -5);

        // Préparation des données pour l'insertion dans 'commande'
        $paramsCommande = [
            'id' => $newId,
            'dateCommande' => $commandeData['DateCommande'],
            'montant' => $commandeData['Montant']
        ];

        // Préparation des données pour l'insertion dans 'commandedocument'
        $paramsCommandeDoc = [
            'id' => $newId,
            'nbExemplaire' => $commandeDocData['NbExemplaire'],
            'idLivreDvd' => $commandeDocData['IdLivreDvd'],
            'idSuivi' => $commandeDocData['IdSuivi']
        ];

        // Début de la transaction
        // Accès à PDO via le getter pour la transaction
        $pdo = $this->conn->getPDO();
        if (!$pdo->beginTransaction()) {
            error_log("insertCommandeDocument: Impossible de démarrer la transaction.");
            return null;
        }

        try {
            // 1. Insertion dans la table 'commande'
            $sqlCommande = "INSERT INTO commande (id, dateCommande, montant) VALUES (:id, :dateCommande, :montant)";
            $resultCommande = $this->conn->updateBDD($sqlCommande, $paramsCommande);

            if ($resultCommande === null || $resultCommande < 1) {
                // Erreur lors de l'insertion dans commande, rollback
                error_log("insertCommandeDocument: Echec insertion commande pour ID {$newId}");
                $pdo->rollback();
                return null;
            }

            // 2. Insertion dans la table 'commandedocument'
            $sqlCommandeDoc = "INSERT INTO commandedocument (id, nbExemplaire, idLivreDvd, idSuivi) VALUES (:id, :nbExemplaire, :idLivreDvd, :idSuivi)";
            $resultCommandeDoc = $this->conn->updateBDD($sqlCommandeDoc, $paramsCommandeDoc);

            if ($resultCommandeDoc === null || $resultCommandeDoc < 1) {
                // Erreur lors de l'insertion dans commandedocument, rollback
                error_log("insertCommandeDocument: Echec insertion commandedocument pour ID {$newId}");
                $pdo->rollback();
                return null;
            }

            // Si tout s'est bien passé, commit
            if ($pdo->commit()) {
                error_log("insertCommandeDocument: Succès pour ID {$newId}");
                return 1;
            } else {
                error_log("insertCommandeDocument: Echec commit pour ID {$newId}");
                $pdo->rollback();
                return null;
            }

        } catch (\Exception $e) {
            error_log("insertCommandeDocument: Exception pour ID {$newId} - " . $e->getMessage());
            $pdo->rollback();
            return null;
        }
    }

    /**
     * Met à jour l'étape de suivi d'une commande document
     * @param string|null $id ID de la commande document
     * @param array|null $champs Doit contenir la clé 'champs' avec une chaîne JSON contenant {"idSuivi": N}
     * @return int|null
     */
    private function updateSuiviCommandeDocument(?string $id, ?array $champs): ?int
    {
        // --- Ajout Logs ---
        error_log("updateSuiviCommandeDocument: Reçu ID = " . ($id ?? 'NULL'));
        error_log("updateSuiviCommandeDocument: Reçu \$champs = " . print_r($champs, true));
        // --- Fin Logs ---

        // Vérification des paramètres
        if (is_null($id) || empty($champs) || !isset($champs['idSuivi'])) {
            error_log("updateSuiviCommandeDocument: ID ou paramètre 'idSuivi' manquant dans \$champs.");
            return null;
        }

        // Préparation des paramètres pour la requête UPDATE
        $params = [
            'id' => $id,
            'idSuivi' => $champs['idSuivi']
        ];

        // Construction et exécution de la requête
        $requete = "UPDATE commandedocument SET idSuivi = :idSuivi WHERE id = :id";

        try {
            $result = $this->conn->updateBDD($requete, $params);
            error_log("updateSuiviCommandeDocument: Résultat updateBDD pour ID $id avec idSuivi {$params['idSuivi']} -> " . ($result ?? 'NULL'));
            return $result;
        } catch (\Exception $e) {
            error_log("updateSuiviCommandeDocument: Exception pour ID $id - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Supprime une commande. La suppression dans `commandedocument` est gérée par un trigger SQL
     * @param array|null $champs Doit contenir la clé 'id' avec l'ID de la commande à supprimer
     * @return int|null
     */
    private function deleteCommande(?array $champs): ?int
    {
        // Vérification du paramètre 'id'
        if (empty($champs) || !isset($champs['id'])) {
            error_log("deleteCommande (Trigger): Paramètre 'id' manquant.");
            return null;
        }

        $idCommande = $champs['id'];
        $params = ['id' => $idCommande];

        try {
            // Suppression directe dans la table 'commande'. Le trigger s'occupe de 'commandedocument'
            $sqlDeleteCmd = "DELETE FROM commande WHERE id = :id";
            $resultDeleteCmd = $this->conn->updateBDD($sqlDeleteCmd, $params);

            if ($resultDeleteCmd === null) {
                error_log("deleteCommande (Trigger): Erreur lors de la suppression dans commande pour ID {$idCommande}");
                return null;
            } elseif ($resultDeleteCmd === 0) {
                error_log("deleteCommande (Trigger): Aucune commande trouvée avec l'ID {$idCommande}.");
                return 1;
            } else {
                error_log("deleteCommande (Trigger): Succès suppression commande ID {$idCommande}");
                return $resultDeleteCmd;
            }

        } catch (\Exception $e) {
            error_log("deleteCommande (Trigger): Exception pour ID {$idCommande} - " . $e->getMessage());
            return null;
        }
    }

}
