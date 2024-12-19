<!DOCTYPE html>
<html>
<head>
    <title>Puissance 4 - WebSocket</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="./style.css">
</head>
<body>
    <!-- Container principal -->
    <div id="game-container">
        <!-- Page d'accueil -->
        <div id="welcome-screen">
            <h1>Puissance 4 en ligne</h1>
            
            <!-- Formulaire de création de partie -->
            <div class="game-form" id="create-game-form">
                <h2>Créer une nouvelle partie</h2>
                <form id="new-game-form">
                    <input type="text" id="player1-name" placeholder="Votre pseudo" required>
                    
                    <!-- Sélection d'avatar -->
                    <div class="avatar-selection">
                        <h3>Choisissez votre avatar</h3>
                        <div class="avatar-options">
                            <label>
                                <input type="radio" name="avatar" value="chat" required>
                                <span class="avatar-img">🐱</span>
                            </label>
                            <label>
                                <input type="radio" name="avatar" value="chien">
                                <span class="avatar-img">🐶</span>
                            </label>
                            <label>
                                <input type="radio" name="avatar" value="lapin">
                                <span class="avatar-img">🐰</span>
                            </label>
                            <label>
                                <input type="radio" name="avatar" value="renard">
                                <span class="avatar-img">🦊</span>
                            </label>
                            <label>
                                <input type="radio" name="avatar" value="panda">
                                <span class="avatar-img">🐼</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit">Créer la partie</button>
                </form>
            </div>

            <!-- Formulaire pour rejoindre une partie -->
            <div class="game-form" id="join-game-form">
                <h2>Rejoindre une partie</h2>
                <form id="join-form">
                    <input type="text" id="player2-name" placeholder="Votre pseudo" required>
                    <input type="text" id="game-id" placeholder="ID de la partie" required>
                    
                    <!-- Sélection d'avatar -->
                    <div class="avatar-selection">
                        <h3>Choisissez votre avatar</h3>
                        <div class="avatar-options">
                            <label>
                                <input type="radio" name="avatar2" value="chat" required>
                                <span class="avatar-img">🐱</span>
                            </label>
                            <label>
                                <input type="radio" name="avatar2" value="chien">
                                <span class="avatar-img">🐶</span>
                            </label>
                            <label>
                                <input type="radio" name="avatar2" value="lapin">
                                <span class="avatar-img">🐰</span>
                            </label>
                            <label>
                                <input type="radio" name="avatar2" value="renard">
                                <span class="avatar-img">🦊</span>
                            </label>
                            <label>
                                <input type="radio" name="avatar2" value="panda">
                                <span class="avatar-img">🐼</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit">Rejoindre la partie</button>
                </form>
            </div>
        </div>

        <!-- Zone de jeu (initialement cachée) -->
        <div id="game-screen" style="display: none;">
            <div id="game-info">
                <div id="player1-info" class="player-info"></div>
                <div id="game-status"></div>
                <div id="player2-info" class="player-info"></div>
            </div>
            <div id="game-board"></div>
        </div>
    </div>

    <script src="./script.js"></script>
</body>
</html>