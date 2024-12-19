<?php
// Utilisation de l'espace de nom MyApp lié au composer.json
namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $games;

    // Constructeur pour initialiser la liste des clients
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->games = [];
    }

    // Méthode appelée lorsqu'un client se connecte
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nouvelle connexion! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg);
        echo "Message reçu: " . $msg . "\n"; // Log pour déboguer
        
        switch ($data->type) {
            case 'createGame':
                $this->handleCreateGame($from, $data);
                break;
            
            case 'joinGame':
                $this->handleJoinGame($from, $data);
                break;
            
            case 'move':
                $this->handleMove($from, $data);
                break;
        }
    }

    protected function handleCreateGame($from, $data) {
        $gameId = uniqid();
        $this->games[$gameId] = [
            'player1' => [
                'conn' => $from,
                'name' => $data->playerName,
                'avatar' => $data->avatar
            ],
            'player2' => null,
            'board' => array_fill(0, 6, array_fill(0, 7, null)),
            'currentPlayer' => 1
        ];

        echo "Nouvelle partie créée avec l'ID: {$gameId}\n";

        $from->send(json_encode([
            'type' => 'gameCreated',
            'gameId' => $gameId,
            'playerInfo' => [
                'player1' => [
                    'name' => $data->playerName,
                    'avatar' => $data->avatar
                ]
            ]
        ]));
    }

    protected function handleJoinGame($from, $data) {
        echo "Tentative de rejoindre la partie: {$data->gameId}\n";

        // Vérifier si l'ID de partie est défini dans la requête
        if (!isset($data->gameId)) {
            echo "Erreur: ID de partie manquant\n";
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'ID de partie manquant'
            ]));
            return;
        }

        // Vérifier si la partie existe
        if (!isset($this->games[$data->gameId])) {
            echo "Erreur: Partie non trouvée - ID: {$data->gameId}\n";
            echo "Parties disponibles: " . implode(', ', array_keys($this->games)) . "\n";
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Partie non trouvée. Vérifiez l\'ID de la partie.'
            ]));
            return;
        }

        $game = &$this->games[$data->gameId];
        
        // Vérifier si la partie n'est pas déjà complète
        if ($game['player2'] !== null) {
            echo "Erreur: Partie déjà complète\n";
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'La partie est déjà complète'
            ]));
            return;
        }

        // Ajout du joueur 2 avec toutes les informations nécessaires
        $game['player2'] = [
            'conn' => $from,
            'name' => $data->playerName,
            'avatar' => $data->avatar
        ];

        // Mettre à jour le gameId pour le joueur 2
        $from->gameId = $data->gameId;

        echo "Joueur 2 ({$data->playerName}) a rejoint la partie {$data->gameId}\n";

        // Informer les deux joueurs que la partie peut commencer
        $playerInfo = [
            'player1' => [
                'name' => $game['player1']['name'],
                'avatar' => $game['player1']['avatar']
            ],
            'player2' => [
                'name' => $data->playerName,
                'avatar' => $data->avatar
            ]
        ];

        $messageForPlayers = json_encode([
            'type' => 'gameJoined',
            'gameId' => $data->gameId,  // Ajout de l'ID de partie dans la réponse
            'playerInfo' => $playerInfo
        ]);

        try {
            $game['player1']['conn']->send($messageForPlayers);
            $from->send($messageForPlayers);
            echo "Messages envoyés aux deux joueurs\n";
        } catch (\Exception $e) {
            echo "Erreur lors de l'envoi des messages: " . $e->getMessage() . "\n";
        }
    }

    protected function handleMove($from, $data) {
        if (!isset($this->games[$data->gameId])) {
            echo "Erreur: Tentative de jouer dans une partie inexistante\n";
            return;
        }

        $game = &$this->games[$data->gameId];
        
        // Vérifier si c'est bien le tour du joueur
        if ($game['currentPlayer'] !== $data->player) {
            echo "Erreur: Ce n'est pas le tour du joueur\n";
            return;
        }

        // Envoyer le mouvement aux deux joueurs
        $moveMessage = json_encode([
            'type' => 'move',
            'col' => $data->col,
            'player' => $data->player
        ]);

        try {
            $game['player1']['conn']->send($moveMessage);
            if ($game['player2']) {
                $game['player2']['conn']->send($moveMessage);
            }
            echo "Mouvement effectué: Colonne {$data->col} par Joueur {$data->player}\n";
        } catch (\Exception $e) {
            echo "Erreur lors de l'envoi du mouvement: " . $e->getMessage() . "\n";
        }

        // Changer de joueur
        $game['currentPlayer'] = $game['currentPlayer'] === 1 ? 2 : 1;
    }

    // Méthode appelée lorsqu'un client se déconnecte
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Gérer la déconnexion d'un joueur
        foreach ($this->games as $gameId => $game) {
            if (($game['player1'] && $game['player1']['conn'] === $conn) ||
                ($game['player2'] && $game['player2']['conn'] === $conn)) {
                
                echo "Un joueur s'est déconnecté de la partie {$gameId}\n";
                
                // Informer l'autre joueur de la déconnexion
                try {
                    if ($game['player1'] && $game['player1']['conn'] !== $conn) {
                        $game['player1']['conn']->send(json_encode([
                            'type' => 'playerDisconnected'
                        ]));
                    }
                    if ($game['player2'] && $game['player2']['conn'] !== $conn) {
                        $game['player2']['conn']->send(json_encode([
                            'type' => 'playerDisconnected'
                        ]));
                    }
                } catch (\Exception $e) {
                    echo "Erreur lors de la notification de déconnexion: " . $e->getMessage() . "\n";
                }
                
                // Supprimer la partie
                unset($this->games[$gameId]);
            }
        }

        echo "Connexion {$conn->resourceId} fermée\n";
    }

    // Méthode appelée lorsqu'une erreur survient
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Une erreur est survenue : {$e->getMessage()}\n";
        $conn->close();
    }
}
?>