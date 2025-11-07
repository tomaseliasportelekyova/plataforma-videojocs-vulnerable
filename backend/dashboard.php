<?php
session_start();

if (!isset($_SESSION['nickname'], $_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$nickname = $_SESSION['nickname'];
$user_id = $_SESSION['user_id']; 

require "./funcions/db_mysqli.php";

// --- 1. Lògica per al carrusel "Hero" (Top 5 més jugats) ---
$top_games = [];
$sql_top_games = "
    SELECT 
        j.id as joc_id, 
        j.nom_joc as title, 
        j.descripcio as description, 
        j.cover_image_url as image,
        COALESCE(SUM(p.durada_segons), 0) as total_playtime
    FROM jocs j
    LEFT JOIN partides p ON j.id = p.joc_id
    WHERE j.actiu = 1 
    GROUP BY j.id, j.nom_joc, j.descripcio, j.cover_image_url
    ORDER BY total_playtime DESC, j.nom_joc ASC
    LIMIT 5";
$stmt_top = $conn->prepare($sql_top_games);
if ($stmt_top) {
    $stmt_top->execute();
    $result_top = $stmt_top->get_result();
    while ($row = $result_top->fetch_assoc()) {
         $row['image'] = (!empty($row['image'])) 
                       ? $row['image'] 
                       : 'https://placehold.co/1200x600/1A1A2A/E0E0E0?text=' . urlencode($row['title']);
        // === CANVI: Traiem el 'Web' hardcoded ===
        // $row['platform'] = 'Web'; 
        $top_games[] = $row;
    }
    $stmt_top->close();
}
// ... (fallback es queda igual) ...
if (count($top_games) < 5) {
    $needed = 5 - count($top_games);
    $existing_ids = array_column($top_games, 'joc_id'); 
    $ids_placeholder = !empty($existing_ids) ? implode(',', array_fill(0, count($existing_ids), '?')) : '';
    $sql_fallback = "SELECT id as joc_id, nom_joc as title, descripcio as description, cover_image_url as image 
                     FROM jocs 
                     WHERE actiu = 1 " . 
                     (!empty($ids_placeholder) ? "AND id NOT IN ($ids_placeholder) " : "") .
                     "ORDER BY id ASC LIMIT ?";
    $stmt_fallback = $conn->prepare($sql_fallback);
    if ($stmt_fallback) {
        $types = str_repeat('i', count($existing_ids)) . 'i'; 
        $params = array_merge($existing_ids, [$needed]);
        if (!empty($existing_ids)) {
             $stmt_fallback->bind_param($types, ...$params);
        } else {
             $stmt_fallback->bind_param('i', $needed); 
        }
        $stmt_fallback->execute();
        $result_fallback = $stmt_fallback->get_result();
        while ($row = $result_fallback->fetch_assoc()) {
            $row['image'] = (!empty($row['image'])) 
                           ? $row['image'] 
                           : 'https://placehold.co/1200x600/1A1A2A/E0E0E0?text=' . urlencode($row['title']);
            // === CANVI: Traiem el 'Web' hardcoded ===
            // $row['platform'] = 'Web';
             if (!in_array($row['joc_id'], array_column($top_games, 'joc_id'))) {
                $top_games[] = $row;
            }
        }
        $stmt_fallback->close();
    }
}
$current_hero_game = !empty($top_games) ? $top_games[0] : null;


// === LÒGICA PER A LES FILES DE JOCS ===
$owned_game_ids = [0]; 
$played_games = [];   
$most_played_games = []; 
$upcoming_games = [];
$all_games = []; 

// --- 2. Jocs que l'usuari JA POSSEEIX (per "Continuar Jugando") ---
$sql_played = "SELECT j.id, j.nom_joc, j.cover_image_url, j.valoracio, j.tipus, p.nivell_actual
               FROM jocs j
               JOIN usuari_jocs uj ON j.id = uj.joc_id
               LEFT JOIN progres_usuari p ON j.id = p.joc_id AND uj.usuari_id = p.usuari_id
               WHERE uj.usuari_id = ? AND j.actiu = 1
               ORDER BY p.ultima_partida DESC";
$stmt_played = $conn->prepare($sql_played);
if ($stmt_played) {
    $stmt_played->bind_param("i", $user_id);
    $stmt_played->execute();
    $result_played = $stmt_played->get_result();
    while ($row = $result_played->fetch_assoc()) {
        $row['cover_image_url'] = (!empty($row['cover_image_url'])) 
               ? $row['cover_image_url'] 
               : 'https://placehold.co/400x225/1A1A2A/E0E0E0?text=' . urlencode($row['nom_joc']);
        $played_games[] = $row;
        $owned_game_ids[] = $row['id']; 
    }
    $stmt_played->close();
}

// --- 3. Jocs "MÁS JUGADOS" (que l'usuari NO té) ---
$ids_placeholder = implode(',', array_fill(0, count($owned_game_ids), '?')); 
$sql_most_played = "SELECT j.id, j.nom_joc, j.cover_image_url, j.valoracio, j.tipus,
                       (SELECT COUNT(*) FROM partides p WHERE p.joc_id = j.id) AS total_partides
                    FROM jocs j
                    WHERE j.actiu = 1 AND j.categoria != 'Próximamente' AND id NOT IN ($ids_placeholder)
                    ORDER BY total_partides DESC, j.valoracio DESC
                    LIMIT 10";
$stmt_most_played = $conn->prepare($sql_most_played);
if ($stmt_most_played) {
    $types = str_repeat('i', count($owned_game_ids)); 
    $stmt_most_played->bind_param($types, ...$owned_game_ids);
    $stmt_most_played->execute();
    $result_most_played = $stmt_most_played->get_result();
    while ($row = $result_most_played->fetch_assoc()) {
        $row['cover_image_url'] = (!empty($row['cover_image_url'])) 
               ? $row['cover_image_url'] 
               : 'https://placehold.co/400x225/1A1A2A/E0E0E0?text=' . urlencode($row['nom_joc']);
        $most_played_games[] = $row;
    }
    $stmt_most_played->close();
}

// --- 4. Jocs "MÁS ESPERADOS" (Próximamente) ---
$sql_upcoming = "SELECT id, nom_joc, cover_image_url, valoracio, tipus
                 FROM jocs
                 WHERE actiu = 1 AND categoria = 'Próximamente' AND id NOT IN ($ids_placeholder)
                 ORDER BY id DESC";
$stmt_upcoming = $conn->prepare($sql_upcoming);
if ($stmt_upcoming) {
    $types_upcoming = str_repeat('i', count($owned_game_ids));
    $stmt_upcoming->bind_param($types_upcoming, ...$owned_game_ids);
    $stmt_upcoming->execute();
    $result_upcoming = $stmt_upcoming->get_result();
    while ($row = $result_upcoming->fetch_assoc()) {
        $row['cover_image_url'] = (!empty($row['cover_image_url'])) 
               ? $row['cover_image_url'] 
               : 'https://placehold.co/400x225/1A1A2A/E0E0E0?text=' . urlencode($row['nom_joc']);
        $upcoming_games[] = $row;
    }
    $stmt_upcoming->close();
}

// --- 5. NOU: TOTS ELS JOCS (sense els que ja té) ---
$sql_all = "SELECT id, nom_joc, cover_image_url, valoracio, tipus
            FROM jocs
            WHERE actiu = 1 AND id NOT IN ($ids_placeholder)
            ORDER BY nom_joc ASC";
$stmt_all = $conn->prepare($sql_all);
if ($stmt_all) {
    $types_all = str_repeat('i', count($owned_game_ids));
    $stmt_all->bind_param($types_all, ...$owned_game_ids);
    $stmt_all->execute();
    $result_all = $stmt_all->get_result();
    while ($row = $result_all->fetch_assoc()) {
        $row['cover_image_url'] = (!empty($row['cover_image_url'])) 
               ? $row['cover_image_url'] 
               : 'https://placehold.co/400x225/1A1A2A/E0E0E0?text=' . urlencode($row['nom_joc']);
        $all_games[] = $row;
    }
    $stmt_all->close();
}


$conn->close(); 
$paginaActual = 'juegos'; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Juegos - Shit Games</title>
  
  <link rel="stylesheet" href="../frontend/assets/css/style_base.css" />
  <link rel="stylesheet" href="../frontend/assets/css/style_common.css" />
  <link rel="stylesheet" href="../frontend/assets/css/style_dashboard.css" />

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dark-theme">

  <?php include './includes/_header.php'; ?>

  <section class="hero-carousel">
    <?php if ($current_hero_game): ?>
        <div class="hero-slide active" style="background-image: url('<?php echo htmlspecialchars($current_hero_game['image']); ?>');">
          <div class="hero-gradient"></div>
          <div class="hero-content">
            <div class="hero-info">
              <h1 id="hero-title"><?php echo htmlspecialchars($current_hero_game['title']); ?></h1>
              <p id="hero-desc"><?php echo htmlspecialchars($current_hero_game['description'] ?? 'Sense descripció.'); ?></p>
              
              <?php /* if (!empty($current_hero_game['platform'])): ?>
                <span class="platform-tag" id="hero-platform"><?php echo htmlspecialchars($current_hero_game['platform']); ?></span>
              <?php endif; */ ?>
              
              <a id="hero-play-button" href="game_details.php?joc_id=<?php echo $current_hero_game['joc_id']; ?>" class="play-button"><i class="fas fa-play"></i> Jugar Ahora</a>
               <span id="hero-game-id" data-id="<?php echo $current_hero_game['joc_id']; ?>" style="display:none;"></span>
            </div>
          </div>
        </div>
    <?php else: ?>
        <div class="hero-slide active" style="background-color: #1A1A2A;">
             <div class="hero-content"><div class="hero-info"><h1>No hi ha jocs disponibles</h1></div></div>
        </div>
    <?php endif; ?>
    
    <?php if (count($top_games) > 1): ?>
    <ul class="carousel-dots">
        <?php foreach ($top_games as $index => $game): ?>
            <li class="dot-indicator" data-index="<?php echo $index; ?>"></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    
    <?php if (count($top_games) > 1): ?>
        <button class="carousel-arrow prev"><i class="fas fa-chevron-left"></i></button>
        <button class="carousel-arrow next"><i class="fas fa-chevron-right"></i></button>
    <?php endif; ?>
  </section>
  
  <main class="dashboard-content">

    <?php if (!empty($played_games)): ?>
    <section class="game-row" id="continuar-jugando">
      <h2>Continuar Jugando</h2>
      <div class="carousel-wrapper"> 
        <button class="carousel-arrow-row prev" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>
        <div class="games-carousel">
            <?php foreach ($played_games as $joc): ?>
            <a href="game_details.php?joc_id=<?php echo $joc['id']; ?>" class="game-card-link">
              <article class="game-card">
                <img src="<?php echo htmlspecialchars($joc['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($joc['nom_joc']); ?>">
                <div class="card-info">
                  <h3><?php echo htmlspecialchars($joc['nom_joc']); ?></h3>
                </div>
                <div class="card-footer">
                  <?php if ($joc['tipus'] == 'Premium'): ?>
                    <div class="card-premium-icon"><i class="fas fa-gem"></i></div>
                  <?php else: ?>
                    <div></div> 
                  <?php endif; ?>
                  <div class="card-rating">
                    <i class="fas fa-star"></i>
                    <span><?php echo number_format($joc['valoracio'], 1); ?></span>
                  </div>
                </div>
              </article>
            </a>
            <?php endforeach; ?>
        </div>
        <button class="carousel-arrow-row next" aria-label="Siguiente"><i class="fas fa-chevron-right"></i></button>
      </div> 
    </section>
    <?php endif; ?>
    
    <section class="game-row" id="mas-jugados">
      <h2>Los Más Jugados</h2>
      <div class="carousel-wrapper"> 
        <button class="carousel-arrow-row prev" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>
        <div class="games-carousel">
            <?php if (!empty($most_played_games)): ?>
                <?php foreach ($most_played_games as $joc): ?>
                <a href="game_details.php?joc_id=<?php echo $joc['id']; ?>" class="game-card-link">
                  <article class="game-card">
                    <img src="<?php echo htmlspecialchars($joc['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($joc['nom_joc']); ?>">
                    <div class="card-info">
                      <h3><?php echo htmlspecialchars($joc['nom_joc']); ?></h3>
                    </div>
                    <div class="card-footer">
                      <?php if ($joc['tipus'] == 'Premium'): ?>
                        <div class="card-premium-icon"><i class="fas fa-gem"></i></div>
                      <?php else: ?>
                        <div></div> 
                      <?php endif; ?>
                      <div class="card-rating">
                        <i class="fas fa-star"></i>
                        <span><?php echo number_format($joc['valoracio'], 1); ?></span>
                      </div>
                    </div>
                  </article>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button class="carousel-arrow-row next" aria-label="Siguiente"><i class="fas fa-chevron-right"></i></button>
      </div> 
    </section>
    
    <?php if (!empty($upcoming_games)): ?>
    <section class="game-row" id="mas-esperados">
      <h2>Los Más Esperados</h2>
       <div class="carousel-wrapper"> 
        <button class="carousel-arrow-row prev" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>
        <div class="games-carousel">
            <?php foreach ($upcoming_games as $joc): ?>
                <a href="game_details.php?joc_id=<?php echo $joc['id']; ?>" class="game-card-link">
                  <article class="game-card">
                    <img src="<?php echo htmlspecialchars($joc['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($joc['nom_joc']); ?>">
                    <div class="card-info">
                      <h3><?php echo htmlspecialchars($joc['nom_joc']); ?></h3>
                    </div>
                    <div class="card-footer">
                      <?php if ($joc['tipus'] == 'Premium'): ?>
                        <div class="card-premium-icon"><i class="fas fa-gem"></i></div>
                      <?php else: ?>
                        <div></div> 
                      <?php endif; ?>
                      <div class="card-rating">
                        <i class="fas fa-star" style="color: #5A5A6A;"></i>
                        <span>Próxim.</span>
                      </div>
                    </div>
                  </article>
                </a>
            <?php endforeach; ?>
        </div>
        <button class="carousel-arrow-row next" aria-label="Siguiente"><i class="fas fa-chevron-right"></i></button>
      </div> 
    </section>
    <?php endif; ?>
    
     <section class="game-row" id="todos-los-juegos">
        <h2>Todos los Juegos</h2>
        <div class="games-carousel" style="flex-wrap: wrap; overflow-x: hidden;">
            <?php if (!empty($all_games)): ?>
                <?php foreach ($all_games as $joc): ?>
                <a href="game_details.php?joc_id=<?php echo $joc['id']; ?>" class="game-card-link">
                  <article class="game-card">
                    <img src="<?php echo htmlspecialchars($joc['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($joc['nom_joc']); ?>">
                    <div class="card-info">
                      <h3><?php echo htmlspecialchars($joc['nom_joc']); ?></h3>
                    </div>
                    <div class="card-footer">
                      <?php if ($joc['tipus'] == 'Premium'): ?>
                        <div class="card-premium-icon"><i class="fas fa-gem"></i></div>
                      <?php else: ?>
                        <div></div> 
                      <?php endif; ?>
                      <div class="card-rating">
                        <i class="fas fa-star"></i>
                        <span><?php echo number_format($joc['valoracio'], 1); ?></span>
                      </div>
                    </div>
                  </article>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #a0a0b0;">No hi ha jocs disponibles actualment.</p>
            <?php endif; ?>
        </div>
     </section>
  </main>

  <?php include './includes/_footer.php'; ?>

  <script>
      // ... (El JavaScript del carrusel hero i de les files es queda igual) ...
      document.addEventListener('DOMContentLoaded', () => {
          // --- LÒGICA 1: CARRUSEL HERO ---
          const slidesData = <?php echo json_encode($top_games); ?>;
          if (slidesData && slidesData.length > 0) {
              const heroSlide = document.querySelector('.hero-slide');
              const heroTitle = document.getElementById('hero-title');
              const heroDesc = document.getElementById('hero-desc');
              const heroPlatform = document.getElementById('hero-platform');
              const heroPlayButton = document.getElementById('hero-play-button');
              const dotIndicators = document.querySelectorAll('.dot-indicator');
              let currentSlideIndex = 0;
              let autoSlideInterval = null;
              const slideDuration = 7000; 

              function changeSlide(index) {
                  if (index < 0 || index >= slidesData.length) return; 
                  const slideData = slidesData[index];
                  heroSlide.style.backgroundImage = `url('${slideData.image}')`;
                  if (heroTitle) heroTitle.textContent = slideData.title;
                  if (heroDesc) heroDesc.textContent = slideData.description || 'Sense descripció.';
                  
                  // === CANVI: Lògica del platform tag eliminada ===
                  // if (heroPlatform) { ... } 
                  
                  if (heroPlayButton) {
                      let gameUrl = `game_details.php?joc_id=${slideData.joc_id}`;
                      heroPlayButton.href = gameUrl; 
                      heroPlayButton.onclick = null; 
                      heroPlayButton.style.opacity = 1; 
                  }
                  dotIndicators.forEach((dot, i) => {
                      if (i === index) {
                          dot.classList.add('active');
                      } else {
                          dot.classList.remove('active');
                      }
                  });
                  currentSlideIndex = index; 
              }
              function nextSlide() {
                  let newIndex = (currentSlideIndex + 1) % slidesData.length;
                  changeSlide(newIndex);
              }
              function prevSlide() {
                  let newIndex = (currentSlideIndex - 1 + slidesData.length) % slidesData.length;
                  changeSlide(newIndex);
              }
              function startAutoSlide() {
                  if (autoSlideInterval) clearInterval(autoSlideInterval);
                  autoSlideInterval = setInterval(nextSlide, slideDuration);
              }
              const prevButton = document.querySelector('.hero-carousel .carousel-arrow.prev');
              const nextButton = document.querySelector('.hero-carousel .carousel-arrow.next');
              if (prevButton && nextButton && slidesData.length > 1) {
                  nextButton.addEventListener('click', () => {
                      nextSlide();        
                      startAutoSlide(); 
                  });
                  prevButton.addEventListener('click', () => {
                      prevSlide();        
                      startAutoSlide(); 
                  });
                  dotIndicators.forEach(dot => {
                      dot.addEventListener('click', () => {
                          const newIndex = parseInt(dot.dataset.index, 10);
                          changeSlide(newIndex); 
                          startAutoSlide(); 
                      });
                  });
                  startAutoSlide(); 
              } else {
                   if(prevButton) prevButton.style.display = 'none';
                   if(nextButton) nextButton.style.display = 'none';
                   if(dotIndicators.length > 0) {
                       document.querySelector('.carousel-dots').style.display = 'none';
                   }
              }
              changeSlide(0);
          }

          // --- LÒGICA 2: CARRUSELS DE FILES ---
          function initializeRowCarousels() {
              const allWrappers = document.querySelectorAll('.carousel-wrapper');
              
              allWrappers.forEach(wrapper => {
                  const carousel = wrapper.querySelector('.games-carousel');
                  const prevBtn = wrapper.querySelector('.carousel-arrow-row.prev');
                  const nextBtn = wrapper.querySelector('.carousel-arrow-row.next');
                  
                  if (!carousel || !prevBtn || !nextBtn) return;

                  function updateArrowVisibility() {
                      const hasOverflow = carousel.scrollWidth > carousel.clientWidth;
                      
                      prevBtn.style.display = hasOverflow ? 'flex' : 'none';
                      nextBtn.style.display = hasOverflow ? 'flex' : 'none';
                  }

                  nextBtn.addEventListener('click', () => {
                      const scrollAmount = carousel.clientWidth * 0.8; 
                      if (carousel.scrollLeft + carousel.clientWidth >= carousel.scrollWidth - 50) {
                          carousel.scrollTo({ left: 0, behavior: 'smooth' });
                      } else {
                          carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                      }
                  });

                  prevBtn.addEventListener('click', () => {
                      const scrollAmount = carousel.clientWidth * 0.8;
                      if (carousel.scrollLeft <= 0) {
                          carousel.scrollTo({ left: carousel.scrollWidth, behavior: 'smooth' });
                      } else {
                          carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
                      }
                  });

                  updateArrowVisibility();
                  window.addEventListener('resize', updateArrowVisibility);
              });
          }
          
          initializeRowCarousels(); 

      });
  </script>

</body>
</html>