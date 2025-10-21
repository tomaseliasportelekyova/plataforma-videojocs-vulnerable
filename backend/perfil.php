<?php
session_start();

// Simulación de datos del usuario
$user = [
    'email' => $_SESSION['email'] ?? 'admin@example.com',
    'nickname' => 'PacMaster',
    'password' => '12345',
    'photo' => '../frontend/imatges/default_user.png'
];

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Procesar foto
        if (isset($_FILES['nueva_foto']) && $_FILES['nueva_foto']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../frontend/imatges/';
            $filename = basename($_FILES['nueva_foto']['name']);
            $target_file = $upload_dir . $filename;
            move_uploaded_file($_FILES['nueva_foto']['tmp_name'], $target_file);
            $user['photo'] = $target_file;
        }

        // Procesar nickname y contraseña si fueron desbloqueados
        if (!empty($_POST['nickname'])) {
            $user['nickname'] = $_POST['nickname'];
        }
        if (!empty($_POST['password'])) {
            $user['password'] = $_POST['password'];
        }

        // Aquí guardarías los datos en base de datos
        echo "<script>alert('Perfil actualizado correctamente');</script>";
    }

    // Procesar eliminación
    if (isset($_POST['delete_account'])) {
        // Aquí eliminarías el perfil de la base de datos
        session_destroy();
        echo "<script>alert('Cuenta eliminada'); window.location.href='login.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Perfil de Usuario</title>
  <link rel="stylesheet" href="../frontend/assets/css/style_perfil.css" />
  <script>
    function habilitarCampo(id) {
      document.getElementById(id).removeAttribute('readonly');
      document.getElementById(id).focus();
    }
  </script>
</head>
<body>
  <div class="perfil-contenedor">
    <form class="perfil-panel-unico" method="post" enctype="multipart/form-data">
      <h1 class="perfil-titulo">Perfil personal</h1>

      <div class="perfil-foto-container">
        <img class="perfil-foto" src="<?php echo $user['photo']; ?>" />
        <label for="foto-input" class="boton-foto">Cambiar foto</label>
        <input type="file" id="foto-input" name="nueva_foto" accept="image/*" hidden />
      </div>

      <div class="perfil-campo">
        <label for="nickname">Nickname</label>
        <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($user['nickname']); ?>" readonly />
        <button type="button" onclick="habilitarCampo('nickname')">Editar nickname</button>
      </div>

      <div class="perfil-campo">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($user['password']); ?>" readonly />
        <button type="button" onclick="habilitarCampo('password')">Editar contraseña</button>
      </div>

      <button type="submit" name="update_profile">Actualizar Perfil</button>
      <button type="submit" name="delete_account" style="background-color:#d23b3b;">Eliminar Cuenta</button>

      <a class="perfil-salida" href="login.php">Cerrar sesión</a>
    </form>
  </div>
</body>
</html>
