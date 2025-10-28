<?php
// Arxiu: backend/includes/_header.php

$paginaActual = $paginaActual ?? '';
$is_logged_in = isset($nickname, $_SESSION['user_id']);

// Recuperem la foto de la sessió. Si no existeix, usem la default.
$default_photo_path = '../frontend/imatges/users/default_user.png';
$user_photo_for_header = $_SESSION['user_photo'] ?? $default_photo_path;

// Decidim si mostrem foto o inicial:
// Mostrem foto si està loguejat i la ruta a la sessió NO ÉS LA RUTA DEFAULT.
$show_photo = ($is_logged_in && $user_photo_for_header !== $default_photo_path);

?>

<header class="main-header floating">
  <div class="logo">
    <a href="dashboard.php">Shit Games</a>
  </div>
  <nav class="main-nav">
    <a href="dashboard.php#todos" <?php echo ($paginaActual == 'dashboard') ? 'class="active"' : ''; ?>>Todos</a>
    <a href="dashboard.php#juegos" <?php echo ($paginaActual == 'dashboard') ? 'class="active"' : ''; ?>>Juegos</a>
    <a href="ranking.php" <?php echo ($paginaActual == 'ranking') ? 'class="active"' : ''; ?>>Ranking</a>
    <a href="dashboard.php#reclamar" <?php echo ($paginaActual == 'dashboard') ? 'class="active"' : ''; ?>>Reclamar</a>
  </nav>
  <div class="user-profile">
    <?php if ($is_logged_in): ?>
      <a href="perfil.php" title="Ir al perfil de <?php echo htmlspecialchars($nickname); ?>">
        
        <?php if ($show_photo): ?>
          <img src="<?php echo htmlspecialchars($user_photo_for_header); ?>" alt="Avatar" class="avatar-image">
        <?php else: ?>
          <div class="avatar-placeholder"><?php echo strtoupper(substr($nickname, 0, 1)); ?></div>
        <?php endif; ?>
        
      </a>
    <?php else: ?>
        <a href="login.php" class="login-link">Login</a>
    <?php endif; ?>
  </div>
</header>