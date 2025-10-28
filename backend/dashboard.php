<?php
session_start();

if (!isset($_SESSION['nickname'])) {
    header("Location: login.php");
    exit();
}

$nickname = $_SESSION['nickname'];

// --- Datos de ejemplo para el carrusel Hero ---
// En una aplicación real, esto vendría de la base de datos
$hero_games = [
    [
        'title' => 'Space War',
        'description' => 'Pilota tu caza estelar a través de campos de asteroides y flotas enemigas en este trepidante shooter espacial.',
        'image' => '../frontend/imatges/spacewar.jpg', // Imagen grande para el fondo
        'platform' => 'Nintendo Switch' // Opcional
    ],
    [
        'title' => 'Unlucky Mario Bros',
        'description' => 'Un desafío plataformero extremo. ¿Podrás superar los niveles más injustos jamás creados?',
        'image' => '../frontend/imatges/mario.png',
        'platform' => 'PlayStation 5'
    ],
    [
        'title' => 'Hollow Knight',
        'description' => 'Explora un vasto reino interconectado de insectos y héroes. Descubre misterios ancestrales y combate criaturas retorcidas.',
        'image' => '../frontend/imatges/hollow-knight.jpg',
        'platform' => 'PC'
    ]
];
// Seleccionamos el primer juego para mostrar inicialmente
$current_hero_game = $hero_games[0];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard - Shit Games</title>
  <link rel="stylesheet" href="../frontend/assets/css/style_dashboard.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dark-theme">

  <header class="main-header floating">
    <div class="logo">
      <a href="dashboard.php">Shit Games</a>
    </div>
    <nav class="main-nav">
      <a href="#">Todos</a>
      <a href="#" class="active">Juegos</a>
      <a href="#">Reclamar</a>
    </nav>
    <div class="user-profile">
      <a href="perfil.php" title="Ir al perfil de <?php echo htmlspecialchars($nickname); ?>">
        <div class="avatar-placeholder"><?php echo strtoupper(substr($nickname, 0, 1)); ?></div>
      </a>
    </div>
  </header>

  <section class="hero-carousel">
    <div class="hero-slide active" style="background-image: url('<?php echo htmlspecialchars($current_hero_game['image']); ?>');">
      <div class="hero-gradient"></div>
      <div class="hero-content">
        <div class="hero-info">
          <h1><?php echo htmlspecialchars($current_hero_game['title']); ?></h1>
          <p><?php echo htmlspecialchars($current_hero_game['description']); ?></p>
          <?php if (!empty($current_hero_game['platform'])): ?>
            <span class="platform-tag"><?php echo htmlspecialchars($current_hero_game['platform']); ?></span>
          <?php endif; ?>
          <button class="play-button"><i class="fas fa-play"></i> Jugar Ahora</button>
        </div>
      </div>
    </div>
    <button class="carousel-arrow prev"><i class="fas fa-chevron-left"></i></button>
    <button class="carousel-arrow next"><i class="fas fa-chevron-right"></i></button>
  </section>

  <main class="dashboard-content">

    <section class="game-row">
      <h2>Continuar Jugando</h2>
      <div class="games-carousel">
        
        <a href="../frontend/jocs/joc_naus/index.html" style="text-decoration: none; color: inherit;">
            <article class="game-card"> 
                <img src="../frontend/imatges/spacewar.jpg" alt="Naus vs Ovnis"> 
                <div class="card-info"> <h3>Naus vs Ovnis</h3> </div> 
            </article>
        </a>

        <article class="game-card"> <img src="../frontend/imatges/mario.png" alt="Unlucky Mario Bros"> <div class="card-info"> <h3>Unlucky Mario Bros</h3> </div> </article>
        <article class="game-card"> <img src="../frontend/imatges/hollow-knight.jpg" alt="Hollow Knight"> <div class="card-info"> <h3>Hollow Knight</h3> </div> </article>
        <article class="game-card"> <img src="https://via.placeholder.com/400x225/555/eee?text=Juego+4" alt="Juego 4"> <div class="card-info"> <h3>Juego 4</h3> </div> </article>
        <article class="game-card"> <img src="https://via.placeholder.com/400x225/666/eee?text=Juego+5" alt="Juego 5"> <div class="card-info"> <h3>Juego 5</h3> </div> </article>
      </div>
    </section>

    <section class="game-row">
      <h2>Los Mejores Juegos Para Canjear</h2>
       <div class="games-carousel">
        <article class="game-card"> <img src="https://via.placeholder.com/400x225/777/eee?text=Canjeable+1" alt="Canjeable 1"> <div class="card-info"> <h3>Canjeable 1</h3> <button class="claim-button">Reclamar Juego</button> </div> </article>
        <article class="game-card"> <img src="https://via.placeholder.com/400x225/888/eee?text=Canjeable+2" alt="Canjeable 2"> <div class="card-info"> <h3>Canjeable 2</h3> <button class="claim-button">Reclamar Juego</button> </div> </article>
         <article class="game-card"> <img src="https://via.placeholder.com/400x225/999/eee?text=Canjeable+3" alt="Canjeable 3"> <div class="card-info"> <h3>Canjeable 3</h3> <button class="claim-button">Reclamar Juego</button> </div> </article>
      </div>
    </section>

  </main>

  </body>
</html>