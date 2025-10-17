<?php
session_start();

$valid_email = "admin@example.com";
$valid_pass = "12345";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $pass = trim($_POST['pass'] ?? '');

    if ($email === $valid_email && $pass === $valid_pass) {
        $_SESSION['email'] = $email;
        header("Location: registre.php");
        exit();
    } else {
        $error = "Email o contraseña incorrectos.";
    }
}
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
    <!-- NUEVO CONTENEDOR CON FONDO BLANCO Y SOMBREADO -->
    <div class="card">
      <!-- Panel de imagen -->
      <div class="image_login">
        <img src="../frontend/imatges/videojuegos.png" alt="Panel decorativo" />
      </div>

      <!-- Panel de login -->
      <form class="login-panel" method="post" action="#">
        <div class="form-header">
          <div class="header-image">
            <img src="../frontend/imatges/pacman.png" />
          </div>
          <h1 class="login-title">Login</h1>
        </div>

        <div class="form-body">
          <div class="form-field">
            <label for="email"><b>Email</b></label>
            <input id="email" type="text" name="email" placeholder="Introduce tu email" required />
          </div>

          <div class="form-field">
            <label for="pass"><b>Contraseña</b></label>
            <input id="pass" type="password" name="pass" placeholder="Introduce tu contraseña" required />
          </div>

          <label class="remember-label">
            <input type="checkbox" name="remember" checked /> Recordarme
          </label>
        </div>

        <div class="form-footer">
          <button type="submit">Entrar</button>
          <div class="bottom-container">
            <a href="#">¿Olvidaste la contraseña?</a>
            <a href="registre.php">Crear cuenta</a>
          </div>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
