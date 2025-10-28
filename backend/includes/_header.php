<?php
// Arxiu: backend/includes/_header.php
// Necessita $nickname i $paginaActual definits abans.
// Ara també utilitzarà $_SESSION['user_photo']

$paginaActual = $paginaActual ?? '';
$is_logged_in = isset($nickname, $_SESSION['user_id']); // Comprovació més robusta

// Recuperem la foto de la sessió (si existeix)
$user_photo_for_header = $_SESSION['user_photo'] ?? '../frontend/imatges/users/default_user.png';
$default_photo_path = '../frontend/imatges/users/default_user.png';

// Decidim si mostrem foto o inicial
// Comprovem si la ruta NO és la default I si l'arxiu existeix realment
// La comprovació file_exists pot fallar depenent de permisos i rutes relatives/absolutes.
// Si dóna problemes, podem simplificar i només comprovar si NO és la default.
$show_photo = ($is_logged_in && $user_photo_for_header != $default_photo_path /* && file_exists($user_photo_for_header) */ ); // Comentat file_exists per simplicitat inicial

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