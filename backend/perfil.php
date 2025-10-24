<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Conexión a la base de datos
$conexion = new mysqli('localhost', 'plataforma_user', '123456789a', 'plataforma_videojocs');
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$email = $_SESSION['email'];

// Obtener datos del usuario
$sql = "SELECT * FROM usuaris WHERE email = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();
$user = $resultado->fetch_assoc();

if (!$user) {
    echo "<script>alert('Usuario no encontrado'); window.location.href='login.php';</script>";
    exit();
}

$nickname = htmlspecialchars($user['nickname']);
$password = htmlspecialchars($user['password_hash']);
$photo = isset($user['photo']) ? htmlspecialchars($user['photo']) : '../frontend/imatges/default_user.png';

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        if (isset($_FILES['nueva_foto']) && $_FILES['nueva_foto']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../frontend/imatges/';
            $filename = basename($_FILES['nueva_foto']['name']);
            $target_file = $upload_dir . $filename;
            move_uploaded_file($_FILES['nueva_foto']['tmp_name'], $target_file);
            $photo = $target_file;
        }

        if (!empty($_POST['nickname'])) {
            $nickname = $_POST['nickname'];
        }
        if (!empty($_POST['password'])) {
            $password = $_POST['password'];
        }

        $sql = "UPDATE usuaris SET nickname = ?, password_hash = ?, photo = ? WHERE email = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssss", $nickname, $password, $photo, $email);
        $stmt->execute();

        echo "<script>alert('Perfil actualizado correctamente');</script>";
    }

    if (isset($_POST['delete_account'])) {
        $sql = "DELETE FROM usuaris WHERE email = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        session_destroy();
        echo "<script>alert('Cuenta eliminada'); window.location.href='login.php';</script>";
        exit();
    }
}
?>

<!-- HTML del perfil -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
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
        <img class="perfil-foto" src="<?php echo $photo; ?>" />
        <label for="foto-input" class="boton-foto">Cambiar foto</label>
        <input type="file" id="foto-input" name="nueva_foto" accept="image/*" hidden />
      </div>

      <div class="perfil-campo">
        <label for="nickname">Nickname</label>
        <input type="text" id="nickname" name="nickname" value="<?php echo $nickname; ?>" readonly />
        <button type="button" onclick="habilitarCampo('nickname')">Editar nickname</button>
      </div>

      <div class="perfil-campo">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" value="<?php echo $password; ?>" readonly />
        <button type="button" onclick="habilitarCampo('password')">Editar contraseña</button>
      </div>

      <button type="submit" name="update_profile">Actualizar Perfil</button>
      <button type="submit" name="delete_account" style="background-color:#d23b3b;">Eliminar Cuenta</button>

      <a class="perfil-salida" href="logout.php">Cerrar sesión</a>
    </form>
  </div>
</body>
</html>
