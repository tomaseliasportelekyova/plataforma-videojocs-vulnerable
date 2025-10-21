<?php
session_start();
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Registro - GameHub</title>
  <link rel="stylesheet" href="../frontend/assets/css/style_registre.css" />
</head>
<body>

  <div class="split-container">

    <div class="left-panel">
      <div class="background-carousel-container">
        <div class="carousel-slide" id="slide-a"></div>
        <div class="carousel-slide" id="slide-b"></div>
      </div>
    </div>

    <div class="right-panel">
      <canvas id="interactive-dots-canvas"></canvas>

      <form class="registro-form" method="post" action="./funcions/crear_usuari.php">
        <div class="form-header">
          <h1>Crear Cuenta En Shit Games</h1>
          <p>Únete a la comunidad de Shit Games.</p>
        </div>

        <?php if ($error): ?><p class="form-message error"><?php echo $error; ?></p><?php endif; ?>
        <?php if ($success): ?><p class="form-message success"><?php echo $success; ?></p><?php endif; ?>

        <div class="form-body">
          <div class="input-group">
            <input id="nom" type="text" name="nom" required placeholder="Nombre">
            <input id="cognom" type="text" name="cognom" required placeholder="Apellidos">
          </div>
          <input id="nickname" type="text" name="nickname" required placeholder="Nombre de usuario">
          <input id="email" type="email" name="email" required placeholder="Email">
          <input id="data_naixement" type="date" name="data_naixement" required>
          <input id="pass" type="password" name="pass" required placeholder="Contraseña">
          <input id="confirm_password" type="password" name="confirm_password" required placeholder="Confirmar contraseña">
        </div>

        <div class="form-footer">
          <button type="submit">Registrarse</button>
          <div class="bottom-link">
            <span>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></span>
          </div>
        </div>
      </form>
    </div>

  </div>

  <script src="../frontend/assets/js/carousel.js"></script>
  <script src="../frontend/assets/js/interactive-dots.js"></script>
</body>
</html>