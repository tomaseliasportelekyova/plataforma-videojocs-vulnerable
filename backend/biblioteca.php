<?php
session_start();

if (!isset($_SESSION['nickname'], $_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$nickname = $_SESSION['nickname'];
$user_id = $_SESSION['user_id']; 

require "./funcions/db_mysqli.php";

$paginaActual = 'biblioteca'; // Per al header

// --- 1. Obtenim NOMÉS els jocs que l'usuari posseeix ---
$owned_games = [];
$sql_owned = "SELECT j.id, j.nom_joc, j.cover_image_url, j.valoracio, j.tipus
              FROM jocs j
              JOIN usuari_jocs uj ON j.id = uj.joc_id
              WHERE uj.usuari_id = ? AND j.actiu = 1
              ORDER BY j.nom_joc ASC";
$stmt_owned = $conn->prepare($sql_owned);
if ($stmt_owned) {
    $stmt_owned->bind_param("i", $user_id);
    $stmt_owned->execute();
    $result_owned = $stmt_owned->get_result();
    while ($row = $result_owned->fetch_assoc()) {
        $row['cover_image_url'] = (!empty($row['cover_image_url'])) 
               ? $row['cover_image_url'] 
               : 'https://placehold.co/400x225/1A1A2A/E0E0E0?text=' . urlencode($row['nom_joc']);
        $owned_games[] = $row;
    }
    $stmt_owned->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mi Biblioteca - Shit Games</title>
  
  <link rel="stylesheet" href="../frontend/assets/css/style_base.css" />
  <link rel="stylesheet" href="../frontend/assets/css/style_common.css" />
  <link rel="stylesheet" href="../frontend/assets/css/style_dashboard.css" />

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dark-theme">

  <?php include './includes/_header.php'; ?>
  
  <main class="dashboard-content" style="margin-top: 50px;"> <section class="game-row" id="biblioteca">
      <h2><i class="fas fa-book-open"></i> Mi Biblioteca</h2>
      
      <div class="games-carousel" style="flex-wrap: wrap; overflow-x: hidden;">
        
        <?php if (!empty($owned_games)): ?>
            <?php foreach ($owned_games as $joc): ?>
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
                    <div></div> <?php endif; ?>
                  <div class="card-rating">
                    <i class="fas fa-star"></i>
                    <span><?php echo number_format($joc['valoracio'], 1); ?></span>
                  </div>
                </div>
              </article>
            </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #a0a0b0; padding-left: 10px;">Aún no has canjeado ningún juego. ¡Visita la sección "Juegos" para descubrir nuevos títulos!</p>
        <?php endif; ?>
        
      </div>
    </section>

  </main>

  <?php include './includes/_footer.php'; ?>

</body>
</html>