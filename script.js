// Classe pour gérer le jeu Puissance 4
class Puissance4 {
    constructor() {
        this.board = Array(6).fill().map(() => Array(7).fill(null));
        this.currentPlayer = 1;
        this.gameOver = false;
    }

    // Vérifie si une colonne est pleine
    isColumnFull(col) {
        return this.board[0][col] !== null;
    }

    // Trouve la première position libre dans une colonne
    findAvailableRow(col) {
        for (let row = 5; row >= 0; row--) {
            if (this.board[row][col] === null) {
                return row;
            }
        }
        return -1;
    }

    // Place un jeton dans la colonne spécifiée
    makeMove(col) {
        if (this.gameOver || this.isColumnFull(col)) return false;

        const row = this.findAvailableRow(col);
        if (row === -1) return false;

        this.board[row][col] = this.currentPlayer;
        return { row, col, player: this.currentPlayer };
    }

    // Vérifie s'il y a un gagnant
    checkWin(row, col) {
        const directions = [
            [[0, 1], [0, -1]],  // Horizontal
            [[1, 0], [-1, 0]],  // Vertical
            [[1, 1], [-1, -1]], // Diagonale principale
            [[1, -1], [-1, 1]]  // Diagonale secondaire
        ];

        const player = this.board[row][col];

        for (const [dir1, dir2] of directions) {
            let count = 1;
            
            // Vérifie dans la première direction
            count += this.countInDirection(row, col, dir1[0], dir1[1], player);
            // Vérifie dans la direction opposée
            count += this.countInDirection(row, col, dir2[0], dir2[1], player);

            if (count >= 4) return true;
        }

        return false;
    }

    // Compte les jetons alignés dans une direction
    countInDirection(row, col, deltaRow, deltaCol, player) {
        let count = 0;
        let currentRow = row + deltaRow;
        let currentCol = col + deltaCol;

        while (
            currentRow >= 0 && currentRow < 6 &&
            currentCol >= 0 && currentCol < 7 &&
            this.board[currentRow][currentCol] === player
        ) {
            count++;
            currentRow += deltaRow;
            currentCol += deltaCol;
        }

        return count;
    }

    // Vérifie si la grille est pleine (match nul)
    isBoardFull() {
        return this.board[0].every(cell => cell !== null);
    }
}

// Gestion de la connexion WebSocket
const conn = new WebSocket('ws://localhost:8080');
let game = null;
let gameId = null;
let playerNumber = null;
let playerInfo = null;

// Éléments du DOM
const welcomeScreen = document.getElementById('welcome-screen');
const gameScreen = document.getElementById('game-screen');
const newGameForm = document.getElementById('new-game-form');
const joinForm = document.getElementById('join-form');
const gameBoard = document.getElementById('game-board');
const gameStatus = document.getElementById('game-status');
const player1Info = document.getElementById('player1-info');
const player2Info = document.getElementById('player2-info');

// Création de la grille de jeu
function createGameBoard() {
    gameBoard.innerHTML = '';
    for (let row = 0; row < 6; row++) {
        for (let col = 0; col < 7; col++) {
            const cell = document.createElement('div');
            cell.className = 'cell';
            cell.dataset.col = col;
            cell.addEventListener('click', () => handleCellClick(col));
            gameBoard.appendChild(cell);
        }
    }
}

// Gestion du clic sur une cellule
function handleCellClick(col) {
    if (game && !game.gameOver && playerNumber === game.currentPlayer) {
        const move = game.makeMove(col);
        if (move) {
            conn.send(JSON.stringify({
                type: 'move',
                gameId: gameId,
                col: col,
                player: playerNumber
            }));
        }
    }
}

// Mise à jour de l'affichage
function updateDisplay(row, col, player) {
    const cells = document.querySelectorAll('.cell');
    const index = row * 7 + col;
    
    // Animation de chute
    let currentRow = 0;
    const animationInterval = setInterval(() => {
        // Effacer la cellule précédente
        if (currentRow > 0) {
            const previousIndex = (currentRow - 1) * 7 + col;
            cells[previousIndex].classList.remove(`player${player}`);
        }
        
        // Afficher dans la cellule courante
        const currentIndex = currentRow * 7 + col;
        cells[currentIndex].classList.add(`player${player}`);
        
        // Si on a atteint la ligne cible, arrêter l'animation
        if (currentRow === row) {
            clearInterval(animationInterval);
        } else {
            currentRow++;
        }
    }, 50); // Vitesse de l'animation
}

// Gestion des événements WebSocket
conn.onopen = function(e) {
    console.log("Connexion WebSocket établie");
};

conn.onerror = function(e) {
    console.error("Erreur WebSocket:", e);
    alert("Erreur de connexion au serveur");
};

conn.onclose = function(e) {
    console.log("Connexion WebSocket fermée");
    alert("La connexion au serveur a été perdue");
};

// Gestion du formulaire de création de partie
newGameForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('player1-name').value;
    const avatarInput = document.querySelector('input[name="avatar"]:checked');
    
    if (!avatarInput) {
        alert("Veuillez sélectionner un avatar");
        return;
    }
    
    const avatar = avatarInput.value;
    console.log("Création d'une nouvelle partie:", { name, avatar });
    
    conn.send(JSON.stringify({
        type: 'createGame',
        playerName: name,
        avatar: avatar
    }));
});

// Gestion du formulaire pour rejoindre une partie
joinForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('player2-name').value;
    const avatarInput = document.querySelector('input[name="avatar2"]:checked');
    const gameIdToJoin = document.getElementById('game-id').value;
    
    console.log("Tentative de connexion avec les données:", {
        name,
        avatar: avatarInput?.value,
        gameId: gameIdToJoin
    });
    
    if (!avatarInput) {
        alert("Veuillez sélectionner un avatar");
        return;
    }
    
    if (!gameIdToJoin.trim()) {
        alert("Veuillez entrer l'ID de la partie");
        return;
    }
    
    const avatar = avatarInput.value;
    console.log("Tentative de rejoindre la partie:", { name, avatar, gameIdToJoin });
    
    conn.send(JSON.stringify({
        type: 'joinGame',
        gameId: gameIdToJoin,
        playerName: name,
        avatar: avatar
    }));
});

// Gestion des messages WebSocket
conn.onmessage = function(e) {
    const data = JSON.parse(e.data);
    console.log("Message WebSocket reçu:", data);
    
    switch (data.type) {
        case 'gameCreated':
            console.log("Partie créée avec l'ID:", data.gameId);
            gameId = data.gameId;
            playerNumber = 1;
            game = new Puissance4();
            showGame(data.playerInfo);
            gameStatus.textContent = "En attente du joueur 2... ID de la partie : " + gameId;
            break;

        case 'gameJoined':
            console.log("Partie rejointe avec les joueurs:", data.playerInfo);
            if (!game) {
                playerNumber = 2;
                game = new Puissance4();
            }
            showGame(data.playerInfo);
            updateGameStatus();
            break;

        case 'move':
            console.log("Mouvement reçu:", data);
            const move = game.makeMove(data.col);
            if (move) {
                updateDisplay(move.row, move.col, move.player);
                if (game.checkWin(move.row, move.col)) {
                    gameStatus.textContent = `Joueur ${move.player} a gagné !`;
                    game.gameOver = true;
                } else if (game.isBoardFull()) {
                    gameStatus.textContent = "Match nul !";
                    game.gameOver = true;
                } else {
                    game.currentPlayer = game.currentPlayer === 1 ? 2 : 1;
                    updateGameStatus();
                }
            }
            break;

        case 'error':
            console.error("Erreur reçue:", data.message);
            alert(data.message);
            break;
            
        case 'playerDisconnected':
            console.log("Déconnexion d'un joueur");
            gameStatus.textContent = "L'autre joueur s'est déconnecté !";
            game.gameOver = true;
            break;
    }
};

// Affichage du jeu
function showGame(players) {
    welcomeScreen.style.display = 'none';
    gameScreen.style.display = 'block';
    createGameBoard();
    updatePlayerInfo(players);
}

// Mise à jour du statut du jeu
function updateGameStatus() {
    if (!game.gameOver) {
        gameStatus.textContent = `C'est au tour du joueur ${game.currentPlayer}`;
    }
}

// Mise à jour des informations des joueurs
function updatePlayerInfo(players) {
    player1Info.innerHTML = `
        <div class="avatar">${players.player1.avatar}</div>
        <div>${players.player1.name}</div>
    `;
    if (players.player2) {
        player2Info.innerHTML = `
            <div class="avatar">${players.player2.avatar}</div>
            <div>${players.player2.name}</div>
        `;
    }
}