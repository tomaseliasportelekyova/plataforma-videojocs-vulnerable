<?php
session_start();
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - Shit Game</title>
  <link rel="stylesheet" href="../frontend/assets/css/style_login.css" />
</head>
<body>
  <div class="background-container">
    <img id="background-gif" src="" alt="Fondo animado">
  </div>

  <div class="login-container">
    <div class="login-panel">
      <canvas id="interactive-dots-canvas"></canvas>

      <form class="login-form" method="post" action="./funcions/validacio_login.php">
        <div class="form-header">
          <h1>Bienvenido a Shit Games</h1>
          <p>Inicia sesión para acceder</p>
        </div>

        <?php if ($error_message): ?>
          <p class="form-message error"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <div class="form-body">
          <input id="email" type="email" name="email" required placeholder="Email">
          <input id="pass" type="password" name="pass" required placeholder="Contraseña">
        </div>

        <div class="form-footer">
          <button type="submit">Entrar</button>
          <div class="bottom-link">
            <span>¿No tienes cuenta? <a href="registre.php">Regístrate</a></span>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script src="../frontend/assets/js/interactive-dots_login.js"></script>
  <script src="../frontend/assets/js/gif-rotator.js"></script>
</body>
</html>
