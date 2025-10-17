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
  <title>Registro</title>
  <link rel="stylesheet" href="../frontend/assets/css/style_registre.css" />
</head>
<body>
  <div class="container">
    <div class="card">
      <form class="login-panel" method="post" action="#">
        <div class="form-header">
          <div class="header-image">
            <img src="../frontend/imatges/pacman.png" />
          </div>
          <h1 class="login-title">Registro</h1>
        </div>

        <div class="form-body">
          <div class="form-field">
            <label for="nombre"><b>Nombre real</b></label>
            <input id="nombre" type="text" name="nombre" placeholder="Introduce tu nombre" required />
          </div>

          <div class="form-field">
            <label for="apellidos"><b>Apellidos</b></label>
            <input id="apellidos" type="text" name="apellidos" placeholder="Introduce tus apellidos" required />
          </div>

          <div class="form-field">
            <label for="fecha_nacimiento"><b>Fecha de nacimiento</b></label>
            <input id="fecha_nacimiento" type="date" name="fecha_nacimiento" required />
          </div>

          <div class="form-field">
            <label for="usuario"><b>Nombre de usuario</b></label>
            <input id="usuario" type="text" name="usuario" placeholder="Elige un nombre de usuario" required />
          </div>

          <div class="form-field">
            <label for="pass"><b>Contraseña</b></label>
            <input id="pass" type="password" name="pass" placeholder="Crea una contraseña" required />
          </div>
        </div>

        <div class="form-footer">
          <button type="submit">Registrarse</button>
          <div class="bottom-container">
            <a href="index.php">¿Ya tienes cuenta? Inicia sesión</a>
          </div>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
