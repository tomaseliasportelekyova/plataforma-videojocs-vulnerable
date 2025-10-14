<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameHub - Tu Universo de Juegos</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href=".frontend/css/index.css">
</head>
<body>
    <header class="main-header">
        <div class="logo">
            <h1>Game<span class="hub-accent">Hub</span>_</h1>
        </div>
        <nav class="main-nav">
            <a href="#" class="active">Juegos</a>
            <a href="#">Perfil</a>
            <a href="#">Ranking</a>
            <a href="logout.php" class="logout">Salir</a>
        </nav>
    </header>

    <main class="main-content">
        <section class="featured-game">
            <div class="featured-info">
                <span>Juego de la Semana</span>
                <h2>CYBERCORE UPRISING</h2>
                <p>La rebelión ha comenzado. Hackea el sistema o sé borrado. Una nueva expansión te espera en el corazón de Neo-Kyoto.</p>
                <a href="jugar.php?id_juego=1" class="play-button-featured">Jugar Ahora</a>
            </div>
            <div class="featured-image">
                <img src="https://via.placeholder.com/1200x600/000/00ffab?text=CyberCore+Uprising" alt="Banner del juego CyberCore Uprising">
            </div>
        </section>

        <section class="game-library">
            <h3 class="library-title">Tu Biblioteca</h3>
            <div class="games-grid">
                
                <article class="game-card">
                    <div class="card-border"></div>
                    <div class="card-content">
                        <img src="https://via.placeholder.com/400x500/ff5733/10101a?text=Void+Runner" alt="Void Runner">
                        <div class="card-overlay">
                            <h4>Void Runner</h4>
                            <p>Velocidad terminal. Riesgo total.</p>
                            <a href="jugar.php?id_juego=2" class="play-button">▶ Jugar</a>
                        </div>
                    </div>
                </article>

                <article class="game-card">
                    <div class="card-border"></div>
                    <div class="card-content">
                        <img src="https://via.placeholder.com/400x500/8e44ad/10101a?text=Star+Forge" alt="Star Forge">
                        <div class="card-overlay">
                            <h4>Star Forge</h4>
                            <p>Tu imperio. Tus reglas.</p>
                            <a href="jugar.php?id_juego=3" class="play-button">▶ Jugar</a>
                        </div>
                    </div>
                </article>

                <article class="game-card">
                    <div class="card-border"></div>
                    <div class="card-content">
                        <img src="https://via.placeholder.com/400x500/3498db/10101a?text=Rogue+Sector" alt="Rogue Sector">
                        <div class="card-overlay">
                            <h4>Rogue Sector</h4>
                            <p>Sobrevive. Conquista.</p>
                            <a href="jugar.php?id_juego=4" class="play-button">▶ Jugar</a>
                        </div>
                    </div>
                </article>
                
            </div>
        </section>
    </main>
</body>
</html>