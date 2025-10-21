<?php
session_start();

// Recogemos cualquier mensaje que nos pueda llegar desde otras páginas
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';

// Limpiamos los mensajes para que no se muestren de nuevo si se recarga la página
unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <link rel="stylesheet" href="../frontend/assets/css/style.css" />
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="image_login">
        <img src="../frontend/imatges/videojuegos.png" alt="Panel decorativo" />
      </div>

      <form class="login-panel" method="post" action="./funcions/validacio_login.php">
        <div class="form-header">
          <div class="header-image">
            <img src="../frontend/imatges/pacman.png" />
          </div>
          <h1 class="login-title">Login</h1>
        </div>

        <?php if ($success_message): ?><p style="color: green; text-align: center;"><?php echo $success_message; ?></p><?php endif; ?>
        <?php if ($error_message): ?><p style="color: red; text-align: center;"><?php echo $error_message; ?></p><?php endif; ?>

        <div class="form-body">
          <div class="form-field">
            <label for="email"><b>Email</b></label>
            <input id="email" type="text" name="email" placeholder="Introduce tu email" required />
          </div>

          <div class="form-field">
            <label for="pass"><b>Contraseña</b></label>
            <input id="pass" type="password" name="pass" placeholder="Introduce tu contraseña" required />
          </div>
        </div>

        <div class="form-footer">
          <button type="submit">Entrar</button>
          <div class="bottom-container">
            <a href="registre.php">Crear cuenta</a>
          </div>
        </div>
      </form>
    </div>
  </div>
</body>
</html>