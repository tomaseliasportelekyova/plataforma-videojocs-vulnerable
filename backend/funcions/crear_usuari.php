<?php
// En /backend/funcions/crear_usuari.php

session_start();
require "./db_mysqli.php";

// Variable para controlar el destino de la redirección
$redirect_location = "../registre.php"; // Por defecto, volvemos al registro

if (isset($_POST['nom'], $_POST['cognom'], $_POST['nickname'], $_POST['email'], $_POST['pass'], $_POST['confirm_password'], $_POST['data_naixement'])) {
  
  $nom = $_POST['nom'];
  $cognom = $_POST['cognom'];
  $nickname = $_POST['nickname'];
  $email = $_POST['email'];
  $password = $_POST['pass'];
  $confirm_password = $_POST['confirm_password'];
  $data_naixement = $_POST['data_naixement'];

  if ($password !== $confirm_password) {
    $_SESSION['error'] = "Las contraseñas no coinciden.";
    // Si hay error, no cambiamos la redirección
  } else {
    $sql = "INSERT INTO usuaris (nickname, email, password_hash, nom, cognom, data_naixement) VALUES ('$nickname', '$email', '$password', '$nom', '$cognom', '$data_naixement')";

    if ($conn->query($sql)) {
      // ¡CAMBIO CLAVE! Si el registro es exitoso...
      $_SESSION['success'] = "¡Usuario registrado correctamente! Ya puedes iniciar sesión.";
      $redirect_location = "../login.php"; // ...cambiamos el destino a la página de login
    } else {
      $_SESSION['error'] = "Error: el nickname o el email ya existen.";
    }
  }
  $conn->close();

} else {
    $_SESSION['error'] = "Por favor, completa todos los campos del formulario.";
}

// Redirigimos al destino que hemos decidido
header("Location: " . $redirect_location);
exit();
?>