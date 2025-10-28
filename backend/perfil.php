<?php
session_start();

// Comprobación de sesión
if (!isset($_SESSION['email'], $_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Necesitamos el ID para la cookie

// Conexión BBDD (usando tu script)
require "./funcions/db_mysqli.php"; // Asegúrate que $conn se define aquí

// --- Lógica del Contador de Cookies para Nickname ---
$cookie_name = "nickname_changes_count_" . $user_id;
$nickname_changes_count = isset($_COOKIE[$cookie_name]) ? intval($_COOKIE[$cookie_name]) : 0;
$max_nickname_changes = 3;
$can_change_nickname = ($nickname_changes_count < $max_nickname_changes);
$error_message = '';
$success_message = '';

// --- Recuperar Datos del Usuario (con consulta preparada) ---
$sql_select = "SELECT nickname, email, password_hash, nom, cognom, data_registre, photo FROM usuaris WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $user_id);
$stmt_select->execute();
$resultado = $stmt_select->get_result();
$user = $resultado->fetch_assoc();
$stmt_select->close();

if (!$user) {
    // Si no se encuentra el usuario (raro, pero posible)
    session_destroy();
    header("Location: login.php?error=user_not_found");
    exit();
}

// Valores actuales (usaremos htmlspecialchars al mostrarlos)
$current_nickname = $user['nickname'];
$current_email = $user['email'];
$current_password_placeholder = "********"; // No mostramos el hash real
$current_nom = $user['nom'];
$current_cognom = $user['cognom'];
$current_data_registre = $user['data_registre'] ? date("d/m/Y", strtotime($user['data_registre'])) : 'N/A';
$current_photo = $user['photo'] ?? '../frontend/imatges/users/default_user.png';

// --- Procesar Actualización del Perfil (si se envió el formulario) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    // Nuevos valores del formulario (o los actuales si no se cambian)
    $new_nickname = $_POST['nickname'] ?? $current_nickname;
    $new_email = $_POST['email'] ?? $current_email;
    $new_password = $_POST['password'] ?? null; // Contraseña solo si se introduce nueva
    $new_nom = $_POST['nom'] ?? $current_nom;
    $new_cognom = $_POST['cognom'] ?? $current_cognom;
    $new_photo_path = $current_photo; // Mantenemos la foto actual por defecto

    $nickname_changed = ($new_nickname !== $current_nickname);

    // Validar límite de cambios de nickname
    if ($nickname_changed && !$can_change_nickname) {
        $error_message = "Has alcanzado el límite de " . $max_nickname_changes . " cambios de nickname.";
        $new_nickname = $current_nickname; // Revertir al actual si no se puede cambiar
        $nickname_changed = false; // Marcar como no cambiado
    } else {
         // --- Gestión de la Foto ---
        if (isset($_FILES['nueva_foto']) && $_FILES['nueva_foto']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../frontend/imatges/users/';
            // Crear un nombre de archivo único para evitar colisiones
            $file_extension = pathinfo($_FILES['nueva_foto']['name'], PATHINFO_EXTENSION);
            $filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $upload_dir . $filename;

            // Mover archivo
            if (move_uploaded_file($_FILES['nueva_foto']['tmp_name'], $target_file)) {
                // Borrar foto antigua si no es la default (opcional)
                if ($current_photo != '../frontend/imatges/users/default_user.png' && file_exists($current_photo)) {
                    // unlink($current_photo); // Descomentar si quieres borrar la antigua
                }
                $new_photo_path = $target_file; // Actualizar ruta
            } else {
                $error_message .= " Error al subir la nueva foto.";
            }
        }

        // --- Preparar la Consulta de Actualización ---
        // Construimos la query dinámicamente según si se cambia la contraseña
        $fields_to_update = [];
        $types = "";
        $params = [];

        // Añadir campos siempre actualizables
        $fields_to_update[] = "nickname = ?"; $types .= "s"; $params[] = &$new_nickname;
        $fields_to_update[] = "email = ?";    $types .= "s"; $params[] = &$new_email;
        $fields_to_update[] = "nom = ?";      $types .= "s"; $params[] = &$new_nom;
        $fields_to_update[] = "cognom = ?";   $types .= "s"; $params[] = &$new_cognom;
        $fields_to_update[] = "photo = ?";    $types .= "s"; $params[] = &$new_photo_path;

        // Añadir contraseña solo si se ha proporcionado una nueva
        if (!empty($new_password)) {
            // ¡¡IMPORTANTE!! Hashear la contraseña antes de guardarla
            // $password_hash = password_hash($new_password, PASSWORD_DEFAULT); // Forma recomendada
             $password_hash = $new_password; // Manteniendo tu sistema actual (NO SEGURO)
            $fields_to_update[] = "password_hash = ?"; $types .= "s"; $params[] = &$password_hash;
        }

        // Añadir el ID del usuario al final para el WHERE
        $types .= "i";
        $params[] = &$user_id;

        $sql_update = "UPDATE usuaris SET " . implode(", ", $fields_to_update) . " WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);

        // Vincular parámetros dinámicamente
        $stmt_update->bind_param($types, ...$params);

        if ($stmt_update->execute()) {
            $success_message = "Perfil actualizado correctamente.";
            // Actualizar variables current para mostrar los nuevos datos
            $current_nickname = $new_nickname;
            $current_email = $new_email;
            $current_nom = $new_nom;
            $current_cognom = $new_cognom;
            $current_photo = $new_photo_path;

             // Actualizar email en sesión si cambió
             if ($new_email !== $_SESSION['email']) {
                $_SESSION['email'] = $new_email;
            }
             // Actualizar nickname en sesión si cambió
            if ($nickname_changed) {
                 $_SESSION['nickname'] = $new_nickname;
                // Incrementar contador y guardar cookie por 1 año
                $nickname_changes_count++;
                setcookie($cookie_name, $nickname_changes_count, time() + (365 * 24 * 60 * 60), "/");
                $can_change_nickname = ($nickname_changes_count < $max_nickname_changes); // Recalcular permiso
            }
        } else {
            $error_message = "Error al actualizar el perfil: " . $stmt_update->error;
            // Podría ser email/nickname duplicado
            if ($conn->errno == 1062) { // Código de error para entrada duplicada
                 $error_message = "Error: El email o nickname ya está en uso por otro usuario.";
            }
        }
        $stmt_update->close();
    }
}

// --- Procesar Eliminación de Cuenta ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $sql_delete = "DELETE FROM usuaris WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $user_id);
    if ($stmt_delete->execute()) {
        // Borrar cookie de contador
        setcookie($cookie_name, '', time() - 3600, "/"); // Expira la cookie
        session_destroy();
        echo "<script>alert('Cuenta eliminada correctamente.'); window.location.href='login.php';</script>";
        exit();
    } else {
        $error_message = "Error al eliminar la cuenta: " . $stmt_delete->error;
    }
    $stmt_delete->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Perfil de <?php echo htmlspecialchars($current_nickname); ?> - Shit Games</title>
  <link rel="stylesheet" href="../frontend/assets/css/style_dashboard.css" />
  <link rel="stylesheet" href="../frontend/assets/css/style_perfil.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script>
    // Función para habilitar edición (simplificada)
    function habilitarEdicion(inputId) {
      const input = document.getElementById(inputId);
      if (input) {
        input.removeAttribute('readonly');
        input.focus();
        // Opcional: Cambiar estilo para indicar que es editable
        input.style.backgroundColor = '#e0f7fa';
      }
      // Ocultar el botón de editar después de hacer clic
      const button = input.nextElementSibling;
      if (button && button.tagName === 'BUTTON') {
          button.style.display = 'none';
      }
    }
     // Función para previsualizar imagen
     function previsualizarFoto(event) {
        const reader = new FileReader();
        reader.onload = function(){
            const output = document.getElementById('perfil-foto-img');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
  </script>
</head>
<body class="dark-theme"> <header class="main-header floating">
    <div class="logo">
      <a href="dashboard.php">Shit Games</a>
    </div>
    <nav class="main-nav">
      <a href="dashboard.php#todos">Todos</a> <a href="dashboard.php#juegos">Juegos</a>
      <a href="dashboard.php#reclamar">Reclamar</a>
    </nav>
    <div class="user-profile">
      <a href="perfil.php" title="Ir al perfil de <?php echo htmlspecialchars($current_nickname); ?>">
        <div class="avatar-placeholder"><?php echo strtoupper(substr($current_nickname, 0, 1)); ?></div>
      </a>
    </div>
  </header>
  <main class="perfil-main-content">
      <form class="perfil-panel" method="post" enctype="multipart/form-data">
          <h1 class="perfil-titulo">Tu Perfil</h1>

          <?php if ($error_message): ?>
              <p class="form-message error"><?php echo $error_message; ?></p>
          <?php endif; ?>
          <?php if ($success_message): ?>
              <p class="form-message success"><?php echo $success_message; ?></p>
          <?php endif; ?>

          <div class="perfil-foto-container">
              <img id="perfil-foto-img" class="perfil-foto" src="<?php echo htmlspecialchars($current_photo); ?>" alt="Foto de perfil" />
              <label for="foto-input" class="boton-foto">Cambiar Foto</label>
              <input type="file" id="foto-input" name="nueva_foto" accept="image/*" hidden onchange="previsualizarFoto(event)" />
          </div>

          <div class="perfil-seccion">
              <h2>Datos Personales</h2>
              <div class="perfil-campo">
                  <label for="nickname">Nickname</label>
                  <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($current_nickname); ?>" <?php echo $can_change_nickname ? '' : 'readonly'; ?> />
                  <?php if ($can_change_nickname): ?>
                      <button type="button" onclick="habilitarEdicion('nickname')">Editar</button>
                      <small>(Cambios restantes: <?php echo $max_nickname_changes - $nickname_changes_count; ?>)</small>
                  <?php else: ?>
                      <small>(Límite de cambios alcanzado)</small>
                  <?php endif; ?>
              </div>
              <div class="perfil-campo">
                  <label for="email">Email</label>
                  <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_email); ?>" readonly />
                  <button type="button" onclick="habilitarEdicion('email')">Editar</button>
              </div>
               <div class="perfil-campo">
                  <label for="nom">Nombre</label>
                  <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($current_nom); ?>" readonly />
                  <button type="button" onclick="habilitarEdicion('nom')">Editar</button>
              </div>
               <div class="perfil-campo">
                  <label for="cognom">Apellido</label>
                  <input type="text" id="cognom" name="cognom" value="<?php echo htmlspecialchars($current_cognom); ?>" readonly />
                  <button type="button" onclick="habilitarEdicion('cognom')">Editar</button>
              </div>
              <div class="perfil-campo">
                  <label for="password">Nueva Contraseña</label>
                  <input type="password" id="password" name="password" placeholder="Dejar en blanco para no cambiar" readonly/>
                   <button type="button" onclick="habilitarEdicion('password')">Cambiar</button>
              </div>
          </div>

          <div class="perfil-seccion">
              <h2>Información de la Cuenta</h2>
              <div class="perfil-info-item">
                  <strong>Miembro desde:</strong> <?php echo $current_data_registre; ?>
              </div>
               </div>


          <div class="perfil-acciones">
              <button type="submit" name="update_profile" class="boton-principal">Guardar Cambios</button>
              <button type="submit" name="delete_account" class="boton-peligro" onclick="return confirm('¿Estás seguro de que quieres eliminar tu cuenta permanentemente? Esta acción no se puede deshacer.');">Eliminar Cuenta</button>
              <a class="perfil-salida" href="logout.php">Cerrar Sesión</a>
          </div>
      </form>
  </main>

</body>
</html>