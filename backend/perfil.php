<?php
session_start();

// Comprobación de sesión
if (!isset($_SESSION['email'], $_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nickname = $_SESSION['nickname']; // Necessari pel header

// Conexión BBDD
require "./funcions/db_mysqli.php";

// --- Variables per missatges i dades ---
$error_message = '';
$success_message = '';
$current_nickname = '';
$current_email = '';
$current_nom = '';
$current_cognom = '';
$current_data_registre = 'N/A';
$current_photo = '../frontend/imatges/users/default_user.png';
$global_rank = 'N/A';
$played_games = [];
$wishlist_games = [];

// --- Lógica del Contador de Cookies para Nickname ---
$cookie_name = "nickname_changes_count_" . $user_id;
$nickname_changes_count = isset($_COOKIE[$cookie_name]) ? intval($_COOKIE[$cookie_name]) : 0;
$max_nickname_changes = 3;
$can_change_nickname = ($nickname_changes_count < $max_nickname_changes);

// --- Recuperar Datos Principales del Usuario ---
$sql_select_user = "SELECT nickname, email, nom, cognom, data_registre, photo FROM usuaris WHERE id = ?";
$stmt_select_user = $conn->prepare($sql_select_user);
if ($stmt_select_user) {
    $stmt_select_user->bind_param("i", $user_id);
    $stmt_select_user->execute();
    $resultado_user = $stmt_select_user->get_result();
    $user = $resultado_user->fetch_assoc();
    $stmt_select_user->close();

    if ($user) {
        $current_nickname = $user['nickname'];
        $current_email = $user['email'];
        $current_nom = $user['nom'];
        $current_cognom = $user['cognom'];
        $current_data_registre = $user['data_registre'] ? date("d/m/Y", strtotime($user['data_registre'])) : 'N/A';
        $current_photo = (!empty($user['photo']) && file_exists($user['photo'])) ? $user['photo'] : '../frontend/imatges/users/default_user.png';
    } else {
        session_destroy();
        header("Location: login.php?error=user_session_invalid");
        exit();
    }
} else {
    $error_message = "Error preparant la consulta d'usuari: " . $conn->error;
}

// --- Calcular Ranking Global del Usuario ---
// (Requereix MySQL 8+ per RANK())
$sql_rank = "
    SELECT user_rank FROM (
        SELECT
            u.id as user_id,
            RANK() OVER (ORDER BY SUM(COALESCE(p.puntuacio_obtinguda, 0)) DESC) as user_rank
        FROM usuaris u
        LEFT JOIN partides p ON u.id = p.usuari_id -- LEFT JOIN per incloure usuaris sense partides
        GROUP BY u.id
    ) as ranked_users
    WHERE user_id = ?
";
$stmt_rank = $conn->prepare($sql_rank);
if ($stmt_rank) {
    $stmt_rank->bind_param("i", $user_id);
    $stmt_rank->execute();
    $result_rank = $stmt_rank->get_result();
    if ($rank_data = $result_rank->fetch_assoc()) {
        $global_rank = '#' . $rank_data['user_rank'];
    } else {
        $global_rank = 'Sense Rank';
    }
    $stmt_rank->close();
} else {
    $global_rank = 'Error Rank';
}


// --- Recuperar Juegos Jugados, Tiempo, Última Partida y Ranking por Juego ---
// ================== CONSULTA CORREGIDA ==================
$sql_played = "
    SELECT
        j.id as joc_id,
        j.nom_joc,
        j.cover_image_url, -- Assegura't que aquesta columna existeix a 'jocs'
        COALESCE(SUM(p.durada_segons), 0) as total_temps_jugat_segons,
        MAX(p.data_partida) as ultima_vegada_jugat,
        -- Subconsulta per obtenir la puntuació total de l'usuari actual en aquest joc
        COALESCE((SELECT SUM(p_user.puntuacio_obtinguda)
                  FROM partides p_user
                  WHERE p_user.joc_id = j.id AND p_user.usuari_id = ?), 0) as current_user_total_score,
        -- Subconsulta per calcular el rank dins del joc (pot ser lenta)
        (SELECT COUNT(*) + 1
         FROM (SELECT usuari_id, SUM(puntuacio_obtinguda) as total_score
               FROM partides
               WHERE joc_id = j.id
               GROUP BY usuari_id) as game_scores
         WHERE game_scores.total_score >
               COALESCE((SELECT SUM(p_user2.puntuacio_obtinguda)
                         FROM partides p_user2
                         WHERE p_user2.joc_id = j.id AND p_user2.usuari_id = ?), 0)
        ) as rank_en_joc
    FROM jocs j -- Comencem per jocs per si l'usuari no ha jugat encara
    LEFT JOIN partides p ON j.id = p.joc_id AND p.usuari_id = ? -- Unim partides de l'usuari
    -- WHERE j.id IN (SELECT DISTINCT joc_id FROM partides WHERE usuari_id = ?) -- Alternativa: Només jocs jugats
    GROUP BY j.id, j.nom_joc, j.cover_image_url -- Afegit cover_image_url al GROUP BY
    ORDER BY ultima_vegada_jugat DESC, j.nom_joc ASC
";

$stmt_played = $conn->prepare($sql_played);
if ($stmt_played) {
    // Necessitem passar l'user_id tres vegades per la consulta corregida
    $stmt_played->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt_played->execute();
    $result_played = $stmt_played->get_result();
    while ($game = $result_played->fetch_assoc()) {
        // Formatejar temps i data
        $total_segons = $game['total_temps_jugat_segons'];
        $hores = floor($total_segons / 3600);
        $minuts = floor(($total_segons % 3600) / 60);
        $game['temps_format'] = sprintf('%dh %dm', $hores, $minuts); // Més curt
        $game['ultima_vegada_format'] = $game['ultima_vegada_jugat'] ? date("d/m/y", strtotime($game['ultima_vegada_jugat'])) : 'Mai'; // Format més curt
        // Assignar imatge de placeholder si no hi ha cover_image_url o està buida
        $game['cover_image_url'] = (!empty($game['cover_image_url']) && file_exists(parse_url($game['cover_image_url'], PHP_URL_PATH)))
                                   ? $game['cover_image_url']
                                   : 'https://via.placeholder.com/160x120/1A1A2A/E0E0E0?text=' . urlencode($game['nom_joc']);
        $played_games[] = $game;
    }
    $stmt_played->close();
} else {
     $error_message .= " Error recuperant jocs jugats: " . $conn->error;
}
// ================== FI CONSULTA CORREGIDA ==================


// --- Lógica de Actualización (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // ... (La lògica d'update es queda igual que abans) ...
     $new_nickname = trim($_POST['nickname'] ?? $current_nickname);
    $new_email = trim($_POST['email'] ?? $current_email);
    $new_password = $_POST['password'] ?? null;
    $confirm_password = $_POST['confirm_password'] ?? null;
    $new_nom = trim($_POST['nom'] ?? $current_nom);
    $new_cognom = trim($_POST['cognom'] ?? $current_cognom);
    $new_photo_path = $current_photo;
    $nickname_changed = ($new_nickname !== $current_nickname);

    // Validacions
    if ($nickname_changed && !$can_change_nickname) {
        $error_message = "Has alcanzado el límite de " . $max_nickname_changes . " cambios de nickname.";
        $new_nickname = $current_nickname;
        $nickname_changed = false;
    } else if (!empty($new_password) && $new_password !== $confirm_password) {
        $error_message = "Las contraseñas nuevas no coinciden.";
    } else if (empty($new_nickname) || empty($new_email) || empty($new_nom) || empty($new_cognom)) {
        $error_message = "Nickname, Email, Nombre y Apellido no pueden estar vacíos.";
    } else {
        // Gestió Foto (igual que abans)
        if (isset($_FILES['nueva_foto']) && $_FILES['nueva_foto']['error'] === UPLOAD_ERR_OK && $_FILES['nueva_foto']['size'] > 0) {
           // ... (codi de gestió de la foto) ...
             $upload_dir = '../frontend/imatges/users/';
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB limit

            if (in_array($_FILES['nueva_foto']['type'], $allowed_types) && $_FILES['nueva_foto']['size'] <= $max_size) {
                $file_extension = strtolower(pathinfo($_FILES['nueva_foto']['name'], PATHINFO_EXTENSION));
                $filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
                $target_file = $upload_dir . $filename;

                if (!file_exists($upload_dir)) {
                   mkdir($upload_dir, 0775, true);
                }

                if (move_uploaded_file($_FILES['nueva_foto']['tmp_name'], $target_file)) {
                    if ($current_photo != '../frontend/imatges/users/default_user.png' && file_exists($current_photo)) {
                        // unlink($current_photo);
                    }
                    $new_photo_path = $target_file;
                } else {
                    $error_message .= " Error al mover la nueva foto subida.";
                }
            } else {
                 $error_message .= " Formato de imagen no permitido o archivo demasiado grande (max 5MB).";
            }
        } elseif (isset($_FILES['nueva_foto']) && $_FILES['nueva_foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error_message .= " Error al subir la foto (código: ".$_FILES['nueva_foto']['error'].").";
        }


        // Update BBDD (igual que abans, només si no hi ha errors)
        if(empty($error_message)) {
            $fields_to_update = []; $types = ""; $params = [];
            if ($new_nickname !== $current_nickname) { $fields_to_update[] = "nickname = ?"; $types .= "s"; $params[] = $new_nickname; }
            if ($new_email !== $current_email) { $fields_to_update[] = "email = ?"; $types .= "s"; $params[] = $new_email; }
            if ($new_nom !== $current_nom) { $fields_to_update[] = "nom = ?"; $types .= "s"; $params[] = $new_nom; }
            if ($new_cognom !== $current_cognom) { $fields_to_update[] = "cognom = ?"; $types .= "s"; $params[] = $new_cognom; }
            if ($new_photo_path !== $current_photo) { $fields_to_update[] = "photo = ?"; $types .= "s"; $params[] = $new_photo_path; }
            if (!empty($new_password)) {
                 $password_hash = $new_password; // Recorda usar password_hash()
                 $fields_to_update[] = "password_hash = ?"; $types .= "s"; $params[] = $password_hash;
            }

            if(!empty($fields_to_update)) {
                $types .= "i"; $params[] = $user_id;
                $sql_update = "UPDATE usuaris SET " . implode(", ", $fields_to_update) . " WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                if ($stmt_update) {
                    $stmt_update->bind_param($types, ...$params);
                    if ($stmt_update->execute()) {
                        $success_message = "Perfil actualizado correctamente.";
                        // Actualitzar variables current
                        $current_nickname = $new_nickname; $current_email = $new_email; $current_nom = $new_nom; $current_cognom = $new_cognom; $current_photo = $new_photo_path;
                        // Actualitzar sessió i cookie
                        if ($new_photo_path !== $current_photo) {
                            $_SESSION['user_photo'] = $new_photo_path; 
                        }
                        // Si s'ha esborrat la foto i torna a la default (necessitaria lògica extra si permetem esborrar)
                        elseif ($photo_deleted_and_set_to_default) {
                            $_SESSION['user_photo'] = '../frontend/imatges/users/default_user.png';
                        }
                        $_SESSION['email'] = $new_email;
                        if ($nickname_changed) {
                            $_SESSION['nickname'] = $new_nickname; $nickname = $new_nickname;
                            $nickname_changes_count++;
                            setcookie($cookie_name, $nickname_changes_count, time() + (365 * 24 * 60 * 60), "/");
                            $can_change_nickname = ($nickname_changes_count < $max_nickname_changes);
                        }
                    } else { /* ... gestió errors SQL ... */
                         if ($conn->errno == 1062) { $error_message = "Error: El email o nickname ya está en uso."; }
                         else { $error_message = "Error al actualizar: " . $stmt_update->error; }
                    }
                    $stmt_update->close();
                } else { $error_message = "Error preparing update: " . $conn->error; }
            } else { if(empty($error_message)) $success_message = "No se realizaron cambios."; }
        }
    }
}


// --- Lógica de Eliminación (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    // ... (Lógica d'eliminació igual que abans) ...
    $sql_delete = "DELETE FROM usuaris WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
     if (!$stmt_delete) {
        $error_message = "Error preparing delete statement: " . $conn->error;
    } else {
        $stmt_delete->bind_param("i", $user_id);
        if ($stmt_delete->execute()) {
            setcookie($cookie_name, '', time() - 3600, "/");
            session_destroy();
            echo "<script>alert('Cuenta eliminada correctamente.'); window.location.href='login.php';</script>";
            exit();
        } else {
            $error_message = "Error al eliminar la cuenta: " . $stmt_delete->error;
        }
        $stmt_delete->close();
    }
}

// --- Definim pàgina pel header ---
$paginaActual = ''; // Cap actiu al perfil

// Tanquem la connexió si encara està oberta
if ($conn && $conn->ping()) {
   $conn->close();
}
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
    // Funcions JS toggleEditMode, previsualizarFoto, i guardar valors inicials (igual que abans)
     function toggleEditMode(enable) {
      const form = document.getElementById('perfil-form');
      const inputs = form.querySelectorAll('input[data-editable]');
      const editButton = document.getElementById('edit-button');
      const saveButton = document.getElementById('save-button');
      const cancelButton = document.getElementById('cancel-button');
      const passwordFields = form.querySelectorAll('.password-field');
      const fileInputLabel = document.querySelector('.boton-foto.edit-mode-item');

      if (enable) {
        form.classList.add('edit-mode');
        form.classList.remove('view-mode');
        inputs.forEach(input => {
            if(input.id === 'nickname' && !<?php echo json_encode($can_change_nickname); ?>) {
                input.setAttribute('readonly', true);
            } else {
                input.removeAttribute('readonly');
                input.style.backgroundColor = '#0F0F1A';
                input.style.borderColor = 'rgba(255, 255, 255, 0.15)';
            }
        });
        passwordFields.forEach(field => field.style.display = 'block');
        if(fileInputLabel) fileInputLabel.style.display = 'inline-block';
        if(document.getElementById('nickname') && <?php echo json_encode($can_change_nickname); ?>) { document.getElementById('nickname').focus(); }
        else if (document.getElementById('nom')) { document.getElementById('nom').focus(); }
        editButton.style.display = 'none';
        saveButton.style.display = 'inline-block';
        cancelButton.style.display = 'inline-block';
      } else {
        form.classList.remove('edit-mode');
        form.classList.add('view-mode');
        inputs.forEach(input => {
             input.setAttribute('readonly', true);
             input.value = input.defaultValue;
             input.style.backgroundColor = 'transparent';
             input.style.borderColor = 'transparent';
        });
        document.getElementById('password').value = '';
        document.getElementById('confirm_password').value = '';
        passwordFields.forEach(field => field.style.display = 'none');
        if(fileInputLabel) fileInputLabel.style.display = 'none';
        editButton.style.display = 'inline-block';
        saveButton.style.display = 'none';
        cancelButton.style.display = 'none';
        const currentPhotoSrc = '<?php echo htmlspecialchars($current_photo); ?>';
        const photoPreview = document.getElementById('perfil-foto-img');
        if (photoPreview) photoPreview.src = currentPhotoSrc;
        const fileInput = document.getElementById('foto-input');
        if (fileInput) fileInput.value = null;
        const errorMsg = form.querySelector('.form-message.error');
        const successMsg = form.querySelector('.form-message.success');
        if (errorMsg) errorMsg.remove();
        if (successMsg) successMsg.remove();
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
         const inputs = document.querySelectorAll('#perfil-form input[data-editable]');
         inputs.forEach(input => input.defaultValue = input.value);
         toggleEditMode(false);
    });

     function previsualizarFoto(event) {
        const reader = new FileReader();
        const output = document.getElementById('perfil-foto-img');
        reader.onload = function(){ if(output) output.src = reader.result; };
        if(event.target.files && event.target.files[0]){ reader.readAsDataURL(event.target.files[0]); }
        else { if(output) output.src = '<?php echo htmlspecialchars($current_photo); ?>'; }
    }
  </script>
</head>
<body class="dark-theme">

  <?php include './includes/_header.php'; ?>

  <main class="perfil-main-content">
      <form id="perfil-form" class="perfil-panel view-mode" method="post" enctype="multipart/form-data">

          <div class="perfil-edit-buttons">
              <button type="button" id="edit-button" class="boton-editar" onclick="toggleEditMode(true)">
                  <i class="fas fa-pencil-alt"></i> Editar Perfil
              </button>
              <button type="submit" id="save-button" name="update_profile" class="boton-principal" style="display: none;">
                  <i class="fas fa-save"></i> Guardar Cambios
              </button>
              <button type="button" id="cancel-button" class="boton-cancelar" onclick="toggleEditMode(false)" style="display: none;">
                   Cancelar
              </button>
          </div>

          <h1 class="perfil-titulo">Tu Perfil</h1>

          <?php if ($error_message): ?><p class="form-message error"><?php echo htmlspecialchars($error_message); ?></p><?php endif; ?>
          <?php if ($success_message): ?><p class="form-message success"><?php echo htmlspecialchars($success_message); ?></p><?php endif; ?>

          <div class="perfil-header-section">
              <div class="perfil-foto-container">
                  <img id="perfil-foto-img" class="perfil-foto" src="<?php echo htmlspecialchars($current_photo); ?>" alt="Foto de perfil" />
                  <label for="foto-input" class="boton-foto edit-mode-item" style="display: none;">Cambiar Foto</label>
                  <input type="file" id="foto-input" name="nueva_foto" accept="image/*" hidden onchange="previsualizarFoto(event)" data-editable />
              </div>
              <div class="perfil-info-basica">
                  <div class="perfil-info-row">
                      <div class="perfil-campo nickname-field">
                          <label for="nickname">Nickname</label>
                          <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($current_nickname); ?>" readonly data-editable <?php echo !$can_change_nickname ? 'title="Límite de cambios alcanzado"' : ''; ?>/>
                           <?php if (!$can_change_nickname): ?>
                              <small class="edit-mode-item" style="color: #d23b3b;">(Límite alcanzado)</small>
                           <?php else: ?>
                              <small class="edit-mode-item">(Restantes: <?php echo $max_nickname_changes - $nickname_changes_count; ?>)</small>
                           <?php endif; ?>
                      </div>
                      <div class="perfil-info-item rank-global">
                          <strong>Rank Global:</strong> <?php echo $global_rank; ?>
                      </div>
                  </div>
                  <div class="perfil-info-row">
                       <div class="perfil-campo">
                          <label for="nom">Nombre</label>
                          <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($current_nom); ?>" readonly data-editable />
                      </div>
                       <div class="perfil-campo">
                          <label for="cognom">Apellido</label>
                          <input type="text" id="cognom" name="cognom" value="<?php echo htmlspecialchars($current_cognom); ?>" readonly data-editable />
                      </div>
                  </div>
                   <div class="perfil-campo">
                      <label for="email">Email</label>
                      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_email); ?>" readonly data-editable />
                  </div>
                   <div class="perfil-campo password-field" style="display: none;">
                      <label for="password">Nueva Contraseña</label>
                      <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" data-editable autocomplete="new-password"/>
                  </div>
                   <div class="perfil-campo password-field" style="display: none;">
                      <label for="confirm_password">Confirmar Contraseña</label>
                      <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite la nueva contraseña" data-editable autocomplete="new-password"/>
                  </div>
              </div>
          </div>


          <div class="perfil-seccion">
              <h2><i class="fas fa-gamepad"></i> Mis Juegos</h2>
              <?php if (!empty($played_games)): ?>
                  <div class="played-games-grid">
                      <?php foreach ($played_games as $game): ?>
                          <div class="played-game-card">
                              <img src="<?php echo htmlspecialchars($game['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($game['nom_joc']); ?>" />
                              <div class="game-card-info">
                                  <h3><?php echo htmlspecialchars($game['nom_joc']); ?></h3>
                                  <p><i class="fas fa-clock"></i> <?php echo $game['temps_format']; ?></p>
                                  <p><i class="fas fa-calendar-alt"></i> Última: <?php echo $game['ultima_vegada_format']; ?></p>
                                  <p><i class="fas fa-trophy"></i> Rank: #<?php echo $game['rank_en_joc']; ?></p>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  </div>
              <?php else: ?>
                  <p class="placeholder-text">Aún no has jugado ninguna partida.</p>
              <?php endif; ?>
          </div>
          
          <div class="perfil-seccion">
                <h2><i class="fas fa-heart"></i> Mi Lista de Deseos</h2>
                <p class="placeholder-text">Aún no has añadido juegos a tu lista.</p>
                </div>

          <div class="perfil-seccion">
              <h2><i class="fas fa-info-circle"></i> Información de la Cuenta</h2>
              <div class="perfil-info-item">
                  <strong>Miembro desde:</strong> <?php echo $current_data_registre; ?>
              </div>
          </div>

          <div class="perfil-acciones">
              <a class="perfil-salida" href="logout.php">Cerrar Sesión</a>
              <button type="submit" name="delete_account" class="boton-peligro" onclick="return confirm('¿Estás seguro de que quieres eliminar tu cuenta permanentemente? Esta acción no se puede deshacer.');">Eliminar Cuenta</button>
          </div>
      </form>
  </main>

</body>
</html>