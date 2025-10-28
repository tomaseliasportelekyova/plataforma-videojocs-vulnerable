<?php
session_start();

if (!isset($_SESSION['nickname'])) {
    header("Location: login.php");
    exit();
}

$nickname = $_SESSION['nickname'];

// --- Datos de ejemplo para el carrusel Hero ---
$hero_games = [
    [
        'title' => 'Space War',
        'description' => 'Pilota tu caza estelar a través de campos de asteroides y flotas enemigas en este trepidante shooter espacial.',
        'image' => '../frontend/imatges/spacewar.jpg',
        'platform' => 'Web'
    ],
    [
        'title' => 'Unlucky Mario Bros',
        'description' => 'Un desafío plataformero extremo. ¿Podrás superar los niveles más injustos jamás creados?',
        'image' => '../frontend/imatges/mario.png',
        'platform' => 'Web'
    ],
    [
        'title' => 'Hollow Knight',
        'description' => 'Explora un vasto reino interconectado de insectos y héroes. Descubre misterios ancestrales y combate criaturas retorcidas.',
        'image' => '../frontend/imatges/hollow-knight.jpg',
        'platform' => 'PC/Consola'
    ]
];
$current_hero_game = $hero_games[0]; // Muestra Naus vs Ovnis por defecto

// ========= DEFINIM LA PÀGINA ACTUAL PEL HEADER =========
$paginaActual = 'dashboard';
// =======================================================
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

  <?php include './includes/_header.php'; ?>
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
          <?php if ($current_hero_game['title'] === 'Space War'): ?>
               <a href="../frontend/jocs/joc_naus/index.html" class="play-button"><i class="fas fa-play"></i> Jugar Ahora</a>
           <?php elseif ($current_hero_game['title'] === 'Unlucky Mario Bros'): ?>
               <button class="play-button" disabled><i class="fas fa-play"></i> Próximamente</button>
           <?php else: ?>
                <button class="play-button" disabled><i class="fas fa-tools"></i> No Jugable</button>
           <?php endif; ?>
        </div>
      </div>
    </div>
    <button class="carousel-arrow prev"><i class="fas fa-chevron-left"></i></button>
    <button class="carousel-arrow next"><i class="fas fa-chevron-right"></i></button>
  </section>

  <main class="dashboard-content">

    <section class="game-row" id="juegos">
      <h2>Continuar Jugando / Descubrir</h2>
      <div class="games-carousel">

        <a href="../frontend/jocs/joc_naus/index.html?joc_id=1" class="game-card-link"> <article class="game-card">
                <img src="../frontend/imatges/spacewar.jpg" alt="Naus vs Ovnis">
                <div class="card-info"> <h3>Naus vs Ovnis</h3> </div>
                 <a href="ranking.php?joc_id=1" class="rank-button-card">Ver Ranking</a> </article>
        </a>

        <a href="#" class="game-card-link" onclick="alert('Juego aún no disponible'); return false;">
            <article class="game-card">
                <img src="../frontend/imatges/mario.png" alt="Unlucky Mario Bros">
                <div class="card-info"> <h3>Unlucky Mario Bros</h3> </div>
                 <button class="rank-button-card" disabled>Ver Ranking</button>
            </article>
        </a>

        <a href="#" class="game-card-link" onclick="alert('Este juego no está disponible en la plataforma'); return false;">
            <article class="game-card">
                <img src="../frontend/imatges/hollow-knight.jpg" alt="Hollow Knight">
                <div class="card-info"> <h3>Hollow Knight</h3> </div>
                 <button class="rank-button-card" disabled>Ver Ranking</button>
            </article>
        </a>

        <article class="game-card"> <img src="https://via.placeholder.com/400x225/555/eee?text=Juego+4" alt="Juego 4"> <div class="card-info"> <h3>Juego 4</h3> </div> </article>
        <article class="game-card"> <img src="https://via.placeholder.com/400x225/666/eee?text=Juego+5" alt="Juego 5"> <div class="card-info"> <h3>Juego 5</h3> </div> </article>
      </div>
    </section>

    <section class="game-row" id="reclamar">
      <h2>Los Mejores Juegos Para Canjear</h2>
       <div class="games-carousel">
        <article class="game-card"> <img src="https://via.placeholder.com/400x225/777/eee?text=Canjeable+1" alt="Canjeable 1"> <div class="card-info"> <h3>Canjeable 1</h3> <button class="claim-button">Reclamar Juego</button> </div> </article>
        <article class="game-card"> <img src="https://via.placeholder.com/400x225/888/eee?text=Canjeable+2" alt="Canjeable 2"> <div class="card-info"> <h3>Canjeable 2</h3> <button class="claim-button">Reclamar Juego</button> </div> </article>
         <article class="game-card"> <img src="https://via.placeholder.com/400x225/999/eee?text=Canjeable+3" alt="Canjeable 3"> <div class="card-info"> <h3>Canjeable 3</h3> <button class="claim-button">Reclamar Juego</button> </div> </article>
      </div>
    </section>

     <section class="game-row" id="todos">
        <h2>Todos los Juegos</h2>
         <p style="color: #a0a0b0;">Próximamente...</p>
     </section>

  </main>

  <script>
      document.addEventListener('DOMContentLoaded', () => {
          const slides = <?php echo json_encode($hero_games); ?>;
          const heroSlide = document.querySelector('.hero-slide');
          const heroTitle = heroSlide.querySelector('h1');
          const heroDesc = heroSlide.querySelector('p');
          const heroPlatform = heroSlide.querySelector('.platform-tag');
          let currentSlideIndex = 0;

          function changeSlide(index) {
              const slideData = slides[index];
              heroSlide.style.backgroundImage = `url('${slideData.image}')`;
              heroTitle.textContent = slideData.title;
              heroDesc.textContent = slideData.description;
              if (heroPlatform) {
                  if (slideData.platform) {
                      heroPlatform.textContent = slideData.platform;
                      heroPlatform.style.display = 'inline-block';
                  } else {
                      heroPlatform.style.display = 'none';
                  }
              }
              // Actualizar enlace/botón del Hero
              const heroButtonContainer = heroSlide.querySelector('.hero-info');
              let existingButton = heroButtonContainer.querySelector('.play-button');
              if (existingButton) existingButton.remove(); // Remove previous button/link

              let newButtonHTML;
              if (slideData.title === 'Space War') {
                   newButtonHTML = `<a href="../frontend/jocs/joc_naus/index.html?joc_id=1" class="play-button"><i class="fas fa-play"></i> Jugar Ahora</a>`;
               } else if (slideData.title === 'Unlucky Mario Bros') {
                   newButtonHTML = `<button class="play-button" disabled><i class="fas fa-play"></i> Próximamente</button>`;
               } else {
                   newButtonHTML = `<button class="play-button" disabled><i class="fas fa-tools"></i> No Jugable</button>`;
               }
               // Insert the new button/link after the platform tag (or description if no tag)
               const referenceElement = heroPlatform && heroPlatform.style.display !== 'none' ? heroPlatform : heroDesc;
               referenceElement.insertAdjacentHTML('afterend', newButtonHTML);
          }

          document.querySelector('.carousel-arrow.next').addEventListener('click', () => {
              currentSlideIndex = (currentSlideIndex + 1) % slides.length;
              changeSlide(currentSlideIndex);
          });
          document.querySelector('.carousel-arrow.prev').addEventListener('click', () => {
              currentSlideIndex = (currentSlideIndex - 1 + slides.length) % slides.length;
              changeSlide(currentSlideIndex);
          });

          // Initialize the first slide button/link
          changeSlide(0);
      });
  </script>

</body>
</html>